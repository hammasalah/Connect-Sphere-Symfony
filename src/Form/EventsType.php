<?php
// src/Form/EventsType.php

namespace App\Form;

use App\Entity\Events;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
// Ajoutez cette contrainte si vous voulez valider que endTime > startTime dans le formulaire
use Symfony\Component\Validator\Constraints\GreaterThan;

class EventsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Event Name',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter an event name.']),
                ],
            ])
            ->add('description', TextType::class, [ // Ou TextareaType
                'label' => 'Description',
                 'constraints' => [
                    new NotBlank(['message' => 'Please enter a description.']),
                ],
            ])
            ->add('startTime', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Start Time',
                'html5' => true,
                'mapped' => false, // <<< AJOUTER ICI
                 'constraints' => [
                    new NotBlank(['message' => 'Please select a start time.']),
                ],
                 'attr' => ['min' => date('Y-m-d\TH:i')],
            ])
            ->add('endTime', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'End Time',
                 'html5' => true,
                 'mapped' => false, // <<< AJOUTER ICI
                 'constraints' => [
                    new NotBlank(['message' => 'Please select an end time.']),
                    // Exemple de validation pour s'assurer que endTime > startTime
                    // Fonctionne car le formulaire récupère la valeur de startTime même s'il n'est pas mappé
                    new GreaterThan([
                        'propertyPath' => 'parent.all[startTime].data', // Accède à la donnée du champ startTime
                         'message' => 'End time must be after start time.'
                    ])
                ],
                 'attr' => ['min' => date('Y-m-d\TH:i')],
            ])
             ->add('location', TextType::class, [
                 'label' => 'Location',
                  'constraints' => [
                     new NotBlank(['message' => 'Please enter a location.']),
                 ],
                 // Ce champ est mappé (par défaut mapped=true)
             ])
              ->add('points', IntegerType::class, [
                 'label' => 'Points',
                  'constraints' => [
                     new NotBlank(['message' => 'Please enter the points.']),
                 ],
                  // Ce champ est mappé
             ])
            ->add('categoryId', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Category',
                 'placeholder' => 'Choose a category',
                 'constraints' => [
                    new NotBlank(['message' => 'Please select a category.']),
                ],
                 // Ce champ est mappé
            ])
            ->add('image', FileType::class, [
                'label' => 'Event Image',
                'mapped' => false, // Déjà false, c'est correct
                'required' => false, // Ou true si obligatoire + contrainte NotBlank
                 'constraints' => [
                     // Ajoutez ici la contrainte NotBlank si required=true
                     // new NotBlank(['message' => 'Please upload an event image.']),
                     new Image([
                         'maxSize' => '5M',
                         'mimeTypes' => [
                             'image/jpeg',
                             'image/png',
                             'image/gif',
                         ],
                         'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, GIF).',
                         'maxSizeMessage' => 'The image is too large ({{ size }} {{ suffix }}). Allowed maximum size is {{ limit }} {{ suffix }}.',
                     ])
                 ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Events::class,
            // 'csrf_protection' => true, // Généralement activé par défaut
            // 'csrf_field_name' => '_token',
            // 'csrf_token_id'   => 'event_item', // ID unique pour le token CSRF
        ]);
    }
}