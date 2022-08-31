<?php

declare(strict_types=1);

namespace WisebitTest\WisebitTest\Model;

interface PersistInterface
{
    public function persist(EntityInterface $entity): void;
}
