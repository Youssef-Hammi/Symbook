<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shippingName', TextType::class, [
                'label' => 'Nom Complet',
                'attr' => ['placeholder' => 'Votre nom complet'],
            ])
            ->add('shippingAddress', TextareaType::class, [
                'label' => 'Adresse de livraison',
                'attr' => ['placeholder' => 'Votre adresse complète'],
            ])
            ->add('shippingCity', TextType::class, [
                'label' => 'Ville',
                'attr' => ['placeholder' => 'Votre ville'],
            ])
            ->add('shippingPostalCode', TextType::class, [
                'label' => 'Code Postal',
                'attr' => ['placeholder' => 'Votre code postal'],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Méthode de paiement',
                'choices' => [
                    'Payer à la livraison' => 'delivery',
                    'Payer par carte' => 'card',
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider la commande',
                'attr' => ['class' => 'btn btn-success mt-3'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
} 