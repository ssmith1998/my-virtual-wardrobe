<?php
 namespace App\Message\Handler;

use App\Entity\ClothingItem;
use App\Message\FileMetaDataMessage;
use App\Service\VisionAiAgentService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


#[AsMessageHandler]
class FileMetaDataMessageHandler
{
    public function __construct(
        private readonly VisionAiAgentService $visionAiAgentService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(FileMetaDataMessage $message): void
    {
        $fileMetadata = $this->visionAiAgentService->getClothingItemMetadata($message->getFilename());
        $this->logger->info('Received file metadata', [
            'filename' => $message->getFilename(),
            'userId' => $message->getUserId(),
            'metadata' => $fileMetadata,
        ]);

        $existingFileEntity = $this->entityManager->getRepository(ClothingItem::class)->findOneBy([
            'filename' => $message->getFilename(),
            'user' => $message->getUserId(),
        ]);

        if (!$existingFileEntity) {
            $this->logger->error('File entity not found', [
                'filename' => $message->getFilename(),
                'userId' => $message->getUserId(),
            ]);
            return;
        }

        $existingFileEntity->setMetadata($fileMetadata);
        $this->entityManager->flush();
    }
}
