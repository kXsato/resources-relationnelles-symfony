<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $data = Yaml::parseFile(__DIR__ . '/../../config/fixtures/category.yaml');

        foreach ($data['users'] as $categoryData) {
            $category = new Category();
            $category->setName($categoryData['name']);
            $manager->persist($category);
        }

        $manager->flush();
    }
}