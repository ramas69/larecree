<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Enrollment;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use PHPUnit\Framework\TestCase;

final class LessonProgressTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $progress = new LessonProgress();

        self::assertNull($progress->getId());
        self::assertNull($progress->getEnrollment());
        self::assertNull($progress->getLesson());
        self::assertSame(0, $progress->getWatchedSeconds());
        self::assertSame(0, $progress->getPercentWatched());
        self::assertNull($progress->getCompletedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $progress->getLastWatchedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $progress->getCreatedAt());
        self::assertFalse($progress->isCompleted());
    }

    public function testAddProgressToEnrollmentBindsBothSides(): void
    {
        $enrollment = new Enrollment();
        $progress = new LessonProgress();

        $enrollment->addProgress($progress);

        self::assertSame($enrollment, $progress->getEnrollment());
        self::assertCount(1, $enrollment->getProgresses());
    }

    public function testAddProgressToLessonBindsBothSides(): void
    {
        $lesson = new Lesson();
        $progress = new LessonProgress();

        $lesson->addProgress($progress);

        self::assertSame($lesson, $progress->getLesson());
        self::assertCount(1, $lesson->getProgresses());
    }

    public function testRecordWatchUpdatesSecondsPercentAndLastWatchedAt(): void
    {
        $progress = new LessonProgress();
        $before = $progress->getLastWatchedAt();
        usleep(2_000);

        $progress->recordWatch(120, 50);

        self::assertSame(120, $progress->getWatchedSeconds());
        self::assertSame(50, $progress->getPercentWatched());
        self::assertGreaterThan($before, $progress->getLastWatchedAt());
        self::assertNull($progress->getCompletedAt());
    }

    public function testRecordWatchClampsPercentBetween0And100(): void
    {
        $progress = new LessonProgress();

        $progress->recordWatch(10, -5);
        self::assertSame(0, $progress->getPercentWatched());

        $progress->recordWatch(10, 250);
        self::assertSame(100, $progress->getPercentWatched());
    }

    public function testRecordWatchRefusesNegativeSeconds(): void
    {
        $progress = new LessonProgress();

        $this->expectException(\InvalidArgumentException::class);
        $progress->recordWatch(-1, 0);
    }

    public function testMarkCompletedSetsTimestampPercent100AndIsCompletedTrue(): void
    {
        $progress = new LessonProgress();

        $progress->markCompleted();

        self::assertTrue($progress->isCompleted());
        self::assertSame(100, $progress->getPercentWatched());
        self::assertInstanceOf(\DateTimeImmutable::class, $progress->getCompletedAt());
    }

    public function testMarkCompletedIsIdempotent(): void
    {
        $progress = new LessonProgress();

        $progress->markCompleted();
        $first = $progress->getCompletedAt();
        usleep(2_000);
        $progress->markCompleted();

        self::assertSame($first, $progress->getCompletedAt());
    }
}
