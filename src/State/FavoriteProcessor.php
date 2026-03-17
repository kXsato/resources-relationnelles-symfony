<?php

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Favorite;
use App\Repository\ArticleRepository;
use App\Repository\FavoriteRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FavoriteProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: PersistProcessor::class)]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private ArticleRepository $articleRepository,
        private FavoriteRepository $favoriteRepository,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Favorite) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $user = $this->security->getUser();
        $articleId = $data->getArticleId();

        if (!$articleId) {
            throw new BadRequestHttpException('Le champ articleId est requis.');
        }

        $article = $this->articleRepository->find($articleId);
        if (!$article) {
            throw new NotFoundHttpException("Article #{$articleId} introuvable.");
        }

        $existing = $this->favoriteRepository->findOneBy(['user' => $user, 'article' => $article]);
        if ($existing) {
            throw new ConflictHttpException('Cet article est déjà dans vos favoris.');
        }

        $data->setArticle($article);
        $data->setUser($user);
        $data->setCreatedAt(new \DateTimeImmutable());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
