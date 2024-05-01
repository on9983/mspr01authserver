<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\DevOnly;
use App\Service\JWTService;
use App\Service\RandomString;
use App\Service\SendMailService;
use DateTimeImmutable;
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

    

    public function __construct(
        private EmailVerifier $emailVerifier,
        private DevOnly $devOnly,
        )
    {
        $this->emailVerifier = $emailVerifier;
        $this->devOnly = $devOnly;
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

                return new JsonResponse([
                    'traited' => true,
                    'message' => "Votre compte a été créé avec success."
                ]);

            } else {
                return new JsonResponse([
                    'message' => "Compte déja existant."
                ]);
            }

        } catch (\Exception $ex) {
            return new JsonResponse([
                'error' => $this->devOnly->displayError($ex->getMessage())
            ]);
        }
    }


    #[Route('register/verif', name: 'verify_user')]
    public function verifyUser(Request $request, JWTService $jwt, UserRepository $userRepository): Response
    {
        $data = json_decode($request->getContent(), true);

        try {
            $userUid = $data["username"];
            $userJeton = $data["jeton"];

            $user = $userRepository->findOneByUid($userUid);
            if ($user) 
            {
                $now = new DateTimeImmutable();
                $jetonVerif = $user->getJeton();
                if($jetonVerif)
                {
                    if($jetonVerif === $userJeton)
                    {
                        if($user->getJetonExpiration() > $now->getTimestamp()) 
                        {
                            $user->setIsVerified(true);
                            $user->setJetonExpiration(null);
                            $user->setJeton(null);
                            $userRepository->save($user, true);

                            return new JsonResponse([
                                'traited' => true,
                                'uid' => $user->getUid(),
                                'message' => "Votre email a été validé avec success."
                            ]);
                        }
                        else
                        {
                            return new JsonResponse([
                                'error' => 'error',
                                'message' => "Le jeton a expiré."
                            ]);
                        }
                    }
                }

                return new JsonResponse([
                    'message' => 'Pas de jeton disponible. Ou alors, le jeton a déja été utilisé avec success.'
                ]);
            }
            return new JsonResponse([
                'error' => 'error',
                'message' => 'Utilisateur non valide. Le compte a peut-etre été supprimé.'
            ]);

        }
        catch (\Exception $ex) {
            return new JsonResponse([
                'error' => 'error'
            ]);
        }
    }

     #[Route('/register/sendverif', name: 'send_verif')]
    public function sendVerif(
        Request $request, 
        JWTService $jwtService, 
        SendMailService $sendMailService, 
        UserRepository $userRepository,
        RandomString $randomString,
    ): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $userUid = $data["username"];

            $user = $userRepository->findOneByUid($userUid);

            #$this->makeToken($jwtService,$findUserByEmail);
            if ($user) 
            {
                if($user->isActive())
                {
                    if ($user->IsVerified()) {
                        return new JsonResponse([
                            'message' => "Email déja validé."
                        ]);
                    }

                    $token = $randomString->generate();
                    $user->setJeton($token);
                    $now = new DateTimeImmutable();
                    $user->setJetonExpiration($now->getTimestamp() + 900);

                    $userRepository->save($user, true);

                    try {
                        $sendMailService->send(
                            'mail-checker.onbot-noreply@gmail.com',
                            $user->getEmail(),
                            'Activation de votre compte',
                            'register',
                            compact('token')
                        );
                        return new JsonResponse([
                            'traited' => true,
                            'message' => "Email de vérification envoyé."
                        ]);
                    } catch (\Exception $ex) {
                        return new JsonResponse([
                            'error' => $this->devOnly->displayError($ex->getMessage())
                        ]);
                    }



                }
                return new JsonResponse([
                    'message' => "Votre compte à été déactivé pour un certain temps."
                ]);
            }
            return new JsonResponse([
                'message' => "Utilisateur non valide. Le compte a peut-etre été supprimé."
            ]);
        } catch (\Exception $ex) {
            return new JsonResponse([
                'error' => $this->devOnly->displayError($ex->getMessage())
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
