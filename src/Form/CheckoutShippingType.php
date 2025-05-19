<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CheckoutShippingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shippingName', TextType::class, [
                'label' => 'Nom complet',
                'required' => true,
            ])
            ->add('shippingAddress', TextType::class, [
                'label' => 'Adresse',
                'required' => true,
            ])
            ->add('shippingCity', TextType::class, [
                'label' => 'Ville',
                'required' => true,
            ])
            ->add('shippingPostalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => true,
            ])
            ->add('shippingPhone', TextType::class, [
                'label' => 'Numéro de téléphone',
                'required' => false, // Phone might be optional
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Passer la commande',
                'attr' => ['class' => 'btn btn-success btn-lg mt-3'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
} 