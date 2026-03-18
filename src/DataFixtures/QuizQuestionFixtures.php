<?php

namespace App\DataFixtures;

use App\Entity\Activity;
use App\Entity\QuizQuestion;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class QuizQuestionFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [ActivityFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $data = Yaml::parseFile(__DIR__ . '/../../config/fixtures/quiz_question.yaml');

        foreach ($data['quiz_questions'] as $item) {
            /** @var Activity $activity */
            $activity = $this->getReference('activity_' . $item['activity'], Activity::class);

            $question = new QuizQuestion();
            $question->setActivity($activity);
            $question->setQuestion($item['question']);
            $question->setPropositionA($item['propositionA']);
            $question->setPropositionB($item['propositionB']);
            $question->setPropositionC($item['propositionC']);
            $question->setCorrectAnswer($item['correctAnswer']);

            $manager->persist($question);
        }

        $manager->flush();
    }
}