<?php

declare(strict_types=1);

namespace WisebitTest\WisebitTest\Model;

interface EntityInterface
{
    public function setId(int $id): self;
    public function getId(): ?int;
}
