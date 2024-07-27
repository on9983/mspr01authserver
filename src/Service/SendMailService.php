<?php

namespace App\Service;
use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use phpDocumentor\Reflection\Types\Void_;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class SendMailService
{
    public function __construct(
        private MailerInterface $mailer,
        private UserRepository $userRepository
        ){
        $this->mailer = $mailer;
        $this->userRepository = $userRepository;
    }

    public function send(string $from,string $to, string $subject, string $template, array $context = []):Void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($from, 'Mailer Verif Bot'))
            ->to($to)
            ->subject($subject)
            ->htmlTemplate("email/$template.html.twig")
            ->context($context);

        $this->mailer->send($email);
    }

    public function checkNbEnvoie(User $user):bool
    {
        $now = new DateTimeImmutable();
        if($user->getNbDeSignalement() === null){
            $user->setNbDeSignalement(0);
        }
        if ($user->getSignalExpiration()) {
            if ($now->getTimestamp() > $user->getSignalExpiration()) {
                $user->setNbDeSignalement(0);
            }
        }
        if ($user->getNbDeSignalement() < 3) {
            $user->setSignalExpiration($now->getTimestamp() + 1800);
            $user->setNbDeSignalement($user->getNbDeSignalement() + 1);
            $this->userRepository->save($user, true);
            return true;
        }else{
            return false;
        }
    }
}