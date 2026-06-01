<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\LessonProgress;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
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
            ->setHelp('index', 'La progression est générée automatiquement quand un·e étudiant·e regarde une leçon. Consultation seule.')
            ->setDefaultSort(['lastWatchedAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        // Lecture seule : la progression est gérée par l'app (lecteur / mark-completed).
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
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
