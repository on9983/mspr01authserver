<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\RetardFunction;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:delete-invalide-user',
    description: 'Add a short description for your command',
)]
class DeleteInvalideUserCommand extends Command
{
    public function __construct(
        private RetardFunction $retardFunction,
    ) {
        parent::__construct();
        $this->retardFunction = $retardFunction;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Argument description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $email = $input->getArgument('email');
        $this->retardFunction->DeleteNonValidatedUser($email);

        return Command::SUCCESS;
    }
}
