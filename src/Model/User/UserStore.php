<?php

declare(strict_types=1);

namespace WisebitTest\WisebitTest\Model\User;

use WisebitTest\WisebitTest\Model\EntityInterface;
use WisebitTest\WisebitTest\Model\PersistInterface;

class UserStore implements PersistInterface
{
    private const TABLE = "user";

    public function __construct(
        private \PDO $connection
    ) {
    }

    public function persist(EntityInterface $user): void
    {
        if (!$user instanceof User) {
            throw new \Exception("\$user not expected type: {${$user::class}}");
        }

        if (is_null($user->getId())) {
            $query = $this->connection->prepare(
                "INSERT INTO {${self::TABLE}} 
                (name, email, notes, created)
                VALUE (:name, :email, :notes, :created)
                "
            );

            $query->execute([
                "name" => $user->getName(),
                "email" => $user->getEmail(),
                "notes" => $user->getNotes(),
                "created" => $user->getCreated(),
            ]);

            $id = $this->connection->lastInsertId();
            $user->setId((int) $id);

            return;
        }

        // select user for calc diff and log

        $query = $this->connection->prepare(
            "UPDATE {${self::TABLE}}
            VALUE (:name, :email, :notes, :deleted)
            WHERE id = :id
            "
        );

        $query->execute([
            "name" => $user->getName(),
            "email" => $user->getEmail(),
            "notes" => $user->getNotes(),
            "deleted" => $user->getDeleted(),
        ]);
    }
}
