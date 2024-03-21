<?php

namespace App\Service;
use phpDocumentor\Reflection\Types\Void_;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class SendMailService
{
    private $mailer;
    public function __construct(MailerInterface $mailer){
        $this->mailer = $mailer;
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
}