<?php

namespace App\Test\Domain\User;

use App\Domain\Exceptions\ValidationException;
use App\Domain\User\User;
use App\Domain\User\UserService;
use App\Domain\User\UserValidation;
use App\Domain\Utility\ArrayReader;
use App\Domain\Validation\ValidationResult;
use App\Infrastructure\User\UserRepository;
use App\Test\UnitTestUtil;
use Cake\Datasource\RepositoryInterface;
use PHPUnit\Framework\TestCase;
use Slim\Logger;

class UserServiceTest extends TestCase
{
    use UnitTestUtil;

    /**
     * Test function findAllUsers from UserService
     *
     * @dataProvider \App\Test\Domain\User\UserProvider::oneSetOfMultipleUsersProvider()
     * @param array $users
     */
    public function testFindAllUsers(array $users)
    {
        // Mock the required repository and configure relevant method return value
        $this->mock(UserRepository::class)
            ->method('findAllUsers')
            ->willReturn($users);

        // Instantiate UserService where the autowire function used the previously defined custom mock
        $service = $this->container->get(UserService::class);

        $this->assertEquals($users, $service->findAllUsers());
    }

    /**
     * @dataProvider \App\Test\Domain\User\UserProvider::oneUserProvider()
     * @param array $user
     */
    public function testFindUser(array $user)
    {
        // Mock the required repository and configure relevant method return value
        $this->mock(UserRepository::class)
            ->method('findUserById')
            ->willReturn($user);

        // Instantiate UserService where the autowire function used the previously defined custom mock
        $service = $this->container->get(UserService::class);

        $this->assertEquals($user, $service->findUser($user['id']));
    }

    /**
     * @dataProvider \App\Test\Domain\User\UserProvider::oneUserProvider()
     * @param array $user
     */
    public function testFindUserByEmail(array $user)
    {
        // Mock the required repository and configure relevant method return value
        $this->mock(UserRepository::class)
            ->method('findUserByEmail')
            ->willReturn($user);

        // Instantiate UserService where the autowire function used the previously defined custom mock
        $service = $this->container->get(UserService::class);

        $this->assertEquals($user, $service->findUserByEmail($user['email']));
    }

    /**
     * @dataProvider \App\Test\Domain\User\UserProvider::oneUserProvider()
     * @param array $validUser
     */
    public function testCreateUser(array $validUser)
    {
        // Mock the required repository and configure relevant method return value
        $this->mock(UserRepository::class)
            ->method('insertUser')
            ->willReturn((string)$validUser['id']);

        // Instantiate UserService where the autowire function used the previously defined custom mock
        /** @var UserService $service */
        $service = $this->container->get(UserService::class);

        // Create an user object
        $userObj = new User(new ArrayReader($validUser));

        // Return type of insertUser is string
        $this->assertEquals($validUser['id'], $service->createUser($userObj));
    }

    /**
     * Test that no user is created when values are invalid
     * validateUserRegistration() will be tested separately but
     * here we ensure that this validation is going on in createUser
     * but without specific error analysis just that it didn't create it
     * The method is called with each value of the provider
     *
     * @dataProvider \App\Test\Domain\User\UserProvider::invalidUsersProvider()
     * @param array $invalidUser
     */
    public function testCreateUserWithInvalid(array $invalidUser)
    {
        // Mock UserRepository because it is used by the validation logic
        $this->mock(UserRepository::class)
            ->method('findUserByEmail')
            ->willReturn($invalidUser);

        /** @var UserService $service */
        $service = $this->container->get(UserService::class);

        $this->expectException(ValidationException::class);

        $service->createUser(new User(new ArrayReader($invalidUser)));
    }

//    public function testUpdateUser()
//    {
//
//    }

//    public function testDeleteUser()
//    {
//    }


}