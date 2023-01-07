<?php

declare(strict_types=1);

namespace App\Form;

use App\DTO\EntryDto;
use App\Entity\Magazine;
use App\Form\Autocomplete\MagazineAutocompleteField;
use App\Form\Constraint\ImageConstraint;
use App\Form\DataTransformer\TagTransformer;
use App\Form\EventListener\DisableFieldsOnEntryEdit;
use App\Form\EventListener\ImageListener;
use App\Form\EventListener\RemoveFieldsOnEntryImageEdit;
use App\Form\Type\BadgesType;
use App\Service\SettingsManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntryImageType extends AbstractType
{
    public function __construct(
        private readonly ImageListener $imageListener,
        private readonly SettingsManager $settingsManager
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextareaType::class)
            ->add('tags', TextType::class, [
                'autocomplete' => true,
                'tom_select_options' => [
                    'create' => true,
                    'createOnBlur' => true,
                    'delimiter' => ',',
                ],
            ])
            ->add(
                'badges',
                BadgesType::class,
                [
                    'label' => 'Etykiety',
                ]
            )
            ->add(
                'image',
                FileType::class,
                [
                    'constraints' => ImageConstraint::default(),
                    'mapped' => false,
                ]
            )
            ->add('imageAlt', TextareaType::class);

        if ($this->settingsManager->get('KBIN_JS_ENABLED')) {
            $builder->add('magazine', MagazineAutocompleteField::class);
        } else {
            $builder->add(
                'magazine',
                EntityType::class,
                [
                    'class' => Magazine::class,
                    'choice_label' => 'name',
                ]
            );
        }

        $builder->add('isAdult', CheckboxType::class)
            ->add('isEng', CheckboxType::class)
            ->add('isOc', CheckboxType::class)
            ->add('submit', SubmitType::class);

        $builder->get('tags')->addModelTransformer(
            new TagTransformer()
        );
        $builder->addEventSubscriber(new RemoveFieldsOnEntryImageEdit());
        $builder->addEventSubscriber(new DisableFieldsOnEntryEdit());
        $builder->addEventSubscriber($this->imageListener);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => EntryDto::class,
            ]
        );
    }
}
