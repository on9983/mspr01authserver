<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\JWTService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Error;
use ErrorException;
use phpDocumentor\Reflection\Types\Void_;
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
        SendMailService $sendMailService,
        JWTService $jwtService
    ): Response {
        try {
            $data = json_decode($request->getContent(), true);


        
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
                
                
                // Uid Generation

                $users = $userRepository->findAll();
                restart:
                $user->setUid(bin2hex(random_bytes(8)."_".random_bytes(8)));
                $a=true;
                foreach($users as $user_i) {
                    if($user_i->getUid() === $user->getUid()){
                        $a=false;
                        break;
                    }
                }
                if($a==false){goto restart;}

                $user->setActive(true);
                

                $entityManager->persist($user);
                $entityManager->flush();

                $token = $this->makeToken($jwtService,$user);

                try {
                    $sendMailService->send(
                        'mail-checker.onbot-noreply@gmail.com',
                        $userEmail,
                        'Activation de votre compte',
                        'register',
                        compact('user', 'token')
                    );
                    return new JsonResponse([
                        'traited' => true,
                        'message' => "Email de vérification envoyé."
                    ]);
                } catch (\Exception $ex) {
                    return new JsonResponse([
                        'error' => $ex->getMessage()
                    ]);
                }
            } else {
                return new JsonResponse([
                    'message' => "Compte déja existant."
                ]);
            }

        } catch (\Exception $ex) {
            return new JsonResponse([
                'error' => $ex->getMessage()
            ]);
        }
    }


    #[Route('register/verif/{token}', name: 'verify_user')]
    public function verifyUser($token, JWTService $jwt, UserRepository $userRepository): Response
    {
        $token = str_replace(['='], ['.'], $token);
        if ($jwt->isValid($token) && !$jwt->isExpired($token) && !$jwt->checkSignature($token, $this->getParameter('app.jwtsecret'))) {
            $payload = $jwt->getPayload($token);
            $user = $userRepository->findOneByEmail($payload['user_email']);
            if ($user){ 
                if (!$user->isVerified()) {
                    $user->setIsVerified(true);
                    $userRepository->save($user, true);
                    return $this->redirectToRoute('app_verif_success');
                }
                else 
                {
                    return $this->redirectToRoute("app_verif_echec");
                }
            }
            return $this->redirectToRoute("app_verif_echec");
        }
        return $this->redirectToRoute("app_verif_echec");
    }

    #[Route("/register/test/verifSuccess", name:"app_verif_success")]
    public function appVerifSuccess(): Response
    {
        return $this->render("message/messageSuccess.html.twig");
    }

    #[Route("/register/test/verifEchec", name:"app_verif_echec")]
    public function appVerifEchec(): Response
    {
        return $this->render("message/messageEchec.html.twig");
    }


    #[Route('/register/renvoiverif', name: 'resend_verif')]
    public function resendVerif(Request $request, JWTService $jwtService, SendMailService $sendMailService, UserRepository $userRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $userEmail = $data["username"];

            $findUserByEmail = $userRepository->findOneByEmail($userEmail);

            $this->makeToken($jwtService,$findUserByEmail);
            try {
                $sendMailService->send(
                    'mail-checker.onbot-noreply@gmail.com',
                    $userEmail,
                    'Activation de votre compte',
                    'register',
                    compact('user', 'token')
                );
                return new JsonResponse([
                    'traited' => true,
                    'message' => "Email de vérification envoyé."
                ]);
            } catch (\Exception $ex) {
                return new JsonResponse([
                    'error' => $ex->getMessage()
                ]);
            }


            if ($findUserByEmail->IsVerified()) {
                return new JsonResponse([
                    'message' => "Email déja validé."
                ]);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'error'
            ]);
        }
    }


    private function makeToken(
        JWTService $jwtService,
        User $user
    ):String
    {
        $header = [
            "typ" => "JWT",
            "alg" => 'HS256'
        ];

        $payload = [
            'user_email' => $user->getEmail(),
        ];

        $token = $jwtService->generate($header, $payload, $this->getParameter('app.jwtsecret'));

        return $token;
    }




    // #[Route('/verify/email', name: 'app_verify_email')]
    // public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    // {
    //     $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

    //     // validate email confirmation link, sets User::isVerified=true and persists
    //     try {
    //         $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
    //         return new JsonResponse([
    //             'verified' => true
    //         ]);
    //     } catch (VerifyEmailExceptionInterface $exception) {
    //         $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
    //         return new JsonResponse([
    //             'error' => 'error'
    //         ]);
    //     }
    // }





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
