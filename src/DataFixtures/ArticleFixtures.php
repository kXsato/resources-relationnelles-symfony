<?php

namespace App\DataFixtures;

use App\Entity\Article;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class ArticleFixtures extends Fixture
{
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

            $manager->persist($article);
        }

        $manager->flush();
    }
}
