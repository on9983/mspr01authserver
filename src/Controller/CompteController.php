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

class CompteController extends AbstractController
{

    public function __construct(
        private DevOnly $devOnly,
    ) {
        $this->devOnly = $devOnly;
    }

    #[Route('/compte/suppr', name: 'compte_suppr')]
    public function nousContacter(
        Request $request,
        UserRepository $userRepository,
    ): Response {
        $data = json_decode($request->getContent(), true);

        try {
            $uid = $data["uid"];

            $user = $userRepository->findOneByUid($uid);
            if ($user) {
                $userRepository->remove($user, true);
                return new JsonResponse([
                    'traited' => true,
                    'message' => "Votre compte a été supprimé avec succès."
                ]);
            }
            return new JsonResponse([
                'error' => 'error',
                'message' => "Utilisateur inconnue."
            ]);

        } catch (\Exception $ex) {
            return new JsonResponse([
                'critique' => $this->devOnly->displayError($ex->getMessage())
            ]);
        }
    }



}
