<?php

namespace WisebitTest\WisebitTest\Model\User;

use DateTimeImmutable;
use WisebitTest\WisebitTest\Model\EntityInterface;

class User implements EntityInterface
{
    public function __construct(
        private ?int $id,
        private string $name,
        private string $email,
        private ?string $notes,
        private DateTimeImmutable $created,
        private ?DateTimeImmutable $deleted = null,
    ) {
    }

    public static function create(
        string $name,
        string $email,
        ?string $notes = null,
    ): self {
        return new self(null, $name, $email, $notes, new DateTimeImmutable());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getDeleted(): ?DateTimeImmutable
    {
        return $this->deleted;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function delete($time = new DateTimeImmutable()): self
    {
        $this->deleted = $time;

        return $this;
    }
}
