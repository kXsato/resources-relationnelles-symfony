<?php

namespace App\Twig\Components;

use App\Controller\Dashboard\Moderator\ModeratorArticleCrudController;
use App\Controller\Dashboard\Moderator\ModeratorDashboardController;
use App\Entity\Article;
use App\Enum\ResourceStatus;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class RejectionModal extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $articleId = 0;

    #[LiveProp]
    public bool $isOpen = false;

    #[LiveProp(writable: true)]
    public string $rejectionReason = '';

    #[LiveProp]
    public bool $hasError = false;

    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
    ) {}

    #[LiveAction]
    public function open(): void
    {
        $this->isOpen = true;
        $this->hasError = false;
        $this->rejectionReason = '';
    }

    #[LiveAction]
    public function close(): void
    {
        $this->isOpen = false;
        $this->hasError = false;
        $this->rejectionReason = '';
    }

    #[LiveAction]
    public function confirm(EntityManagerInterface $em): ?Response
    {
        if (!trim($this->rejectionReason)) {
            $this->hasError = true;

            return null;
        }

        $article = $em->find(Article::class, $this->articleId);
        if (!$article) {
            throw $this->createNotFoundException('Article introuvable.');
        }

        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        if ($article->getAuthor()?->getId() === $this->getUser()?->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas rejeter votre propre article.');
        }

        $article->setStatus(ResourceStatus::REJECTED->value);
        $article->setRejectionReason(trim($this->rejectionReason));
        $em->flush();

        return $this->redirect(
            $this->adminUrlGenerator
                ->setDashboard(ModeratorDashboardController::class)
                ->setController(ModeratorArticleCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }
}
