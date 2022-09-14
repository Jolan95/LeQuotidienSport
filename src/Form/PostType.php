<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('picture', FileType::class, [
                'label' => "Photo d'article",
                'constraints' => [
                    new File([
                    'mimeTypes' => ['image/jpeg', 'image/svg+xml', 'image/png'],
                    'mimeTypesMessage' => 'Please upload a format valid (.JPG, JPEG, .SVG, .PNG)',
                ])
            ]])
            ->add('content')
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'Football' => 'Football',
                    'Basketball' => 'Basketball',
                    'Tennis' => 'Tennis',
                    'Rugby' => 'Rugby',
                    'Cyclisme' => 'Cyclisme',
                    'Autres' => 'Autres'
                ],
            ])
            ->add('important', CheckboxType::class,["required" => false])
            ->add('Publier', SubmitType::class)

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
