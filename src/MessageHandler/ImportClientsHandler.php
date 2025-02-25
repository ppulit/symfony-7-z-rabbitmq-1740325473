<?php

namespace App\MessageHandler;

use App\Message\ImportClientsMessage;
use App\Service\ImportClientsService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ImportClientsHandler
{
    public function __construct(
        private ImportClientsService $importClientsService,
    ) {
    }

    public function __invoke(ImportClientsMessage $message): void
    {
        $this->importClientsService->processFile($message->getFilePath(), $message->getFileName());
    }
}
