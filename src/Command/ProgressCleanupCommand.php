<?php

namespace App\Command;

use App\Repository\UserRessourceProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Supprime les entrées UserRessourceProgress dont le statut est "completed"
 * et dont la date de complétion est antérieure à X jours.
 *
 * Usage : php bin/console app:progress:cleanup
 *         php bin/console app:progress:cleanup --days=30
 * Cron suggéré : 0 3 * * *  (chaque nuit à 3h)
 */
#[AsCommand(
    name: 'app:progress:cleanup',
    description: 'Supprime les entrées de progression complétées depuis plus de X jours',
)]
class ProgressCleanupCommand extends Command
{
    public function __construct(
        private readonly UserRessourceProgressRepository $progressRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'days',
            'd',
            InputOption::VALUE_REQUIRED,
            'Nombre de jours de rétention après complétion (défaut : paramètre app.progress.purge_delay_days)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) ($input->getOption('days') ?? $this->params->get('app.progress.purge_delay_days'));

        $threshold = new \DateTimeImmutable(sprintf('-%d days', $days));
        $entries = $this->progressRepository->findCompletedBefore($threshold);

        if (empty($entries)) {
            $io->success(sprintf('Aucune entrée complétée depuis plus de %d jour(s) à supprimer.', $days));
            return Command::SUCCESS;
        }

        foreach ($entries as $entry) {
            $this->entityManager->remove($entry);
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d entrée(s) supprimée(s) (complétées depuis plus de %d jour(s)).', count($entries), $days));

        return Command::SUCCESS;
    }
}
