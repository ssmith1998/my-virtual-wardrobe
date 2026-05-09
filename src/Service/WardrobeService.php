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

    public function createItem(User $user, array $data): ClothingItem
    {
        $item = new ClothingItem();
        $item->setName($data['name']);
        $item->setType($data['type']);
        $item->setColor($data['color'] ?? null);
        $item->setSeason($data['season'] ?? null);
        $item->setImageUrl($data['imageUrl'] ?? null);
        $item->setUser($user);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
    }

    public function updateItem(ClothingItem $item, array $data): ClothingItem
    {
        if (isset($data['name'])) {
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

        $this->entityManager->flush();

        return $item;
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
        return $this->entityManager->getRepository(ClothingItem::class)->findOneBy(['id' => $id, 'user' => $user]);
    }
}
