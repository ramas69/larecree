<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Resource;
use App\Entity\ResourceType as ResourceTypeEnum;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File as FileConstraint;

/**
 * Ressource embarquée (CollectionField dans la leçon).
 * Simplifié : un titre + soit un lien, soit un fichier téléversé.
 * Le type est déduit automatiquement (Lien si URL, Fichier si upload).
 */
final class ResourceFormType extends AbstractType
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('url', TextType::class, [
                'label'    => 'Lien (https://…)',
                'required' => false,
                'help'     => 'Pour un lien externe.',
            ])
            ->add('uploadFile', FileType::class, [
                'label'       => 'Ou téléverser un fichier',
                'required'    => false,
                'mapped'      => false,
                'help'        => 'PDF, image, doc… (max 10 Mo).',
                'constraints' => [
                    new FileConstraint(maxSize: '10M'),
                ],
            ]);

        // Priorité élevée : déduire type + déplacer le fichier AVANT la validation (Assert\Callback).
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            /** @var Resource $resource */
            $resource = $event->getData();
            if ($resource === null) {
                return;
            }

            $upload = $event->getForm()->get('uploadFile')->getData();
            if ($upload instanceof UploadedFile) {
                $destDir = $this->projectDir.'/public/uploads/content';
                $ext = $upload->guessExtension() ?: 'bin';
                $name = bin2hex(random_bytes(8)).'-'.time().'.'.$ext;
                $upload->move($destDir, $name);
                $resource->setFilePath('/uploads/content/'.$name);
                $resource->setUrl(null);
                $resource->setType(ResourceTypeEnum::File);

                return;
            }

            // Pas de fichier : c'est un lien si une URL est fournie.
            if ($resource->getUrl() !== null && $resource->getUrl() !== '') {
                $resource->setType(ResourceTypeEnum::Link);
                $resource->setFilePath(null);
            } elseif ($resource->getFilePath() !== null && $resource->getFilePath() !== '') {
                // fichier déjà présent (édition sans nouveau upload) → reste un fichier
                $resource->setType(ResourceTypeEnum::File);
            }
        }, 10);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Resource::class,
        ]);
    }
}
