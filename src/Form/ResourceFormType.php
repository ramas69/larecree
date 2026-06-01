<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Resource;
use App\Entity\ResourceType as ResourceTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire ressource embarqué (CollectionField dans la leçon).
 */
final class ResourceFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EnumType::class, [
                'class'        => ResourceTypeEnum::class,
                'label'        => 'Type',
                'choice_label' => static fn (ResourceTypeEnum $t): string => $t === ResourceTypeEnum::File ? 'Fichier (PDF…)' : 'Lien externe',
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('url', TextType::class, [
                'label'    => 'URL (si lien)',
                'required' => false,
            ])
            ->add('filePath', TextType::class, [
                'label'    => 'Chemin fichier (si fichier)',
                'required' => false,
            ])
            ->add('displayOrder', IntegerType::class, [
                'label' => 'Ordre',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Resource::class,
        ]);
    }
}
