<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use App\Repository\CheeseListingRepository;
use App\Validator\IsValidOwner;
use Carbon\Carbon;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[
    ApiResource(
        operations: [
            new Get(normalizationContext: ['groups' => ['cheese:item:get']]),
            new GetCollection(),
            new Post(security: 'is_granted("ROLE_USER")', denormalizationContext: ['groups' => ['cheese:collection:post', 'cheese:read']]),
            new Patch(security: 'is_granted("POST_EDIT", object)', securityMessage: 'Only the creator can edit a cheese listing'),
            new Delete(security: 'is_granted("ROLE_ADMIN")')
        ],
        shortName: 'cheese',
        normalizationContext: ['groups' => ['cheese:read'], 'swagger_definition_name' => 'Read'],
        denormalizationContext: ['groups' => ['cheese:write'],  'swagger_definition_name' => 'Write'],
        paginationItemsPerPage: 10,
        formats: ['jsonld', 'json', 'html', 'csv']
    ),
]
#[ApiFilter(BooleanFilter::class, properties: ['isPublished'])]
#[ApiFilter(SearchFilter::class, properties: ['title' => 'partial', "description" => "partial", "owner" => "exact", "owner.username" => "partial"])]
#[ApiFilter(RangeFilter::class, properties: ['price'])]
#[ApiFilter(PropertyFilter::class)]
#[ORM\Entity(repositoryClass: CheeseListingRepository::class)]
#[ORM\EntityListeners(["App\Doctrine\CheeseListingSetOwnerListener"])]
class CheeseListing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['cheese:read', 'cheese:write', 'user:read', 'user:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50, maxMessage: 'Le titre ne doit pas dépasser {{ limit }} caractères')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['cheese:read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['cheese:read', 'cheese:write', 'user:read', 'user:write'])]
    #[Assert\NotBlank]
    private ?int $price = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?bool $isPublished = false;

    // #[Assert\Valid] // Permet de valider les données de l'entité associée
    #[ORM\ManyToOne(inversedBy: 'cheeseListings')]
    #[Groups(['cheese:collection:post', 'cheese:read'])]
    #[ORM\JoinColumn(nullable: false)]
    #[IsValidOwner()]
    private ?User $owner;

    public function __construct(string $title = null)
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->title = $title; // Permet de définir un titre immutable à la création de l'objet
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getTitle(): ?string
    {
        return $this->title;
    }

    // public function setTitle(string $title): static
    // {
    //     $this->title = $title;

    //     return $this;
    // }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    #[Groups(['cheese:read'])]
    public function getShortDescription(): ?string
    {
        if (strlen($this->description) < 40) {
            return $this->description;
        }

        return substr($this->description, 0, 40) . '...';
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    #[Groups(['cheese:write', 'user:write'])]
    #[SerializedName('description')]
    public function setTextDescription(string $description): static
    {
        $this->description = nl2br($description);

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[Groups(['cheese:read'])]
    public function getCreatedAtAgo(): string
    {
        return Carbon::instance($this->createdAt)->diffForHumans();
    }

    // public function setCreatedAt(\DateTimeImmutable $createdAt): static
    // {
    //     $this->createdAt = $createdAt;

    //     return $this;
    // }

    public function getIsPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}
