<?php

namespace App\Controller;

use App\Entity\ClothingItem;
use App\Entity\User;
use App\Service\AwsAiAgentService;
use App\Service\WardrobeService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ApiController extends AbstractController
{
    public function __construct(
        private readonly AwsAiAgentService $aiAgent,
        private readonly WardrobeService $wardrobeService,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }


    #[Route('/api/login', name: 'app_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json([
                'message' => 'missing credentials',
            ], 401);
        }

        return $this->json([
            'user' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/api/register', name: 'app_register', methods: ['POST'])]
    public function register(Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return $this->json(['error' => 'Missing required fields: email, password'], 400);
        }

        $userRepository = $doctrine->getRepository(User::class);
        if ($userRepository->findOneByEmail($data['email'])) {
            return $this->json(['error' => 'User already exists'], 409);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));

        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'message' => 'User registered successfully',
            'user' => $user->getUserIdentifier(),
            ], 201);
    }

    #[Route('/api/wardrobe', name: 'app_wardrobe_list', methods: ['GET'])]
    public function listWardrobe(ManagerRegistry $doctrine): JsonResponse
    {
        $user = $this->getUser();
        $items = $doctrine->getRepository(ClothingItem::class)->findBy(['user' => $user]);

        return $this->json(array_map([$this, 'serializeClothingItem'], $items));
    }

    #[Route('/api/wardrobe', name: 'app_wardrobe_create', methods: ['POST'])]
    public function createWardrobeItem(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'], $data['type'])) {
            return $this->json(['error' => 'Missing required fields: name, type'], 400);
        }

        $user = $this->getUser();
        $item = $this->wardrobeService->createItem($user, $data);

        return $this->json($this->serializeClothingItem($item), 201);
    }

    #[Route('/api/wardrobe/{id}/image', name: 'app_wardrobe_upload_image', methods: ['POST'])]
    public function uploadWardrobeImage(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        $item = $this->wardrobeService->findItemForUser($id, $user);
        if (!$item) {
            return $this->json(['error' => 'Clothing item not found'], 404);
        }

        $file = $request->files->get('image');
        if (!$file instanceof UploadedFile) {
            return $this->json(['error' => 'No image file uploaded under field "image"'], 400);
        }

        try {
            $item = $this->wardrobeService->uploadItemImage($item, $file);
        } catch (\Throwable $exception) {
            return $this->json(['error' => $exception->getMessage()], 500);
        }

        return $this->json($this->serializeClothingItem($item));
    }

    #[Route('/api/outfits/recommend', name: 'app_outfits_recommend', methods: ['POST'])]
    public function recommendOutfits(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $prompt = $data['prompt'] ?? null;
        $season = $data['season'] ?? null;
        $occasion = $data['occasion'] ?? null;

        if (!is_string($prompt) || trim($prompt) === '') {
            return $this->json(['error' => 'Missing required field: prompt'], 400);
        }

        try {
            $recommendations = $this->aiAgent->recommendOutfits($prompt, $season, $occasion);
        } catch (\Throwable $exception) {
            return $this->json(['error' => $exception->getMessage()], 502);
        }

        return $this->json(['recommendations' => $recommendations]);
    }

    private function serializeClothingItem(ClothingItem $item): array
    {
        return [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'type' => $item->getType(),
            'color' => $item->getColor(),
            'season' => $item->getSeason(),
            'imageUrl' => $item->getImageUrl(),
            'createdAt' => $item->getCreatedAt()->format('c'),
        ];
    }
}
