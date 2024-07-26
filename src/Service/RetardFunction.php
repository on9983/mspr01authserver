<?php
namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Cocur\BackgroundProcess\BackgroundProcess;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class RetardFunction
{
    public function __construct(
        private UserRepository $userRepository,
        private KernelInterface $appKernel
    ) {
        $this->userRepository = $userRepository;
        $this->appKernel = $appKernel;
    }

    public function DeleteNonValidatedUser(string $email)
    {
        sleep(900);
        $user = $this->userRepository->findOneByEmail($email);
        if ($user) {
            if (!$user->isVerified()) {
                $this->userRepository->remove($user, true);
            }
        }
    }

    public function RunDeleteNonValideUser(string $email)
    {
        //shell_exec('php bin/console app:delete-invalide-user ' . $email);

        //$process = new Process(['\usr\bin\php \var\www\html\bin\console app:delete-invalide-user '.$email]);
        $process = new Process(['php', 'bin/console', 'app:delete-invalide-user', $email]);
        // set the working directory to the root of the project
        $process->setWorkingDirectory(getcwd() . "/..//");
        $process->setOptions(['create_new_console' => true]);

        //FOR NON ASYNCRO
        //$process->run();

        //FOR ASYNCRO
        $process->start();

        //$process->wait();

    }

}