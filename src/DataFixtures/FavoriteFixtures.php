<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Favorite;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class FavoriteFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [UserFixtures::class, ArticleFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $data = Yaml::parseFile(__DIR__ . '/../../config/fixtures/favorite.yaml');

        foreach ($data['favorites'] as $item) {
            /** @var User $user */
            $user = $this->getReference('user_' . $item['user'], User::class);
            /** @var Article $article */
            $article = $this->getReference('article_' . $item['article'], Article::class);

            $favorite = new Favorite();
            $favorite->setUser($user);
            $favorite->setArticle($article);
            $favorite->setCreatedAt(new \DateTimeImmutable($item['createdAt']));

            $manager->persist($favorite);
        }

        $manager->flush();
    }
}
