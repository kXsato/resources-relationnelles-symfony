<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Yaml\Yaml;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $data = Yaml::parseFile(__DIR__ . '/../../config/fixtures/users.yaml');

        foreach ($data['users'] as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);
            $user->setUserName($userData['userName']);
            $user->setBirthDate(new \DateTime($userData['birthDate']));
            $user->setRegistrationDate(new \DateTime($userData['registrationDate']));
            $user->setPassword($this->hasher->hashPassword($user, $userData['password']));

            $manager->persist($user);
            $this->addReference('user_' . $userData['userName'], $user);
        }

        $manager->flush();
    }
}
