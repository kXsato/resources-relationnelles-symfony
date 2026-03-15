<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Repository\ActivityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/quiz')]
class QuizController extends AbstractController
{
    #[Route('/questions/{id}', name: 'quiz_questions', methods: ['GET'])]
    public function questions(Activity $activity): JsonResponse
    {
        $questions = $activity->getQuestions()->map(fn($q) => [
            'id'          => $q->getId(),
            'question'    => $q->getQuestion(),
            'propositionA' => $q->getPropositionA(),
            'propositionB' => $q->getPropositionB(),
            'propositionC' => $q->getPropositionC(),
        ])->toArray();

        return $this->json($questions);
    }

    #[Route('/answer/{id}', name: 'quiz_answer', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function answer(Request $request, Activity $activity): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $answers = $data['answers'] ?? [];

        $score = 0;
        $total = count($activity->getQuestions());

        foreach ($activity->getQuestions() as $question) {
            $userAnswer = $answers[$question->getId()] ?? null;
            if ((int)$userAnswer === $question->getCorrectAnswer()) {
                $score++;
            }
        }

        return $this->json([
            'score' => $score,
            'total' => $total,
        ]);
    }
}