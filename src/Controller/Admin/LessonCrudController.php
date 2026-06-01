<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Lesson;
use App\Form\ResourceFormType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
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
        yield FormField::addFieldset('Leçon')->setIcon('fa fa-play-circle');
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('module');
        yield TextField::new('title', 'Titre');
        yield SlugField::new('slug')->setTargetFieldName('title');
        yield TextField::new('vimeoVideoId', 'ID Vimeo')
            ->setHelp('Héberge la vidéo sur Vimeo, puis colle ici l\'identifiant numérique (ex : 123456789). Pas d\'upload de fichier vidéo ici.')
            ->hideOnIndex();
        yield IntegerField::new('durationSeconds', 'Durée (secondes)')
            ->setHelp('Durée de la vidéo en secondes (ex : 540 = 9 min).');
        yield TextareaField::new('description')
            ->setFormTypeOption('attr', ['class' => 'ckeditor'])
            ->setHelp('Éditeur riche : titres, couleurs, alignement, images, listes. Termine par l\'exercice « À toi de jouer ».')
            ->hideOnIndex();
        yield IntegerField::new('displayOrder', 'Ordre');

        yield FormField::addFieldset('Ressources de la leçon')
            ->setIcon('fa fa-link')
            ->setHelp('Ajoute autant de ressources que tu veux : fiches PDF, liens utiles. Chacune a un titre.');
        yield CollectionField::new('resources', 'Ressources')
            ->setEntryType(ResourceFormType::class)
            ->setEntryIsComplex(true)
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->onlyOnForms();
    }
}
