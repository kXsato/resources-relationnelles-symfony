<?php

namespace App\DataFixtures;

use App\Entity\Activity;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class ActivityFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [CategoryFixtures::class, UserFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $data = Yaml::parseFile(__DIR__ . '/../../config/fixtures/activity.yaml');

        foreach ($data['activities'] as $activityData) {
            $activity = new Activity();
            $activity->setTitle($activityData['title']);
            $activity->setSlug($activityData['slug']);
            $activity->setDescription($activityData['description']);
            $activity->setStatus($activityData['status'] ?? 'published');
            $activity->setContent($activityData['content'] ?? null);
            $activity->setGameType($activityData['gameType'] ?? null);

            if (!empty($activityData['startDate'])) {
                $activity->setStartDate(new \DateTimeImmutable($activityData['startDate']));
            }

            if (!empty($activityData['endDate'])) {
                $activity->setEndDate(new \DateTimeImmutable($activityData['endDate']));
            }

            foreach ($activityData['categories'] as $categoryKey) {
                $activity->addCategory($this->getReference('category_' . $categoryKey, Category::class));
            }

            if (!empty($activityData['author'])) {
                /** @var User $user */
                $user = $this->getReference('user_' . $activityData['author'], User::class);
                $activity->setAuthor($user);
            }

            if (!empty($activityData['createdAt'])) {
                $activity->setCreatedAt(new \DateTimeImmutable($activityData['createdAt']));
            }

            $manager->persist($activity);
            $this->addReference('activity_' . $activityData['slug'], $activity);
        }

        $manager->flush();
    }
}