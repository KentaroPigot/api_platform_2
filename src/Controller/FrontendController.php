<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to render a basic "homepage".
 */
class FrontendController extends AbstractController
{
    #[Route(path: '/', name: 'homepage')]
    public function homepage()
    {
        return $this->render('frontend/homepage.html.twig');
    }
}
