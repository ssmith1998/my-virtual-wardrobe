<?php

namespace App\Service;

use App\Entity\ClothingItem;
use App\Entity\User;
use App\Service\S3UploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
         if (isset($request->name)) {
             $item->setName($request->name);
         }
         if (isset($request->type)) {
             $item->setType($request->type);
         }
         if (array_key_exists('color', $request)) {     
             $item->setColor($request->color);
         }
         if (array_key_exists('season', $request)) {
             $item->setSeason($request->season);
         }
    }
            $item->setName($data['name']);
        }
        if (isset($data['type'])) {
            $item->setType($data['type']);
        }
        if (array_key_exists('color', $data)) {
            $item->setColor($data['color']);
        }
        if (array_key_exists('season', $data)) {
            $item->setSeason($data['season']);
        }
        if (array_key_exists('imageUrl', $data)) {
            $item->setImageUrl($data['imageUrl']);
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

    public function findItemForUser(int $id, User $user): ?ClothingItem
    {
        $item = $this->entityManager->getRepository(ClothingItem::class)->findOneBy(['id' => $id, 'user' => $user]);

        return $this->getClothingItemData($item);
    }

       private function getClothingItemData(ClothingItem $item): array
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
