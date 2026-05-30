<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Enrollment;
use App\Entity\EnrollmentSource;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class EnrollmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Enrollment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Inscription')
            ->setEntityLabelInPlural('Inscriptions')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('user', 'Utilisateur');
        yield AssociationField::new('formation');
        yield ChoiceField::new('source')
            ->setChoices(array_combine(
                array_map(fn (EnrollmentSource $s) => ucfirst($s->value), EnrollmentSource::cases()),
                EnrollmentSource::cases(),
            ))
            ->setFormTypeOption('choice_value', fn (?EnrollmentSource $s) => $s?->value);
        yield MoneyField::new('amountCents', 'Montant')->setCurrency('EUR')->setStoredAsCents(true)->hideOnIndex();
        yield TextField::new('stripeSessionId', 'Stripe session')->hideOnIndex();
        yield TextField::new('stripePaymentIntentId', 'Stripe PI')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
    }
}
