<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Module;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class ModuleCrudController extends AbstractCrudController
{
    use SingleSaveActionsTrait;

    public static function getEntityFqcn(): string
    {
        return Module::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Module')
            ->setEntityLabelInPlural('Modules')
            ->setDefaultSort(['formation' => 'ASC', 'displayOrder' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        // Vue liste : colonnes compactes
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('formation')->onlyOnIndex();
        yield IntegerField::new('displayOrder', '#')->onlyOnIndex();
        yield TextField::new('title', 'Titre')->onlyOnIndex();

        // Formulaire : 2 colonnes
        yield FormField::addColumn(8);
        yield FormField::addFieldset('Informations')->setIcon('fa fa-circle-info');
        yield AssociationField::new('formation')->onlyOnForms()
            ->setHelp('À quelle formation appartient ce module.');
        yield TextField::new('title', 'Titre')->onlyOnForms();
        yield SlugField::new('slug')->setTargetFieldName('title')->onlyOnForms()
            ->setHelp('Généré depuis le titre. Sert d\'URL.');

        yield FormField::addFieldset('Contenu')->setIcon('fa fa-align-left');
        yield TextareaField::new('description')
            ->setFormTypeOption('attr', ['class' => 'ckeditor'])
            ->setHelp('Présentation du module + le livrable promis à la fin.')
            ->onlyOnForms();

        yield FormField::addColumn(4);
        yield FormField::addFieldset('Réglages')->setIcon('fa fa-sliders');
        yield IntegerField::new('displayOrder', 'Ordre d\'affichage')->onlyOnForms()
            ->setHelp('1 = premier module de la formation.');
        yield ImageField::new('coverImage', 'Image d\'ouverture')
            ->setUploadDir('public/uploads/modules')
            ->setBasePath('/uploads/modules')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setHelp('Visuel 16:9. Vide = couverture par défaut.')
            ->setRequired(false)
            ->onlyOnForms();
    }
}
