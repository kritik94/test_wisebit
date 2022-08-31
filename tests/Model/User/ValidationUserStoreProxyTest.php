<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WisebitTest\WisebitTest\Model\PersistInterface;
use WisebitTest\WisebitTest\Model\User\User;
use WisebitTest\WisebitTest\Model\User\UserQuery;
use WisebitTest\WisebitTest\Model\User\UserStore;
use WisebitTest\WisebitTest\Model\User\ValidationUserStoreProxy;
use WisebitTest\WisebitTest\Model\ValidationException;

final class ValidationUserStoreProxyTest extends TestCase
{
    /**
     * @dataProvider validDataProvider
     * @dataProvider invalidDataProvider
     */
    public function testValidation(User $user, ?string $validationMessage)
    {
        $storeStub = $this->createStub(UserStore::class);
        $queryStub = $this->createStub(UserQuery::class);

        $validationStore = new ValidationUserStoreProxy($storeStub, $queryStub);

        $valid = false;
        try {
            $validationStore->persist($user);
            $valid = true;
        } catch (ValidationException $exception) {
            $this->assertEquals($validationMessage, $exception->getMessage());
        } catch (Throwable $exception) {
            throw $exception;
        }

        if ($valid) {
            $this->assertEquals($validationMessage, null);
        }
    }

    public function testUniqueValidation()
    {
        $storeStub = $this->createStub(UserStore::class);
        $queryStub = $this->createStub(UserQuery::class);
        $queryStub->method("isExistsByColumn")->willReturn(true);

        $validationStore = new ValidationUserStoreProxy($storeStub, $queryStub);

        $this->expectErrorMessage("name is not unique");
        $validationStore->persist(User::create("asdfzxcv", "some@mail.com"));
    }

    public function validDataProvider()
    {
        return [
            [User::create("Asdfzxcv", "somebody@mail.com"), null],
            [User::create("XXX1337XXX", "sauron@mail.ru"), null],
            [User::create("PupaAndLupa", "lupa-za-pupu@gmail.com", "category B joke"), null],
        ];
    }

    public function invalidDataProvider()
    {
        return [
            [
                User::create("Asdf", "somebody@mail.com"),
                "name length name must be more or equal 8"
            ],
            [
                User::create("Asdf||||||", "somebody@mail.com"),
                "name has unacceptable symbol",
            ],
            [
                User::create("Asdf00BADWord00", "somebody@mail.com"),
                "name has unacceptable word",
            ],
            [
                User::create("PupaAndLupa", "not email", "category B joke"),
                "email is not valid",
            ],
            [
                User::create("XXX1337XXX", "sauron@mail.sru"),
                "email on untrusted domain",
            ],
            [
                User::create(
                    "Asdfzxcv",
                    "somebody@mail.com",
                    null
                )->delete((new DateTimeImmutable)->modify("-1 day")),
                "deleted must be more than created",
            ],
        ];
    }
}
