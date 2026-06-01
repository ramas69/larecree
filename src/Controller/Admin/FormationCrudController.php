<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Formation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class FormationCrudController extends AbstractCrudController
{
    use SingleSaveActionsTrait;

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
        // Vue liste
        yield IdField::new('id')->onlyOnIndex();
        yield IntegerField::new('displayOrder', '#')->onlyOnIndex();
        yield TextField::new('title', 'Titre')->onlyOnIndex();
        yield MoneyField::new('priceCents', 'Prix')->setCurrency('EUR')->setStoredAsCents(true)->onlyOnIndex();
        yield BooleanField::new('published', 'Publié')->onlyOnIndex();
        yield BooleanField::new('comingSoon', 'Bientôt')->onlyOnIndex();

        // Formulaire : 2 colonnes
        yield FormField::addColumn(8);
        yield FormField::addFieldset('Informations')->setIcon('fa fa-circle-info');
        yield TextField::new('title', 'Titre')->onlyOnForms();
        yield SlugField::new('slug')->setTargetFieldName('title')->onlyOnForms()
            ->setHelp('Généré depuis le titre. Sert d\'URL (/formations/<slug>).');
        yield TextField::new('subtitle', 'Sous-titre')->onlyOnForms()
            ->setHelp('Phrase d\'accroche affichée sous le titre.');
        yield TextareaField::new('description')
            ->setFormTypeOption('attr', ['class' => 'ckeditor'])
            ->setHelp('Présentation de la formation (éditeur riche).')
            ->onlyOnForms();

        yield FormField::addColumn(4);
        yield FormField::addFieldset('Tarif & visuel')->setIcon('fa fa-tag');
        yield MoneyField::new('priceCents', 'Prix')->setCurrency('EUR')->setStoredAsCents(true)->onlyOnForms();
        yield ImageField::new('coverImage', 'Image de couverture')
            ->setUploadDir('public/uploads/formations')
            ->setBasePath('/uploads/formations')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setHelp('JPG/PNG paysage 16:9. Vide = couverture par défaut.')
            ->setRequired(false)
            ->onlyOnForms();

        yield FormField::addFieldset('Publication')->setIcon('fa fa-rocket');
        yield BooleanField::new('published', 'Publié')->onlyOnForms()
            ->setHelp('Visible dans le catalogue.');
        yield BooleanField::new('comingSoon', 'Bientôt disponible')->onlyOnForms()
            ->setHelp('Badge « Bientôt » + accès leçons bloqué.');
        yield IntegerField::new('displayOrder', 'Ordre d\'affichage')->onlyOnForms();
        yield TextField::new('vimeoFolderId', 'Dossier Vimeo (optionnel)')->onlyOnForms();
    }
}
