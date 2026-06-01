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
    use SingleSaveActionsTrait;

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
        // Vue liste
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('module')->onlyOnIndex();
        yield IntegerField::new('displayOrder', '#')->onlyOnIndex();
        yield TextField::new('title', 'Titre')->onlyOnIndex();

        // Formulaire : 2 colonnes
        yield FormField::addColumn(8);
        yield FormField::addFieldset('Leçon')->setIcon('fa fa-play-circle');
        yield AssociationField::new('module')->onlyOnForms();
        yield TextField::new('title', 'Titre')->onlyOnForms();
        yield SlugField::new('slug')->setTargetFieldName('title')->onlyOnForms()
            ->setHelp('Généré depuis le titre. Sert d\'URL.');
        yield TextareaField::new('description')
            ->setFormTypeOption('attr', ['class' => 'ckeditor'])
            ->setHelp('Éditeur riche : titres, couleurs, alignement, images, listes. Termine par l\'exercice « À toi de jouer ».')
            ->onlyOnForms();

        yield FormField::addColumn(4);
        yield FormField::addFieldset('Vidéo & réglages')->setIcon('fa fa-sliders');
        yield TextField::new('vimeoVideoId', 'ID Vimeo')
            ->setHelp('Héberge la vidéo sur Vimeo, colle l\'identifiant (ex : 123456789).')
            ->onlyOnForms();
        yield IntegerField::new('durationSeconds', 'Durée (secondes)')
            ->setHelp('Ex : 540 = 9 min.')
            ->onlyOnForms();
        yield IntegerField::new('displayOrder', 'Ordre d\'affichage')->onlyOnForms()
            ->setHelp('1 = première leçon du module.');

        yield FormField::addColumn(12);
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
