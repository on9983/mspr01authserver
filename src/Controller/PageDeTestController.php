<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageDeTestController extends AbstractController
{
    #[Route('/user/pageDeTest', name: 'app_page_de_test')]
    public function index(): Response
    {
        return $this->render('page_de_test/index.html.twig', [
            'controller_name' => 'PageDeTestController',
        ]);
    }
}
