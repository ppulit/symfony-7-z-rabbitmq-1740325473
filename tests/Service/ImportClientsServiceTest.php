<?php

namespace App\Tests\Service;

use App\Service\ImportClientsService;
use App\Service\CsvFileReader;
use App\Entity\Client;
use App\Validator\CsvFileValidator;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use PHPUnit\Framework\TestCase;

class ImportClientsServiceTest extends TestCase
{
    private $logger;
    private $hub;
    private $entityManager;
    private $csvFileValidator;
    private $csvFileReader;
    private $importClientsService;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->hub = $this->createMock(HubInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->csvFileValidator = $this->createMock(CsvFileValidator::class);
        $this->csvFileReader = $this->createMock(CsvFileReader::class);

        $this->importClientsService = new ImportClientsService(
            $this->logger,
            $this->hub,
            $this->entityManager,
            $this->csvFileValidator,
            $this->csvFileReader
        );
    }

    public function testProcessFileWithValidRows()
    {
        $filePath = 'path/to/valid_file.csv';
        $fileName = 'valid_file.csv';

        // Fikcyjne dane CSV
        $mockedData = [
            ['1', 'John Doe', 'john@example.com', 'City'],
            ['2', 'Jane Smith', 'jane@example.com', 'Town']
        ];

        // Mockowanie metody readCsvFile, aby zwracała nasze dane w formie generatora
        $this->csvFileReader->method('countLinesInFile')->willReturn(count($mockedData));
        $this->csvFileReader->method('readFile')
            ->willReturnCallback(function() use ($mockedData) {
                foreach ($mockedData as $row) {
                    yield $row;
                }
            });

        $this->csvFileValidator->expects($this->exactly(2))
            ->method('validateRow')
            ->willReturn(true);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->isInstanceOf(Client::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->hub->expects($this->exactly(3))
            ->method('publish')
            ->with($this->isInstanceOf(Update::class));

        $this->importClientsService->processFile($filePath, $fileName);
    }

    public function testProcessFileWithInvalidRows()
    {
        $filePath = 'path/to/invalid_file.csv';
        $fileName = 'invalid_file.csv';

        // Fikcyjne dane CSV z błędnymi wierszami
        $mockedData = [
            ['1', 'John Doe', 'john@example.com', 'City'],          // poprawny wiersz
            ['2', 'Jane Smith', 'invalid-email', 'Town'],           // niepoprawny email
            ['3', '', 'jane@example.com', ''],                      // brak imienia i miasta
        ];

        // Mockowanie metody readCsvFile, aby zwracała nasze dane w formie generatora
        $this->csvFileReader->method('countLinesInFile')->willReturn(count($mockedData));
        $this->csvFileReader->method('readFile')
            ->willReturnCallback(function() use ($mockedData) {
                foreach ($mockedData as $row) {
                    yield $row;
                }
            });

        $this->csvFileValidator->expects($this->exactly(3))
            ->method('validateRow')
            ->willReturnOnConsecutiveCalls(true, false, false);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Client::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->hub->expects($this->exactly(4))
        ->method('publish')
            ->with($this->isInstanceOf(Update::class));

        $this->importClientsService->processFile($filePath, $fileName);
    }
}
