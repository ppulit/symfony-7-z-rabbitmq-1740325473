<?php

namespace App\Service;

use App\Entity\Client;
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
    ) {
    }

    public function processFile(string $filePath, string $fileName): void
    {
        $this->logger->info('Rozpoczęto przetwarzanie pliku: '.$filePath);

        $topic = 'progress_'.md5($fileName);

        $totalLines = $this->csvFileReader->countLinesInFile($filePath);
        $processedLines = 0;
        $invalidRows = [];
        $batchSize = 10000;

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

                $this->entityManager->persist($client);

                if (0 === $processedLines % $batchSize) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }

                $this->logger->info('Przetworzono wiersz '.$processedLines.': '.json_encode($line));
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

        $this->entityManager->flush();
        $this->entityManager->clear();

        $update = new Update(
            $topic,
            json_encode([
                'progress' => 100,
                'totalLines' => $totalLines,
                'invalidRows' => count($invalidRows),
                'errorRows' => $invalidRows,
            ])
        );
        $this->hub->publish($update);
    }
}
