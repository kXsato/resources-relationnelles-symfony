<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\User;
use App\Entity\UserRessourceProgress;
use App\Repository\ArticleRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class UserRessourceProgressFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private ArticleRepository $articleRepository) {}

    public function getDependencies(): array
    {
        return [UserFixtures::class, ArticleFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $data = Yaml::parseFile(__DIR__ . '/../../config/fixtures/progress.yaml');

        foreach ($data['progress'] as $item) {
            $article = $this->articleRepository->findOneBy(['slug' => $item['article']]);

            if (!$article) {
                continue;
            }

            /** @var User $user */
            $user = $this->getReference('user_' . $item['user'], User::class);

            $progress = new UserRessourceProgress();
            $progress->setUserRessources($user);
            $progress->setResource($article);
            $progress->setStatus($item['status']);
            $progress->setReadPercentage($item['readPercentage']);

            if (!empty($item['completeAt'])) {
                $progress->setCompleteAt(new \DateTime($item['completeAt']));
            }

            $manager->persist($progress);
        }

        $manager->flush();
    }
}
