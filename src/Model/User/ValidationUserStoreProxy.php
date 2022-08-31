<?php

declare(strict_types=1);

namespace WisebitTest\WisebitTest\Model\User;

use DateTimeImmutable;
use Throwable;
use WisebitTest\WisebitTest\Model\EntityInterface;
use WisebitTest\WisebitTest\Model\PersistInterface;
use WisebitTest\WisebitTest\Model\ValidationException;

class ValidationUserStoreProxy implements PersistInterface
{
    private const BANNED_WORDS = ["badword"];
    private const UNTRUST_EMAIL_DOMAINS = ["mail.sru"];
    private const NAME_COLUMN = "name";
    private const EMAIL_COLUMN = "email";

    public function __construct(
        private UserStore $store,
        private UserQuery $query,
    ) {
    }

    public function persist(EntityInterface $user): void
    {
        if (!$user instanceof User) {
            throw new \Exception("\$user not expected type: {${$user::class}}");
        }

        $this->validateName($user->getName());
        $this->validateEmail($user->getEmail());
        $this->validateDeleted($user->getDeleted(), $user->getCreated());

        $this->validateNameIsUnique($user->getName());
        $this->validateEmailIsUnique($user->getEmail());

        try {
            $this->store->persist($user);
        } catch (Throwable $ex) {
            throw $ex;
        }
    }

    private function validateName(string $name): void
    {
        if (preg_match("/[^a-z0-9]/i", $name) !== 0) {
            throw new ValidationException("name has unacceptable symbol");
        }

        if (strlen($name) < 8) {
            throw new ValidationException("name length name must be more or equal 8");
        }

        $lowerName = strtolower($name);
        foreach (self::BANNED_WORDS as $bannedWord) {
            if (str_contains($lowerName, $bannedWord)) {
                throw new ValidationException("name has unacceptable word");
            }
        }
    }

    private function validateEmail(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException("email is not valid");
        }

        $domain = strtolower(preg_replace("/.+@/", "", $email));
        if (in_array($domain, self::UNTRUST_EMAIL_DOMAINS)) {
            throw new ValidationException("email on untrusted domain");
        }
    }

    private function validateDeleted(
        ?DateTimeImmutable $deleted,
        DateTimeImmutable $created
    ): void {
        if (is_null($deleted)) {
            return;
        }

        if ($deleted < $created) {
            throw new ValidationException("deleted must be more than created");
        }
    }

    private function validateNameIsUnique(string $name): void
    {
        if ($this->query->isExistsByColumn(self::NAME_COLUMN, $name)) {
            throw new ValidationException("name is not unique");
        }
    }

    private function validateEmailIsUnique(string $email): void
    {
        if ($this->query->isExistsByColumn(self::EMAIL_COLUMN, $email)) {
            throw new ValidationException("email is not unique");
        }
    }
}
