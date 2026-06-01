<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

/**
 * Garde un seul bouton d'enregistrement (« Enregistrer ») sur les formulaires :
 * retire le « Sauvegarder et continuer » + « créer et ajouter » par défaut d'EA.
 */
trait SingleSaveActionsTrait
{
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, static fn (Action $a): Action => $a->setLabel('Enregistrer'))
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, static fn (Action $a): Action => $a->setLabel('Enregistrer'))
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE);
    }
}
