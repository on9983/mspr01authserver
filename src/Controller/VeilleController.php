<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\DevOnly;
use App\Service\JWTService;
use App\Service\RandomString;
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

class VeilleController extends AbstractController
{

    public function __construct(
        private DevOnly $devOnly,
    ) {
        $this->devOnly = $devOnly;
    }

    #[Route('/veille/nous-contacter', name: 'nousContacterRoute')]
    public function nousContacter(
        Request $request,
        UserRepository $userRepository,
        SendMailService $sendMailService,
    ): Response {
        $data = json_decode($request->getContent(), true);

        try {
            $uid = $data["uid"];

            $user = $userRepository->findOneByUid($uid);
            if ($user) {
                if ($sendMailService->checkNbEnvoie($user)) {
                    if (array_key_exists('autoContact', $data["dataForm"])) {
                        if ($_ENV["APP_ONIPROD"] === "prod") {
                            try {
                                $sendMailService->send(
                                    'mail-checker.onbot-noreply@gmail.com',
                                    "nicolas.ourdouille@outlook.fr",
                                    'Erreur détecté sur GVR',
                                    'autocontact',
                                    [
                                        'qui' => $user->getEmail(),
                                        'page' => $data["dataForm"]['autoContact']['page'],
                                        'action' => $data["dataForm"]['autoContact']['action'],
                                        'erreur' => $data["dataForm"]['autoContact']['erreur'],
                                    ]
                                );
                                return new JsonResponse([
                                    'traited' => true,
                                    'message' => "Email envoyé."
                                ]);
                            } catch (\Exception $ex) {
                                return new JsonResponse([
                                    'critique' => $this->devOnly->displayError($ex->getMessage())
                                ]);
                            }
                        }
                        return new JsonResponse([
                            'error' => 'error',
                            'message' => 'Message non envoyé car le mode dev est activé.'
                        ]);
                    }

                    if (array_key_exists('clientContact', $data["dataForm"])) {
                        try {
                            $sendMailService->send(
                                'mail-checker.onbot-noreply@gmail.com',
                                "nicolas.ourdouille@outlook.fr",
                                'Message de GVR',
                                'nouscontacter',
                                [
                                    'titre' => $data["dataForm"]['clientContact']['titre'],
                                    'text' => $data["dataForm"]['clientContact']['text'],
                                    'qui' => $user->getEmail(),
                                ]
                            );

                            $sendMailService->send(
                                'mail-checker.onbot-noreply@gmail.com',
                                $user->getEmail(),
                                'Copie de votre message',
                                'nouscontacter',
                                [
                                    'titre' => $data["dataForm"]['clientContact']['titre'],
                                    'text' => $data["dataForm"]['clientContact']['text'],
                                    'qui' => "",
                                ]
                            );
                            return new JsonResponse([
                                'traited' => true,
                                'message' => "Email envoyé."
                            ]);
                        } catch (\Exception $ex) {
                            return new JsonResponse([
                                'critique' => $this->devOnly->displayError($ex->getMessage())
                            ]);
                        }
                    }
                } else {
                    return new JsonResponse([
                        'error' => 'error',
                        'message' => 'Nb de signalement épuisé.'
                    ]);
                }
            }
            return new JsonResponse([
                'error' => 'error2',
            ]);

        } catch (\Exception $ex) {
            return new JsonResponse([
                'critique' => $this->devOnly->displayError($ex->getMessage())
            ]);
        }
    }



}
