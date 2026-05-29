<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Lesson;
use App\Entity\Resource;
use App\Entity\ResourceType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ResourceTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $resource = new Resource();

        self::assertNull($resource->getId());
        self::assertNull($resource->getLesson());
        self::assertSame(0, $resource->getDisplayOrder());
        self::assertNull($resource->getType());
        self::assertNull($resource->getUrl());
        self::assertNull($resource->getFilePath());
        self::assertInstanceOf(\DateTimeImmutable::class, $resource->getCreatedAt());
    }

    public function testLessonIsAssignedThroughLessonAddResource(): void
    {
        $lesson = new Lesson();
        $resource = (new Resource())
            ->setType(ResourceType::Link)
            ->setTitle('Doc Anthropic')
            ->setUrl('https://docs.anthropic.com');

        $lesson->addResource($resource);

        self::assertSame($lesson, $resource->getLesson());
        self::assertCount(1, $lesson->getResources());
    }

    public function testRemoveResourceDetachesIt(): void
    {
        $lesson = new Lesson();
        $resource = (new Resource())
            ->setType(ResourceType::File)
            ->setTitle('PDF')
            ->setFilePath('/uploads/x.pdf');
        $lesson->addResource($resource);

        $lesson->removeResource($resource);

        self::assertNull($resource->getLesson());
        self::assertCount(0, $lesson->getResources());
    }

    public function testValidationFailsWhenLinkResourceHasNoUrl(): void
    {
        $resource = (new Resource())
            ->setType(ResourceType::Link)
            ->setTitle('Doc');

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $violations = $validator->validate($resource);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('url', $violations[0]->getPropertyPath());
    }

    public function testValidationFailsWhenFileResourceHasNoFilePath(): void
    {
        $resource = (new Resource())
            ->setType(ResourceType::File)
            ->setTitle('PDF');

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $violations = $validator->validate($resource);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('filePath', $violations[0]->getPropertyPath());
    }

    public function testValidationPassesWhenLinkHasUrl(): void
    {
        $resource = (new Resource())
            ->setType(ResourceType::Link)
            ->setTitle('Doc')
            ->setUrl('https://example.com');

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $violations = $validator->validate($resource);

        self::assertCount(0, $violations);
    }

    public function testValidationPassesWhenFileHasFilePath(): void
    {
        $resource = (new Resource())
            ->setType(ResourceType::File)
            ->setTitle('PDF')
            ->setFilePath('/uploads/x.pdf');

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $violations = $validator->validate($resource);

        self::assertCount(0, $violations);
    }
}
