<?php

namespace App\Service;

use App\Entity\Client;
use App\Repository\ClientsRepository;
use App\Validator\CsvFileValidator;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[WithMonologChannel('import_file')]
class ImportClientsService
{
    public function __construct(
        private LoggerInterface $logger,
        private HubInterface $hub,
        private CsvFileValidator $csvFileValidator,
        private CsvFileReader $csvFileReader,
        private ClientsRepository $clientsRepository,
    ) {
    }

    public function processFile(string $filePath, string $fileName): void
    {
        $this->logger->info('Rozpoczęto przetwarzanie pliku: '.$filePath);

        $topic = 'progress_'.md5($fileName);

        $totalLines = $this->csvFileReader->countLinesInFile($filePath);
        $processedLines = 0;
        $invalidRows = [];
        $batchSize = 1000;
        $clientsData = [];

        foreach ($this->csvFileReader->readFile($filePath, true) as $line) {
            ++$processedLines;
            if (!$this->csvFileValidator->validateRow($line)) {
                $invalidRows[$processedLines] = $line;
                $this->logger->error('Niepoprawny wiersz '.$processedLines.': '.json_encode($line));
            } else {
                list($id, $fullName, $email, $city) = $line;

                $client = new Client();
                $client->setFullName($fullName);
                $client->setEmail($email);
                $client->setCity($city);

                $clientsData[] = $client;

                if (0 === $processedLines % $batchSize) {
                    $this->clientsRepository->insertClientsBatch($clientsData);
                    $clientsData = [];
                }
            }

            $progress = ($processedLines / $totalLines) * 100;
            $progress = floor($progress);

            if (!isset($lastProgress) || $progress > $lastProgress) {
                $lastProgress = $progress;

                $update = new Update(
                    $topic,
                    json_encode(['progress' => $progress])
                );

                $this->hub->publish($update);
            }
        }

        $this->clientsRepository->insertClientsBatch($clientsData);

        $this->logger->info('Zakończono przetwarzanie '.$processedLines.' linii.');
        $this->logger->info('Błędnych linii: '.count($invalidRows));

        $update = new Update(
            $topic,
            json_encode([
                'progress' => 100,
                'totalLines' => $processedLines,
                'invalidRows' => count($invalidRows),
                'errorRows' => $invalidRows,
            ])
        );
        $this->hub->publish($update);
    }
}
