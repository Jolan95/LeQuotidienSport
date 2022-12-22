<?php

namespace App\Tests\Unit;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\User;

class UserTest extends KernelTestCase
{
    public function getEntity(){
        return (new User())->setFullname("Jolan Aubry")
        ->setRoles(["ROLE_USER"])
        ->setPassword("mainikjdj")
        ->setEmail("mainiki@hotmail.fr");
    }

    public function testUserEntity(): void
    {
        $kernel = self::bootKernel();

        $container = static::getContainer();
        $user = $this->getEntity();

        $errors = $container->get("validator")->validate($user);
        $this->assertCount(0, $errors);

    }
    public function testEmail(): void
    {
        $kernel = self::bootKernel();

        $container = static::getContainer();
        $user = $this->getEntity();
        $user->setEmail("aaaaaa@hotmail.fr");

        $errors = $container->get("validator")->validate($user);
        $this->assertCount(0, $errors);
    }
    public function testFullName(): void
    {
        $kernel = self::bootKernel();

        $container = static::getContainer();
        $user = $this->getEntity();
        $user->setFullname("Jolan Aubry");

        $errors = $container->get("validator")->validate($user);
        $this->assertCount(0, $errors);

    }
    public function testPassword(): void
    {
        $kernel = self::bootKernel();

        $container = static::getContainer();
        $user = $this->getEntity();
        $user->setPassword("AZER");

        $errors = $container->get("validator")->validate($user);
        $this->assertCount(0, $errors);

    }
}
