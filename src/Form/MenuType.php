<?php

namespace App\Form;

use App\Entity\Menu;
use App\Entity\Plat;
use App\Repository\PlatRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class MenuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', null, [
                'constraints' => [new NotBlank(), new Length(min: 3, max: 150)],
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [new NotBlank(), new Length(min: 10)],
            ])
            ->add('theme', null, [
                'constraints' => [new NotBlank(), new Length(max: 100)],
            ])
            ->add('regime', null, [
                'constraints' => [new NotBlank(), new Length(max: 100)],
            ])
            ->add('prixMinCentimes', IntegerType::class, [
                'label' => 'Prix minimum (centimes)',
                'constraints' => [new NotBlank(), new GreaterThanOrEqual(0)],
            ])
            ->add('personnesMin', IntegerType::class, [
                'label' => 'Nombre minimum de personnes',
                'constraints' => [new NotBlank(), new GreaterThanOrEqual(1)],
            ])
            ->add('stock', IntegerType::class, [
                'constraints' => [new NotBlank(), new GreaterThanOrEqual(0)],
            ])
            ->add('conditionsParticulieres', TextareaType::class, [
                'required' => false,
            ])
            ->add('actif', CheckboxType::class, [
                'required' => false,
            ])
            ->add('plats', EntityType::class, [
                'class' => Plat::class,
                'choice_label' => static fn (Plat $plat): string => sprintf('%s (%s)', (string) $plat->getNom(), (string) $plat->getType()),
                'query_builder' => static fn (PlatRepository $repository) => $repository
                    ->createQueryBuilder('p')
                    ->orderBy('p.nom', 'ASC'),
                'multiple' => true,
                'required' => false,
                'by_reference' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Menu::class,
        ]);
    }
}
