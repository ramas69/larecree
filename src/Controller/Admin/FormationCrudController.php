<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Formation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class FormationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Formation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Formation')
            ->setEntityLabelInPlural('Formations')
            ->setDefaultSort(['displayOrder' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Titre');
        yield SlugField::new('slug')->setTargetFieldName('title');
        yield TextField::new('subtitle', 'Sous-titre')->hideOnIndex();
        yield TextareaField::new('description')->hideOnIndex();
        yield MoneyField::new('priceCents', 'Prix')->setCurrency('EUR')->setStoredAsCents(true);
        yield TextField::new('coverImage', 'Image')->hideOnIndex();
        yield TextField::new('vimeoFolderId', 'Vimeo Folder')->hideOnIndex();
        yield BooleanField::new('published', 'Publié');
        yield IntegerField::new('displayOrder', 'Ordre');
    }
}
