<?php

namespace App\Command;

use App\Repository\EvalSlotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-expired-slots',
    description: 'Clean up expired evaluation slots',
)]
class CleanExpiredSlotsCommand extends Command
{
    public function __construct(
        private EvalSlotRepository $evalSlotRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $deletedCount = $this->evalSlotRepository->deleteExpiredSlots();
        $this->entityManager->flush();

        if ($deletedCount > 0) {
            $io->success("Deleted {$deletedCount} expired evaluation slots.");
        } else {
            $io->info('No expired evaluation slots found.');
        }

        return Command::SUCCESS;
    }
}
