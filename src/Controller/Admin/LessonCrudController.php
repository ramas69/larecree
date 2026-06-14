<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Lesson;
use App\Form\ResourceFormType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class LessonCrudController extends AbstractCrudController
{
    use SingleSaveActionsTrait;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

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
        yield FormField::addFieldset('Vidéo')->setIcon('fa fa-film')
            ->setHelp('Vidéo auto-hébergée (recommandé) OU ID Vimeo. Si une vidéo locale est présente, elle est prioritaire.');
        yield Field::new('videoUpload', 'Uploader une vidéo')
            ->setFormType(FileType::class)
            ->setFormTypeOptions([
                'mapped'   => false,
                'required' => false,
                'attr'     => ['accept' => 'video/mp4,video/webm,video/quicktime'],
            ])
            ->setHelp('MP4 conseillé. Limité par la taille max PHP du serveur — pour un gros fichier, dépose-le par FTP dans private/videos/ et colle son nom ci-dessous.')
            ->onlyOnForms();
        yield TextField::new('videoFilename', 'Fichier vidéo (déposé par FTP)')
            ->setRequired(false)
            ->setHelp('Nom du fichier dans private/videos/ (ex : m01-l01.mp4). Rempli automatiquement après un upload. Vide ce champ pour retirer la vidéo.')
            ->onlyOnForms();
        if ($pageName === Crud::PAGE_EDIT) {
            yield Field::new('videoRemove', '🗑 Retirer la vidéo actuelle')
                ->setFormType(CheckboxType::class)
                ->setFormTypeOptions(['mapped' => false, 'required' => false])
                ->setHelp('Coche pour supprimer la vidéo locale (le fichier est effacé). Pour remplacer : upload simplement une nouvelle vidéo.')
                ->onlyOnForms();
        }
        yield TextField::new('vimeoVideoId', 'ID Vimeo (fallback)')
            ->setRequired(false)
            ->setHelp('Optionnel. Utilisé seulement si aucune vidéo locale n\'est définie.')
            ->onlyOnForms();

        yield FormField::addFieldset('Réglages')->setIcon('fa fa-sliders');
        yield IntegerField::new('durationMinutes', 'Durée (minutes)')
            ->setHelp('En minutes (ex : 9). Rempli automatiquement après un upload.')
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

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        return $this->attachVideoUpload(parent::createNewFormBuilder($entityDto, $formOptions, $context));
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        return $this->attachVideoUpload(parent::createEditFormBuilder($entityDto, $formOptions, $context));
    }

    /**
     * Déplace le fichier uploadé vers private/videos/ et renseigne videoFilename.
     */
    private function attachVideoUpload(FormBuilderInterface $builder): FormBuilderInterface
    {
        $projectDir = $this->projectDir;

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event) use ($projectDir): void {
            $form = $event->getForm();
            $lesson = $event->getData();
            if (!$lesson instanceof Lesson) {
                return;
            }

            $destDir = $projectDir.'/private/videos';
            $deleteFile = static function (?string $name) use ($destDir): void {
                if ($name === null || $name === '') {
                    return;
                }
                $path = $destDir.'/'.basename($name);
                if (is_file($path)) {
                    @unlink($path);
                }
            };

            // 1) Retirer la vidéo actuelle (case cochée).
            if ($form->has('videoRemove') && $form->get('videoRemove')->getData() === true) {
                $deleteFile($lesson->getVideoFilename());
                $lesson->setVideoFilename(null);
            }

            // 2) Nouvel upload → remplace (et efface l'ancien fichier).
            $upload = $form->has('videoUpload') ? $form->get('videoUpload')->getData() : null;
            if ($upload instanceof UploadedFile) {
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0775, true);
                }

                $ext  = $upload->guessExtension() ?: 'mp4';
                $base = $lesson->getSlug() ?: 'video';
                $name = $base.'-'.uniqid().'.'.$ext;

                try {
                    $deleteFile($lesson->getVideoFilename());
                    $upload->move($destDir, $name);
                    $lesson->setVideoFilename($name);
                } catch (FileException $e) {
                    // Échec d'écriture (disque/permissions) → erreur de formulaire, pas un 500.
                    $form->get('videoUpload')->addError(new FormError('Échec de l\'upload : '.$e->getMessage()));
                }
            }
        }, 10);

        return $builder;
    }
}
