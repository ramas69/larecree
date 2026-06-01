<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Module;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
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
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('formation');
        yield TextField::new('title', 'Titre');
        yield SlugField::new('slug')->setTargetFieldName('title');
        yield TextareaField::new('description')->setFormTypeOption('attr', ['class' => 'ckeditor'])->hideOnIndex();
        yield ImageField::new('coverImage', 'Image d\'ouverture')
            ->setUploadDir('public/uploads/modules')
            ->setBasePath('/uploads/modules')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setHelp('Visuel d\'ouverture du module (16:9). Laisse vide pour la couverture par défaut.')
            ->setRequired(false)
            ->hideOnIndex();
        yield IntegerField::new('displayOrder', 'Ordre');
    }
}
