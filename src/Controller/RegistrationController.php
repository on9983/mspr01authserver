<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Error;
use ErrorException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        SendMailService $sendMailService
    ): Response {
        $data = json_decode($request->getContent(), true);


        try {
            $userEmail = $data["username"];
            $userPw = $data["password"];

            $findUserByEmail = $userRepository->findOneByEmail($userEmail);
            if ($findUserByEmail === null) {

                $user = new User();
                $user->setEmail($userEmail);
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $userPw
                    )
                );

                // $entityManager->persist($user);
                // $entityManager->flush();

                // $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                //     (new TemplatedEmail())
                //         ->from(new Address('mailerbot@arosagemsprtest.fr', 'Mailer Verif Bot'))
                //         ->to($user->getEmail())
                //         ->subject('Please Confirm your Email')
                //         ->htmlTemplate('registration/confirmation_email.html.twig')
                // );

                try {
                    $sendMailService->send(
                        'onbot.noreply@gmail.com',
                        "nicolas.ourdouille@outlook.fr", //$user->getEmail(),
                        'Activation de votre compte',
                        'register',
                        compact('user')
                    );
                }catch(\Exception $ex) {
                    return new JsonResponse([
                        'error' => $ex->getMessage()
                    ]);
                }

                return new JsonResponse([
                    'traité' => true
                ]);
            } else {
                return new JsonResponse([
                    'message' => "Compte déja existant."
                ]);
            }

        } catch (error) {
            return new JsonResponse([
                'error' => 'error'
            ]);
        }
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
            return new JsonResponse([
                'verified' => true
            ]);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
            return new JsonResponse([
                'error' => 'error'
            ]);
        }

    }



    // #[Route('/registerJson', name: 'app_registerJson')]
    // public function registerJson(Request $request, UserPasswordHasherInterface $userPasswordHasherInterface, UserRepository $userRepository): Response
    // {
    //     $userNew = new User;
    //     $rep = $request->getContent();
    //     $data = json_decode($rep, true);
    //     $userNew->setEmail($data["username"]);
    //     $userNew->setPassword(
    //         $userPasswordHasherInterface->hashPassword(
    //             $userNew,
    //             $data["pwd"]
    //         )
    //     );

    //     $userRepository->save($userNew, true);

    //     return $this->json([
    //         'token' => "G&GGHYJ&58",
    //         'message' => 'User added',
    //         'username' => $userNew->getUserIdentifier(),
    //         'roles' => $userNew->getRoles()
    //     ]);


    // }






}
