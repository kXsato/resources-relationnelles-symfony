<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Désactive les comptes inactifs depuis plus de 2 ans (recommandation CNIL/RGPD).
 *
 * La référence d'inactivité est lastLogin si disponible, sinon registrationDate.
 *
 * Usage : php bin/console app:deactivate-inactive-accounts
 * Cron suggéré : 0 2 * * 0  (chaque dimanche à 2h)
 */
#[AsCommand(
    name: 'app:deactivate-inactive-accounts',
    description: 'Désactive les comptes inactifs depuis plus de 2 ans (RGPD/CNIL)',
)]
class DeactivateInactiveAccountsCommand extends Command
{
    private const INACTIVITY_YEARS = 2;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $threshold = new \DateTimeImmutable(sprintf('-%d years', self::INACTIVITY_YEARS));
        $users = $this->userRepository->findInactiveActiveUsers($threshold);

        if (empty($users)) {
            $io->success('Aucun compte inactif trouvé.');
            return Command::SUCCESS;
        }

        foreach ($users as $user) {
            $user->setIsAccountActivated(false);
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d compte(s) désactivé(s) pour inactivité.', count($users)));

        return Command::SUCCESS;
    }
}
