<?php

namespace App\Service;

use App\Entity\ClothingItem;
use App\Entity\User;
use App\Service\S3UploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Request\WardrobeItemRequest;

final class WardrobeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly S3UploadService $s3UploadService,
    ) {
    }

    public function createItem(User $user, WardrobeItemRequest $request): ClothingItem
    {
        $item = new ClothingItem();
        $this->setClothingItemInfo($request, $item);

        return $item;
    }

    public function updateItem(ClothingItem $item, WardrobeItemRequest $request): ClothingItem
    {
        $this->setClothingItemInfo($request, $item);

        return $item;
    }

    public function setClothingItemInfo(WardrobeItemRequest $request, ClothingItem $item): void
    {
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

        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }

    public function uploadItemImage(ClothingItem $item, UploadedFile $file): ClothingItem
    {
        $imageUrl = $this->s3UploadService->uploadFile($file, 'wardrobe', $item->getUser()->getId());
        $item->setImageUrl($imageUrl);
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
        ];
    }
}
