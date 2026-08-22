<?php

namespace App\Controller;

use App\Entity\ClothingItem;
use App\Entity\User;
use App\Message\FileMetaDataMessage;
use App\Repository\UserRepository;
use App\Request\RegisterRequest;
use App\Service\AwsAiAgentService;
use App\Service\S3UploadService;
use App\Service\WardrobeService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;

final class ApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private readonly WardrobeService $wardrobeService,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ManagerRegistry $doctrine,
        private readonly UserRepository $userRepository,
        private readonly S3UploadService $s3UploadService,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    #[Route('/api/register', name: 'app_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $request): JsonResponse
    {
        $data = [
            'email' => $request->email,
            'password' => $request->password,
        ];


        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            return $this->json(['error' => 'User already exists'], 409);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'message' => 'User registered successfully',
            'user' => $user->getEmail(),
            ], 201);
    }

    #[Route('/api/wardrobe', name: 'app_wardrobe_list', methods: ['GET'])]
    public function listWardrobe(): JsonResponse
    {
        $user = $this->getUser();
        $items = $this->doctrine->getRepository(ClothingItem::class)->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->json(array_map([$this->wardrobeService, 'getClothingItemData'], $items));
    }

    #[Route('/api/clothing-items', name: 'app_wardrobe_create', methods: ['POST'])]
    public function createWardrobeItem(Request $request): JsonResponse
    {
        //pass file to AI service to return description which will be sent to another AI service to generate colour and other properties. 
        //Then save the item with the generated properties and return the item data in the response.
        $file = $request->files->get('image');
        /** @var User $user */
        $user = $this->getUser();
        /** @var ClothingItem $item */
        $item = $this->wardrobeService->createItem($user, $request);
        $imageUrl = $this->s3UploadService->uploadFile($file, 'wardrobe', $user->getId());
        $item->setImageUrl($imageUrl['url']);
        $item->setFilename($imageUrl['filename']);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new FileMetaDataMessage($item->getFilename(), $user->getId()));

        return $this->json($this->wardrobeService->getClothingItemData($item), 201);
    }

    #[Route('/api/wardrobe/{clothingItem}/image', name: 'app_wardrobe_upload_image', methods: ['POST'])]
    public function uploadWardrobeImage(ClothingItem $clothingItem, Request $request): JsonResponse
    {

        $file = $request->files->get('image');
        if (!$file instanceof UploadedFile) {
            return $this->json(['error' => 'No image file uploaded under field "image"'], 400);
        }

        try {
            $item = $this->wardrobeService->uploadItemImage($clothingItem, $file);
        } catch (\Throwable $exception) {
            return $this->json(['error' => $exception->getMessage()], 500);
        }

        return $this->json($this->wardrobeService->getClothingItemData($item));
    }

    #[Route('/api/outfits/recommend', name: 'app_outfits_recommend', methods: ['POST'])]
    public function recommendOutfits(Request $request): JsonResponse
    {
        
        $prompt = $request->request->get('prompt');

        $image = $request->files->get('image', null);


        if (is_null($prompt) || trim($prompt) === '') {
            return $this->json(['error' => 'Prompt is required'], 400);
        }

        if($image) {
            if (!$image instanceof UploadedFile) {
                return $this->json(['error' => 'Invalid image file uploaded under field "image"'], 400);
            }
        }

        try {
            $recommendations = $this->wardrobeService->getClothingItemRecommendations($prompt, $image);
        } catch (\Throwable $exception) {
            return $this->json(['error' => $exception->getMessage()], 400);
        }

        return $this->json(['recommendations' => $recommendations]);
    }
}
