<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                "required" => true
            ])
            ->add('email', EmailType::class, [
                "required" => true
            ])
            ->add('password', RepeatedType::class, [
                "type" => PasswordType::class,
                "required" => true,
                'first_options' => ['label' => false, 'attr' => ['placeholder' => "Mot de passe"]],
                'second_options' => ['label' => false, 'attr' => ['placeholder' => "Confirmation du mot de passe"]],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
