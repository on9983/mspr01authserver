<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\JWTService;
use App\Service\SendMailService;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{

    #[Route(path: '/jsonLogin', name: 'app_jsonLogin', methods: ['POST'])]
    public function jsonLogin(#[CurrentUser] ?User $user,UserRepository $userRepository)
    {

        $user = $this->getUser();
        $user = $userRepository->findOneByEmail($user->getUserIdentifier());

        if ($user) 
        {
            if($user->isActive())
            {
                return new JsonResponse([
                    'traited' => true,
                    'uid' => $user->getUid(),
                    'message' => "Connecté avec success."
                ]);
            }
            else
            {
                return new JsonResponse([
                    'message' => "Votre compte à été déactivé pour un certain temps."
                ]);
            }
        }

        return $this->json([
            'message' => 'missing credentials',
        ], Response::HTTP_UNAUTHORIZED);


    }

    #[Route(path: '/oubliMdp', name: 'forgottenPass')]
    public function forgottenPassword(
        Request $request,
        SendMailService $sendMailService,
        JWTService $jwtService,
        TokenGeneratorInterface $tokenGeneratorInterface,
        UserRepository $userRepository,
        UserPasswordHasherInterface $userPasswordHasher,
    ): Response {
        $data = json_decode($request->getContent(), true);

        try {
            $userEmail = $data["username"];
            $user = $userRepository->findOneByEmail($userEmail);

            if($user->isActive())
            {
                $token = $tokenGeneratorInterface->generateToken();
                $user->setJeton($token);
                $now = new DateTimeImmutable();
                $user->setJetonExpiration($now->getTimestamp() + 900);

                $userRepository->save($user, true);
                
                try {
                    $sendMailService->send(
                        'mail-checker.onbot-noreply@gmail.com',
                        $userEmail,
                        'Changement de mot de passe de votre compte',
                        'pwd-change',
                        compact('token')
                    );
                    return new JsonResponse([
                        'traited' => true,
                        'message' => "Un email de changement de mot de pass a été envoyé."
                    ]);
                } catch (\Exception $ex) {
                    return new JsonResponse([
                        'error' => 'error'
                    ]);
                }
            }
            else
            {
                return new JsonResponse([
                    'message' => "Votre compte à été déactivé pour un certain temps."
                ]);
            }

        } catch (\Exception $ex) {
            return new JsonResponse([
                'error' => 'error'
            ]);
        }
    }

    #[Route('/mdpchange', name: 'verify_mdpchange')]
    public function verifyMdpChange(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher, 
        UserRepository $userRepository
    ): Response
    {
        $data = json_decode($request->getContent(), true);

        try {
            $userEmail = $data["username"];
            $userJeton = $data["jeton"];
            $userPw = $data["password"];
            $user = $userRepository->findOneByEmail($userEmail);

            $now = new DateTimeImmutable();
            $jetonVerif = $user->getJeton();

            if($jetonVerif)
            {
                if($jetonVerif === $userJeton)
                {
                    if($user->getJetonExpiration() > $now->getTimestamp()) 
                    {
                        $user->setPassword(
                            $userPasswordHasher->hashPassword(
                                $user,
                                $userPw
                            )
                        );
                        $user->setJetonExpiration(null);
                        $user->setJeton(null);
                        $userRepository->save($user, true);

                        return new JsonResponse([
                            'traited' => true,
                            'message' => "Le mot de passe a été modifié avec success."
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
        catch (\Exception $ex) {
            return new JsonResponse([
                'error' => 'error'
            ]);
        }
    }



}
