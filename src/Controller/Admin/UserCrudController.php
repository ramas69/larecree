<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('email');
        yield TextField::new('firstName', 'Prénom');
        yield TextField::new('lastName', 'Nom');
        yield ChoiceField::new('roles')
            ->setChoices([
                'Admin'   => 'ROLE_ADMIN',
                'VIP'     => 'ROLE_VIP',
                'Student' => 'ROLE_STUDENT',
            ])
            ->allowMultipleChoices()
            ->renderExpanded();
        yield TextField::new('plainPassword', 'Nouveau mot de passe')
            ->setFormType(\Symfony\Component\Form\Extension\Core\Type\PasswordType::class)
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->onlyOnForms();
        yield BooleanField::new('isVerified', 'Vérifié')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }

    public function createEntity(string $entityFqcn): User
    {
        $user = new User();
        $user->setRoles(['ROLE_STUDENT']);

        return $user;
    }

    /**
     * @param User $entityInstance
     */
    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPlainPasswordIfSet($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * @param User $entityInstance
     */
    public function updateEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPlainPasswordIfSet($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function hashPlainPasswordIfSet(User $user): void
    {
        $plain = $user->getPlainPassword();
        if ($plain !== null && $plain !== '') {
            $user->setPassword($this->hasher->hashPassword($user, $plain));
            $user->eraseCredentials();
        }
    }
}
