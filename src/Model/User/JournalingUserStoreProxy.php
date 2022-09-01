<?php

declare(strict_types=1);

namespace WisebitTest\WisebitTest\Model\User;

use PDO;
use TypeError;
use WisebitTest\WisebitTest\Model\EntityInterface;
use WisebitTest\WisebitTest\Model\PersistInterface;

// Можно обобщить для использования на разных моделях
class JournalingUserStoreProxy implements PersistInterface
{
    private const LOG_TABLE = "user_logs";

    public function __construct(
        private PDO $connection,
        private PersistInterface $store
    ) {
    }

    public function persist(EntityInterface $user): void
    {
        if (!$user instanceof User) {
            throw new TypeError("\$user not expected type: {${$user::class}}");
        }

        if (is_null($user->getId())) {
            $this->store->persist($user);
            return;
        }

        $this->connection->beginTransaction();

        $selectQuery = $this->connection->prepare(
            "SELECT * FROM {${UserStore::TABLE}} WHERE id=:id"
        );
        $selectQuery->execute(["id" => $id]);
        $currentData = $selectQuery->fetch(PDO::FETCH_ASSOC);
        $updatedData = $user->toDB();

        $this->store->persist($user);

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
            "INSERT INTO {${self::LOG_TABLE}} (column, old_value, new_value, created) VALUES "
            . $valuesPlaceholders
        );

        $params = array_map(
            fn($column) => [$column, $currentData[$column], $updatedData[$column]],
            $columnDiff,
        );

        $query->execute($params);

        $this->connection->commit();
    }
}
