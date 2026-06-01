<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

/**
 * Garde un seul bouton d'enregistrement (« Sauvegarder ») sur les formulaires :
 * retire le « Sauvegarder et continuer à éditer » + « créer et ajouter » par défaut d'EA.
 */
trait SingleSaveActionsTrait
{
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Actions::NEW, Action::SAVE_AND_RETURN, static fn (Action $a): Action => $a->setLabel('Enregistrer'))
            ->update(Actions::EDIT, Action::SAVE_AND_RETURN, static fn (Action $a): Action => $a->setLabel('Enregistrer'))
            ->remove(Actions::NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Actions::EDIT, Action::SAVE_AND_CONTINUE);
    }
}
