<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Lesson;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class LessonCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Lesson::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Leçon')
            ->setEntityLabelInPlural('Leçons')
            ->setDefaultSort(['module' => 'ASC', 'displayOrder' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('module');
        yield TextField::new('title', 'Titre');
        yield SlugField::new('slug')->setTargetFieldName('title');
        yield TextField::new('vimeoVideoId', 'Vimeo ID');
        yield IntegerField::new('durationSeconds', 'Durée (s)');
        yield TextareaField::new('description')->hideOnIndex();
        yield IntegerField::new('displayOrder', 'Ordre');
    }
}
