<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ResourceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ResourceRepository::class)]
#[ORM\Table(name: 'resource')]
#[ORM\HasLifecycleCallbacks]
class Resource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Lesson::class, inversedBy: 'resources')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Lesson $lesson = null;

    #[ORM\Column(length: 16, enumType: ResourceType::class)]
    private ?ResourceType $type = null;

    #[ORM\Column(length: 200)]
    private ?string $title = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column]
    private int $displayOrder = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    public function setLesson(?Lesson $lesson): static
    {
        $this->lesson = $lesson;
        return $this;
    }

    public function getType(): ?ResourceType
    {
        return $this->type;
    }

    public function setType(?ResourceType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[Assert\Callback]
    public function validateTypePayload(ExecutionContextInterface $context): void
    {
        if ($this->type === ResourceType::Link && ($this->url === null || $this->url === '')) {
            $context->buildViolation('A link resource must have a url.')
                ->atPath('url')
                ->addViolation();
        }

        if ($this->type === ResourceType::File && ($this->filePath === null || $this->filePath === '')) {
            $context->buildViolation('A file resource must have a filePath.')
                ->atPath('filePath')
                ->addViolation();
        }
    }

    public function __toString(): string
    {
        return $this->title ?? 'Ressource #'.($this->id ?? 'nouvelle');
    }
}
