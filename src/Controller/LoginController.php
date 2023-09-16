<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    // #[Route('/loginTest', name: 'app_login')]
    // public function index(AuthenticationUtils $authenticationUtils): Response
    // {
    //      // get the login error if there is one
    //      $error = $authenticationUtils->getLastAuthenticationError();

    //      // last username entered by the user
    //      $lastUsername = $authenticationUtils->getLastUsername();

    //     return $this->render('login/index.html.twig', [
    //         'last_username' => $lastUsername,
    //         'error'         => $error,
    //     ]);
    // }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout()
    {
        // controller can be blank: it will never be called
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }



    #[Route(path: '/jsonLogin', name: 'app_jsonLogin', methods: ['POST'])]
    public function jsonLogin(#[CurrentUser] ?User $user)
    {
        //voir https://symfony.com/doc/current/security.html pour api login
        //$user = $this->getUser();

        if (null === $user) 
        {
            return $this->json([
                'message' => 'missing credentials 2 auth',
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        return $this->json([
            'token' => "G&GGHYJ&56",
            'message' => 'Welcome to your new controller!',
            'username' => $user->getUserIdentifier(),
            'roles' => $user->getRoles()
        ]);

    }

}
