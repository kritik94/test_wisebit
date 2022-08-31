<?php

declare(strict_types=1);

namespace WisebitTest\WisebitTest\Model\User;

class UserQuery
{
    private const TABLE = "user";
    private const EXISTS_BY_COLUMNS = ["name","email"];

    public function __construct(
        private \PDO $connection
    ) {
    }

    public function getById(int $id): ?User
    {
        $query = $this->connection->prepare(
            "SELECT * FROM {${self::TABLE}} WHERE id=:id"
        );

        $query->execute(["id" => $id]);

        $obj = $query->fetchObject(User::class);
        if ($obj === false) {
            return null;
        }
        return $obj;
    }

    public function isExistsByColumn(string $column, string $value): bool
    {
        if (!in_array($column, self::EXISTS_BY_COLUMNS)) {
            throw new \ValueError("Can not check exists by column '$column'");
        }

        $query = $this->connection->prepare(
            "SELECT id FROM {${self::TABLE}} WHERE $column=:value"
        );
        $query->execute(["value" => $value]);
        $result = $query->fetchAll();

        return !empty($result);
    }
}
