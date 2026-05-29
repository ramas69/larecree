<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EnrollmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnrollmentRepository::class)]
#[ORM\Table(name: 'enrollment')]
#[ORM\UniqueConstraint(name: 'UNIQ_enrollment_user_formation', columns: ['user_id', 'formation_id'])]
#[ORM\HasLifecycleCallbacks]
class Enrollment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Formation $formation = null;

    #[ORM\Column(length: 16, enumType: EnrollmentSource::class)]
    private ?EnrollmentSource $source = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(nullable: true)]
    private ?int $amountCents = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, LessonProgress>
     */
    #[ORM\OneToMany(targetEntity: LessonProgress::class, mappedBy: 'enrollment', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $progresses;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->progresses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): static
    {
        $this->formation = $formation;
        return $this;
    }

    public function getSource(): ?EnrollmentSource
    {
        return $this->source;
    }

    public function setSource(?EnrollmentSource $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): static
    {
        $this->stripeSessionId = $stripeSessionId;
        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;
        return $this;
    }

    public function getAmountCents(): ?int
    {
        return $this->amountCents;
    }

    public function setAmountCents(?int $amountCents): static
    {
        $this->amountCents = $amountCents;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isPaid(): bool
    {
        return $this->source === EnrollmentSource::Stripe;
    }

    public function isVipGranted(): bool
    {
        return $this->source === EnrollmentSource::Vip;
    }

    /**
     * @return Collection<int, LessonProgress>
     */
    public function getProgresses(): Collection
    {
        return $this->progresses;
    }

    public function addProgress(LessonProgress $progress): static
    {
        if (!$this->progresses->contains($progress)) {
            $this->progresses->add($progress);
            $progress->setEnrollment($this);
        }

        return $this;
    }

    public function removeProgress(LessonProgress $progress): static
    {
        if ($this->progresses->removeElement($progress) && $progress->getEnrollment() === $this) {
            $progress->setEnrollment(null);
        }

        return $this;
    }
}
