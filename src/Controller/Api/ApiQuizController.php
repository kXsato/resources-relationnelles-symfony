<?php

namespace App\Controller\Api;

use App\Entity\Activity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/quiz')]
class ApiQuizController extends AbstractController
{
    #[Route('/questions/{id}', name: 'api_quiz_questions', methods: ['GET'])]
    public function questions(Activity $activity): JsonResponse
    {
        $now = new \DateTimeImmutable();
        if ($activity->getEndDate() && $activity->getEndDate() < $now) {
            return $this->json(['error' => 'expired'], 410);
        }

        $questions = $activity->getQuestions()->map(fn($q) => [
            'id'           => $q->getId(),
            'question'     => $q->getQuestion(),
            'propositionA' => $q->getPropositionA(),
            'propositionB' => $q->getPropositionB(),
            'propositionC' => $q->getPropositionC(),
        ])->toArray();

        return $this->json($questions);
    }

    #[Route('/answer/{id}', name: 'api_quiz_answer', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function answer(Request $request, Activity $activity): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');
        dump($authHeader); // sera visible dans les logs Docker
        $now = new \DateTimeImmutable();
        if ($activity->getEndDate() && $activity->getEndDate() < $now) {
            return $this->json(['error' => 'expired'], 410);
        }

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