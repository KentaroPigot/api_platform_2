<?php

namespace App\Tests\Functional;

// use App\ApiPlatform\Test\ApiTestCase;

use App\Entity\CheeseListing;
use App\Entity\User;
use App\Test\CustomApiTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;

class CheeseListingResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateCheeseListing()
    {
        $client = self::createClient();
        $client->request('POST', '/api/cheeses', [
            'json' => [],
        ]);
        $this->assertResponseStatusCodeSame(401);

        $authenticatedUser = $this->createUserAndLogIn($client, 'cheeseplease@example.com', 'foo');
        $otherUser = $this->createUser('otheruser@example.com', 'foo');

        $cheesyData = [
            'title' => 'Mystery cheese',
            'description' => 'A mystery',
            'price' => 1000,
        ];

        $client->request('POST', '/api/cheeses', [
            'json' => $cheesyData + ['owner' => '/api/users/' . $otherUser->getId()],
        ]);
        $this->assertResponseStatusCodeSame(422, 'not passing the correct owner');

        $client->request('POST', '/api/cheeses', [
            'json' => $cheesyData + ['owner' => '/api/users/' . $authenticatedUser->getId()],
        ]);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testUpdateCheeseListing()
    {
        $client = self::createClient();
        $user1 = $this->createUser('user1@example.com', 'Test1234');
        $user2 = $this->createUser('user2@example.com', 'Test1234');

        $cheeseListing = new CheeseListing('Block of cheddar');
        $cheeseListing->setOwner($user1);
        $cheeseListing->setPrice(1000);
        $cheeseListing->setDescription('A great cheese!');
        $cheeseListing->setIsPublished(true);

        $em = $this->getEntityManager();
        $em->persist($cheeseListing);
        $em->flush();

        $this->logIn($client, 'user2@example.com', 'Test1234');
        $client->request('PATCH', '/api/cheeses/' . $cheeseListing->getId(), [
            'headers' => [
                'Content-Type' => 'application/ld+json', // Nécessaire pour PATCH
            ],
            'json' => [
                "type" =>  "cheeses",
                "id" =>  $cheeseListing->getId(),
                'title' => 'Updated Cheddar',
                'owner' => '/api/users/' . $user2->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
        // var_dump($client->getResponse()->getContent(false));

        $this->logIn($client, 'user1@example.com', 'Test1234');
        $loggedInUser = self::getContainer()->get('security.token_storage')->getToken()->getUser();
        // dump($loggedInUser->getEmail());
        // dump('Logged in as user1: ' . $user1->getEmail());
        // dump('User1 Id: ' . $user1->getId());
        // dump('CheeseListingId: ' . $cheeseListing->getId());
        // dump('CheeseListingOwner: ' . $cheeseListing->getOwner()->getEmail());
        // dump('CheeseListingOwnerId: ' . $cheeseListing->getOwner()->getId());
        $client->request('PATCH', '/api/cheeses/' . $cheeseListing->getId(), [
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'title' => 'Updated Cheddar',
            ],
        ]);

        // dump($client->getResponse()->getContent(false)); // Debugging
        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetCheeseListingCollection()
    {
        $client = self::createClient();
        $user = $this->createUser('cheeseplease@example.com', 'foo');

        $cheeseListing1 = new CheeseListing('Block of cheddar');
        $cheeseListing1->setOwner($user);
        $cheeseListing1->setPrice(1000);
        $cheeseListing1->setDescription('A great cheese!');

        $cheeseListing2 = new CheeseListing('Wheel of brie');
        $cheeseListing2->setOwner($user);
        $cheeseListing2->setPrice(1000);
        $cheeseListing2->setDescription('A great cheese!');
        $cheeseListing2->setIsPublished(true);

        $cheeseListing3 = new CheeseListing('Wheel of bleu');
        $cheeseListing3->setOwner($user);
        $cheeseListing3->setPrice(1000);
        $cheeseListing3->setDescription('A great cheese!');
        $cheeseListing3->setIsPublished(true);


        $em = $this->getEntityManager();
        $em->persist($cheeseListing1);
        $em->persist($cheeseListing2);
        $em->persist($cheeseListing3);
        $em->flush();

        $client->request('GET', '/api/cheeses');
        $this->assertJsonContains(['hydra:totalItems' => 2]);
    }

    public function testGetCheeseListingItem()
    {
        $client = self::createClient();
        $user = $this->createUserAndLogIn($client, 'cheeseplease@example.com', 'foo');

        $cheeseListing1 = new CheeseListing('Block of cheddar');
        $cheeseListing1->setOwner($user);
        $cheeseListing1->setPrice(1000);
        $cheeseListing1->setDescription('A great cheese!');
        $cheeseListing1->setIsPublished(false);

        $em = $this->getEntityManager();
        $em->persist($cheeseListing1);
        $em->flush();

        $client->request('GET', '/api/cheeses/' . $cheeseListing1->getId());
        $this->assertResponseStatusCodeSame(404);

        $client->request('GET', '/api/cheeses/' . $cheeseListing1->getId());
        $data = $client->getResponse()->toArray();
        $this->assertEmpty($data['cheeseListings']);
    }
}
