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
        // $this->assertSame('test', $kernel->getEnvironment());
        // $routerService = static::getContainer()->get('router');
        // $myCustomService = static::getContainer()->get(CustomService::class);
    }
    public function testEmail(): void
    {
        $kernel = self::bootKernel();

        $container = static::getContainer();
        $user = $this->getEntity();
        $user->setEmail("aa@hotmail.fr");

        $errors = $container->get("validator")->validate($user);
        $this->assertCount(0, $errors);
        // $this->assertSame('test', $kernel->getEnvironment());
        // $routerService = static::getContainer()->get('router');
        // $myCustomService = static::getContainer()->get(CustomService::class);
    }
    public function testFullName(): void
    {
        $kernel = self::bootKernel();

        $container = static::getContainer();
        $user = $this->getEntity();
        $user->setFullname("aa");

        $errors = $container->get("validator")->validate($user);
        $this->assertCount(0, $errors);
        // $this->assertSame('test', $kernel->getEnvironment());
        // $routerService = static::getContainer()->get('router');
        // $myCustomService = static::getContainer()->get(CustomService::class);
    }
    public function testPassword(): void
    {
        $kernel = self::bootKernel();

        $container = static::getContainer();
        $user = $this->getEntity();
        $user->setPassword("aaaaaaaaa");

        $errors = $container->get("validator")->validate($user);
        $this->assertCount(0, $errors);
        // $this->assertSame('test', $kernel->getEnvironment());
        // $routerService = static::getContainer()->get('router');
        // $myCustomService = static::getContainer()->get(CustomService::class);
    }
}
