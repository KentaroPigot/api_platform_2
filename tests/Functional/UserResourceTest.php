<?php

namespace App\Tests\Functional;

// use App\ApiPlatform\Test\ApiTestCase;

use App\Entity\CheeseListing;
use App\Entity\User;
use App\Test\CustomApiTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;

class UserResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateUser()
    {
        $client = self::createClient();

        $client->request('POST', '/api/users', [
            'json' => [
                'email' => 'cheeseplease@example.com',
                'username' => 'cheeseplease',
                'password' => 'brie'
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);

        $this->logIn($client, 'cheeseplease@example.com', 'brie');
    }

    public function testUpdateUser()
    {
        $client = self::createClient();
        $user = $this->createUserAndLogIn($client, 'cheeseplease@example.com', 'foo');

        $client->request('PATCH', '/api/users/' . $user->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json', // Nécessaire pour PATCH
            ],
            'json' => [
                "type" => "users",
                "id" =>  $user->getId(),
                'username' => 'newusername',
                'roles' => ['ROLE_ADMIN']
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'username' => 'newusername',
        ]);

        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->find($user->getId());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    public function testGetUser()
    {
        $client = self::createClient();
        $user = $this->createUser('cheeseplease@example.com', 'foo');
        $this->createUserAndLogIn($client, 'authenticated@example.com', 'foo');

        $user->setPhoneNumber('555.123.4567');
        $em = $this->getEntityManager();
        $em->flush();

        $client->request('GET', '/api/users/' . $user->getId());
        $this->assertJsonContains([
            'username' => 'cheeseplease',
        ]);

        $data = $client->getResponse()->toArray();
        $this->assertArrayNotHasKey('phoneNumber', $data);

        // refresh the user & elevate to admin
        $user = $em->getRepository(User::class)->find($user->getId());
        $user->setRoles(['ROLE_ADMIN']);
        $em->flush();
        $this->logIn($client, 'cheeseplease@example.com', 'foo');

        $client->request('GET', '/api/users/' . $user->getId());
        $this->assertJsonContains([
            'phoneNumber' => '555.123.4567',
        ]);
    }
}
