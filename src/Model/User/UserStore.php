<?php

declare(strict_types=1);

namespace WisebitTest\WisebitTest\Model\User;

use PDO;
use TypeError;
use WisebitTest\WisebitTest\Model\EntityInterface;
use WisebitTest\WisebitTest\Model\PersistInterface;

class UserStore implements PersistInterface
{
    public const TABLE = "user";

    public function __construct(
        private PDO $connection
    ) {
    }

    public function persist(EntityInterface $user): void
    {
        if (!$user instanceof User) {
            throw new TypeError("\$user not expected type: {${$user::class}}");
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

        $this->connection->beginTransaction();

        $selectQuery = $this->connection->prepare(
            "SELECT * FROM users WHERE id=:id"
        );
        $selectQuery->execute(["id" => $id]);
        $currentData = $selectQuery->fetch(PDO::FETCH_ASSOC);

        $query = $this->connection->prepare(
            "UPDATE {${self::TABLE}}
            VALUE (:name, :email, :notes, :deleted)
            WHERE id = :id
            "
        );

        $updatedData = $user->toDB();
        $query->execute($updatedData);

        $this->logUpdate($currentData, $updatedData);

        $this->connection->commit();
    }

    private function logUpdate(array $currentData, array $updatedData)
    {
        $diff = array_diff($currentData, $updatedData);

        if (empty($diff)) {
            return;
        }

        $columnDiff = array_keys($diff);
        $valuesPlaceholders = implode(
            ",",
            array_fill(0, count($columnDiff), "(?, ?, ?, NOW())")
        );

        $query = $this->connection->prepare(
            "INSERT INTO user_logs (column, old_value, new_value, created) VALUES "
            . $valuesPlaceholders
        );

        $params = array_map(
            fn($column) => [$column, $currentData[$column], $updatedData[$column]],
            $columnDiff,
        );

        $query->execute($params);
    }
}
