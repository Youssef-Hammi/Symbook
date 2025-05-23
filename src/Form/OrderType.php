<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('createdAt')
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'pending',
                    'Processing' => 'processing',
                    'Shipped' => 'shipped',
                    'Completed' => 'completed',
                    'Cancelled' => 'cancelled',
                ],
                'label' => 'Statut',
            ])
            ->add('totalPrice')
            ->add('paymentMethod')
            ->add('user')
            ->add('shippingName', TextType::class, [
                'label' => 'Nom de livraison',
            ])
            ->add('shippingAddress', TextType::class, [
                'label' => 'Adresse de livraison',
            ])
            ->add('shippingCity', TextType::class, [
                'label' => 'Ville de livraison',
            ])
            ->add('shippingPostalCode', TextType::class, [
                'label' => 'Code postal de livraison',
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
