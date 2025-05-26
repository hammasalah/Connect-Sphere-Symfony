<?php

namespace App\Form;

use App\Entity\Jobs;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateJobFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jobTitle')
            ->add('eventTitle')
            ->add('jobLocation')
            ->add('employmentType')
            ->add('applicationDeadLine')
            ->add('minSalary')
            ->add('maxSalary')
            ->add('currency')
            ->add('jobDescreption')
            ->add('recruiterName')
            ->add('recruiterEmail')
            
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Jobs::class,
        ]);
    }
}
