<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\LessonProgress;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

final class LessonProgressCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LessonProgress::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Progression leçon')
            ->setEntityLabelInPlural('Progressions leçon')
            ->setDefaultSort(['lastWatchedAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('enrollment', 'Inscription');
        yield AssociationField::new('lesson', 'Leçon');
        yield IntegerField::new('watchedSeconds', 'Secondes vues');
        yield IntegerField::new('percentWatched', '% vu');
        yield DateTimeField::new('completedAt', 'Terminée le')->setRequired(false);
        yield DateTimeField::new('lastWatchedAt', 'Dernière vue')->hideOnForm();
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
    }
}
