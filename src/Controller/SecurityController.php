<?php

namespace App\Controller;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Symfony\Routing\IriConverter;
use App\Entity\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login', methods: ['POST'])]
    public function login(IriConverterInterface $iriConverter): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->json([
                'error' => 'Invalid login request, check that the content-type is application/json and that the body is correctly formatted',
            ], 400);
        }

        /** @var User|null $user */
        $user = $this->getUser();

        // return $this->json([
        //     'user' => $user ?  $user->getId() : null,
        // ]);

        return new Response(null, 204, [
            'Location' => $iriConverter->getIriFromResource($user),
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): void
    {
        throw new \Exception('This should never be reached!');
    }
}
