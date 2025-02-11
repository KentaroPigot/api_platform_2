<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Controller to render a basic "homepage".
 */
class FrontendController extends AbstractController
{
    #[Route(path: '/', name: 'homepage')]
    public function homepage(LoggerInterface $logger, SerializerInterface $serializer)
    {
        return $this->render('frontend/homepage.html.twig', ['user' => $serializer->serialize($this->getUser(), 'jsonld')]);
    }
}
