<?php

namespace App\ApiPlatform;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operations;

class AutoGroupResourceMetadataFactory implements ResourceMetadataCollectionFactoryInterface
{
    private $decorated;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        // Récupère les métadonnées de la ressource d'origine
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        // Parcourt chaque ressource dans la collection
        foreach ($resourceMetadataCollection as $resourceMetadata) {
            // Récupère toutes les opérations (item et collection)
            $operations = $resourceMetadata->getOperations();

            // Met à jour les contextes pour chaque opération
            $updatedOperations = [];
            foreach ($operations as $operationName => $operation) {
                // Vérifie si l'opération est une opération standard (ignore les opérations d'erreur)
                $isItem = str_ends_with($operationName, ':item') || in_array($operationName, ['GET', 'PUT', 'PATCH', 'DELETE']);
                $updatedOperations[$operationName] = $this->updateContextOnOperation($operation, $resourceMetadata->getShortName(), $isItem, $operationName);
            }

            // Convertit le tableau d'opérations en objet Operations
            $updatedOperationsObject = new Operations($updatedOperations);

            // Met à jour les métadonnées avec les opérations modifiées
            $resourceMetadata = $resourceMetadata->withOperations($updatedOperationsObject);
        }

        // Retourne la collection de métadonnées modifiée
        return $resourceMetadataCollection;
    }

    private function updateContextOnOperation(Operation $operation, string $shortName, bool $isItem, string $operationName): Operation
    {
        // Normalization (lecture)
        $normalizationContext = $operation->getNormalizationContext() ?? [];
        $normalizationContext['groups'] = $normalizationContext['groups'] ?? [];
        $normalizationContext['groups'] = array_unique(array_merge(
            $normalizationContext['groups'],
            $this->getDefaultGroups($shortName, true, $isItem, $operationName)
        ));
        $operation = $operation->withNormalizationContext($normalizationContext);

        // Denormalization (écriture)
        $denormalizationContext = $operation->getDenormalizationContext() ?? [];
        $denormalizationContext['groups'] = $denormalizationContext['groups'] ?? [];
        $denormalizationContext['groups'] = array_unique(array_merge(
            $denormalizationContext['groups'],
            $this->getDefaultGroups($shortName, false, $isItem, $operationName)
        ));
        $operation = $operation->withDenormalizationContext($denormalizationContext);

        // dump([
        //     'operation' => $operationName,
        //     'denormalization_groups' => $denormalizationContext['groups'],
        // ]);

        return $operation;
    }

    private function getDefaultGroups(string $shortName, bool $normalization, bool $isItem, string $operationName): array
    {
        $shortName = strtolower($shortName);
        $readOrWrite = $normalization ? 'read' : 'write';
        $itemOrCollection = $isItem ? 'item' : 'collection';

        // Génère des groupes de sérialisation par défaut
        $groups = [
            // {shortName}:{read/write}
            // Exemple : user:read
            sprintf('%s:%s', $shortName, $readOrWrite),

            // {shortName}:{item/collection}:{read/write}
            // Exemple : user:item:read
            sprintf('%s:%s:%s', $shortName, $itemOrCollection, $readOrWrite),

            // {shortName}:{item/collection}:{operationName}
            // Exemple : user:item:get
            sprintf('%s:%s:%s', $shortName, $itemOrCollection, $operationName),
        ];

        if ($shortName !== 'constraintviolationlist') {
            dump(
                [
                    'shortName' => $shortName,
                    'readOrWrite' => $readOrWrite,
                    'itemOrCollection' => $itemOrCollection,
                    'operationName' => $operationName,
                    'groups' => $groups,
                ]
            );
        }


        return $groups;
    }
}
