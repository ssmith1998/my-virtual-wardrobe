<?php

namespace App\Service;

use App\Entity\ClothingItem;
use App\Entity\User;
use App\Service\S3UploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Request\WardrobeItemRequest;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

final class WardrobeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly S3UploadService $s3UploadService,
        private readonly Security $security,
        private readonly VisionAiAgentService $visionAiAgentService
    ) {
    }

    public function createItem(User $user, Request $request): ClothingItem
    {
        $wardrobeRequest = new WardrobeItemRequest(
            name: $request->request->get('name') ?? null,
            type: $request->request->get('type') ?? null,
            color: $request->request->get('color') ?? null,
            season: $request->request->get('season') ?? null,
            imageUrl: $request->request->get('imageUrl') ?? null
        );
        $item = new ClothingItem();
        $this->setClothingItemInfo($wardrobeRequest, $item);

        return $item;
    }

    public function updateItem(ClothingItem $item, WardrobeItemRequest $request): ClothingItem
    {
        $this->setClothingItemInfo($request, $item);

        return $item;
    }

    public function setClothingItemInfo(WardrobeItemRequest $request, ClothingItem $item): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if ($request->name) {
            $item->setName($request->name);
        }
        if ($request->type) {
            $item->setType($request->type);
        }
        if ($request->color) {
            $item->setColor($request->color);
        }
        if ($request->season) {
            $item->setSeason($request->season);
        }

        if ($request->type) {
            $item->setType($request->type);
        }
        if ($request->color) {
            $item->setColor($request->color);
        }
        if ($request->season) {
            $item->setSeason($request->season);
        }
        if ($request->imageUrl) {
            $item->setImageUrl($request->imageUrl);
        }

        if ($user) {
            /** @var User $user */
            $item->setUser($user);
        }

        $this->entityManager->persist($item);
    }

    public function uploadItemImage(ClothingItem $item, UploadedFile $file): ClothingItem
    {
        $imageUrl = $this->s3UploadService->uploadFile($file, 'wardrobe', $item->getUser()->getId());
        $item->setImageUrl($imageUrl['url']);
        $item->setFilename($imageUrl['filename']);  
        $this->entityManager->flush();

        return $item;
    }

    /**
     * Undocumented function
     *
     * @param integer $id
     * @param User $user
     * @return array<string, int|string|null>
     */
    public function findItemForUser(int $id, User $user): array
    {
        /** @var ClothingItem $item */
        $item = $this->entityManager->getRepository(ClothingItem::class)->findOneBy(['id' => $id, 'user' => $user]);

        return $this->getClothingItemData($item);
    }

    /**
     * Undocumented function
     *
     * @param ClothingItem $item
     * @return array<string, int|string|null>
     */
    public function getClothingItemData(ClothingItem $item): array
    {
        return [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'type' => $item->getType(),
            'color' => $item->getColor(),
            'season' => $item->getSeason(),
            'imageUrl' => $item->getImageUrl(),
            'createdAt' => $item->getCreatedAt()->format('c'),
            'metadata' => $item->getMetadata(),
        ];
    }

    public function getClothingItemRecommendations(string $prompt, ?UploadedFile $image = null): array
    {
        $base64Image = null;
        
        //base 64 endoce image if passed in
        if ($image) {
            $imageContent = file_get_contents($image->getRealPath());
            $base64Image = base64_encode($imageContent);
        }

        //collect all items from the user's wardrobe and send them to the AI agent along with the prompt and image to get recommendations.
        $clothingItemsFromDb = $this->entityManager->getRepository(ClothingItem::class)->findByUserAndMetaData($this->security->getUser());

        //return the recommendations as an array of clothing item data.
        $recommendations = $this->visionAiAgentService->recommendOutfits($prompt, $clothingItemsFromDb, $base64Image);
        //if the AI agent fails, return an empty array.
        
        return array_map(function (ClothingItem $item) {
            return $this->getClothingItemData($item);
        }, $recommendations);
    }
}
