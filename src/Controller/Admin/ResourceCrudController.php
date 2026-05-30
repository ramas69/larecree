<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Resource;
use App\Entity\ResourceType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

final class ResourceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Resource::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Ressource')
            ->setEntityLabelInPlural('Ressources')
            ->setDefaultSort(['lesson' => 'ASC', 'displayOrder' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('lesson');
        yield ChoiceField::new('type')
            ->setChoices(array_combine(
                array_map(fn (ResourceType $t) => ucfirst($t->value), ResourceType::cases()),
                ResourceType::cases(),
            ))
            ->setFormTypeOption('choice_value', fn (?ResourceType $t) => $t?->value);
        yield TextField::new('title', 'Titre');
        yield UrlField::new('url')->hideOnIndex();
        yield TextField::new('filePath', 'Chemin fichier')->hideOnIndex();
        yield IntegerField::new('displayOrder', 'Ordre');
    }
}
