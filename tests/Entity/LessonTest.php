<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Lesson;
use App\Entity\Module;
use PHPUnit\Framework\TestCase;

final class LessonTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $lesson = new Lesson();

        self::assertNull($lesson->getId());
        self::assertNull($lesson->getModule());
        self::assertSame(0, $lesson->getDisplayOrder());
        self::assertSame(0, $lesson->getDurationSeconds());
        self::assertInstanceOf(\DateTimeImmutable::class, $lesson->getCreatedAt());
        self::assertNull($lesson->getUpdatedAt());
    }

    public function testModuleIsAssignedThroughModuleAddLesson(): void
    {
        $module = new Module();
        $lesson = (new Lesson())
            ->setTitle('Bienvenue')
            ->setSlug('bienvenue')
            ->setVimeoVideoId('999111222')
            ->setDisplayOrder(1);

        $module->addLesson($lesson);

        self::assertSame($module, $lesson->getModule());
        self::assertCount(1, $module->getLessons());
    }

    public function testRemoveLessonDetachesIt(): void
    {
        $module = new Module();
        $lesson = (new Lesson())->setTitle('A')->setSlug('a')->setVimeoVideoId('111');
        $module->addLesson($lesson);

        $module->removeLesson($lesson);

        self::assertNull($lesson->getModule());
        self::assertCount(0, $module->getLessons());
    }

    public function testGetDurationFormattedRendersMinutesAndSeconds(): void
    {
        $lesson = (new Lesson())->setDurationSeconds(754);

        self::assertSame('12 min 34 s', $lesson->getDurationFormatted());
    }

    public function testGetDurationFormattedHandlesUnderOneMinute(): void
    {
        $lesson = (new Lesson())->setDurationSeconds(42);

        self::assertSame('0 min 42 s', $lesson->getDurationFormatted());
    }

    public function testGetDurationFormattedHandlesExactlyZero(): void
    {
        $lesson = new Lesson();

        self::assertSame('0 min 0 s', $lesson->getDurationFormatted());
    }

    public function testGetResourcesReturnsEmptyCollectionOnConstruct(): void
    {
        $lesson = new Lesson();

        self::assertCount(0, $lesson->getResources());
        self::assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $lesson->getResources());
    }
}
