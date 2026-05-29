<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LessonProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LessonProgressRepository::class)]
#[ORM\Table(name: 'lesson_progress')]
#[ORM\UniqueConstraint(name: 'UNIQ_lesson_progress_enrollment_lesson', columns: ['enrollment_id', 'lesson_id'])]
class LessonProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Enrollment::class, inversedBy: 'progresses')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Enrollment $enrollment = null;

    #[ORM\ManyToOne(targetEntity: Lesson::class, inversedBy: 'progresses')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Lesson $lesson = null;

    #[ORM\Column]
    private int $watchedSeconds = 0;

    #[ORM\Column]
    private int $percentWatched = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $lastWatchedAt;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->lastWatchedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEnrollment(): ?Enrollment
    {
        return $this->enrollment;
    }

    public function setEnrollment(?Enrollment $enrollment): static
    {
        $this->enrollment = $enrollment;
        return $this;
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

    public function getWatchedSeconds(): int
    {
        return $this->watchedSeconds;
    }

    public function getPercentWatched(): int
    {
        return $this->percentWatched;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getLastWatchedAt(): \DateTimeImmutable
    {
        return $this->lastWatchedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }

    public function recordWatch(int $seconds, int $percent): static
    {
        if ($seconds < 0) {
            throw new \InvalidArgumentException('watchedSeconds must be >= 0.');
        }

        $this->watchedSeconds = $seconds;
        $this->percentWatched = max(0, min(100, $percent));
        $this->lastWatchedAt = new \DateTimeImmutable();

        return $this;
    }

    public function markCompleted(): static
    {
        if ($this->completedAt === null) {
            $this->completedAt = new \DateTimeImmutable();
        }
        $this->percentWatched = 100;

        return $this;
    }
}
