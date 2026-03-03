<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class ArticleFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [CategoryFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $data = Yaml::parseFile(__DIR__ . '/../../config/fixtures/article.yaml');

        foreach ($data['articles'] as $articleData) {
            $article = new Article();
            $article->setTitle($articleData['title']);
            $article->setSlug($articleData['slug']);
            $article->setDescription($articleData['description']);
            $article->setStatus($articleData['status']);
            $article->setContent($articleData['content']);

            foreach ($articleData['categories'] as $categoryKey) {
                $article->addCategory($this->getReference('category_' . $categoryKey, Category::class));
            }

            $manager->persist($article);
        }

        $manager->flush();
    }
}
