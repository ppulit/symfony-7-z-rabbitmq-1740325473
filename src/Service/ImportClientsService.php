<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\ImportHistory;
use App\Repository\ClientsRepository;
use App\Validator\CsvFileValidator;
use Doctrine\ORM\EntityManagerInterface;
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
        private EntityManagerInterface $entityManager,
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
        $processedRows = 0;
        $invalidRows = [];
        $errorMessages = [];
        $batchSize = 1000;
        $clientsData = [];

        foreach ($this->csvFileReader->readFile($filePath, true) as $line) {
            ++$processedRows;
            if (!$this->csvFileValidator->validateRow($line)) {
                $invalidRows[$processedRows] = $line;
                $err = 'Niepoprawny wiersz '.$processedRows.': '.json_encode($line);
                $errorMessages[] = $err;
                $this->logger->error($err);
            } else {
                list($id, $fullName, $email, $city) = $line;

                $client = new Client();
                $client->setFullName($fullName);
                $client->setEmail($email);
                $client->setCity($city);

                $clientsData[] = $client;

                if (0 === $processedRows % $batchSize) {
                    $this->clientsRepository->insertClientsBatch($clientsData);
                    $clientsData = [];
                }
            }

            $progress = ($processedRows / $totalLines) * 100;
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

        $this->logger->info('Zakończono przetwarzanie '.$processedRows.' wierszy.');
        $this->logger->info('Błędnych wierszy: '.count($invalidRows));

        $update = new Update(
            $topic,
            json_encode([
                'progress' => 100,
                'totalRows' => $processedRows,
                'successCount' => $processedRows - count($invalidRows),
                'invalidRows' => count($invalidRows),
                'errorRows' => $invalidRows,
            ])
        );
        $this->hub->publish($update);

        $this->entityManager->persist(new ImportHistory($processedRows, count($invalidRows), 'Sukces', json_encode($errorMessages)));
        $this->entityManager->flush();
    }
}
