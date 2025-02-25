<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClientsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function insertClientsBatch(array $clientsData): void
    {
        if (empty($clientsData)) {
            return;
        }

        $conn = $this->getEntityManager()->getConnection();
        $sql = 'INSERT INTO `client` (`full_name`, `email`, `city`) VALUES ';

        $placeholders = [];
        $params = [];
        $index = 0;

        foreach ($clientsData as $client) {
            $placeholders[] = "(:full_name{$index}, :email{$index}, :city{$index})";

            $params["full_name{$index}"] = $client->getFullName();
            $params["email{$index}"] = $client->getEmail();
            $params["city{$index}"] = $client->getCity();

            ++$index;
        }

        $sql .= implode(', ', $placeholders);

        $stmt = $conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->executeQuery();
    }
}
