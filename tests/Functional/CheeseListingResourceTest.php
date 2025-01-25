<?php

namespace App\Tests\Functional;

// use App\ApiPlatform\Test\ApiTestCase;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;

class CheeseListingResourceTest extends ApiTestCase
{
    public function testCreateCheeseListing()
    {
        $client = self::createClient();

        // Effectuer la requête POST avec le bon en-tête Content-Type et données JSON
        $client->request('POST', '/api/cheeses', [
            'json' => [],
            'headers' => [
                'Content-Type' => 'application/json', // Assurez-vous que l'en-tête est bien défini
            ],
        ]);

        // Vérifie que la réponse est une erreur 401 si l'utilisateur n'est pas authentifié
        $this->assertResponseStatusCodeSame(401);

        $user = new User();
        $user->setEmail('cheeseplease@example.com');
        $user->setUsername('cheeseplease');
        $user->setPassword('$2y$13$kZkUgc/28CdU4gNQgcOHJOSG79dfCivBzDI4NJ.YjDz4rn5PrQCpO');

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        $client->request('POST', '/login', [
            'json' => [
                'email' => 'cheeseplease@example.com',
                'password' => 'Test1234',
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(204);
    }
}
