<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserStateProcessor implements ProcessorInterface
{
    private ProcessorInterface $decoratedProcessor;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(ProcessorInterface $persistProcessor, UserPasswordHasherInterface $passwordHasher)
    {
        $this->decoratedProcessor = $persistProcessor;
        $this->passwordHasher = $passwordHasher;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof User && $data->getPlainPassword()) {
            // Crypter le mot de passe
            $data->setPassword(
                $this->passwordHasher->hashPassword($data, $data->getPlainPassword())
            );
            // $data->eraseCredentials(); // Supprime le mot de passe en clair
        }

        // Appeler le processor décoré pour la persistance des données
        return $this->decoratedProcessor->process($data, $operation, $uriVariables, $context);
    }
}
