<?php

namespace App\Form;

use App\Entity\Allergene;
use App\Entity\Plat;
use App\Repository\AllergeneRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PlatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, [
                'constraints' => [new NotBlank(), new Length(min: 2, max: 150)],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Entree' => 'entree',
                    'Plat' => 'plat',
                    'Dessert' => 'dessert',
                    'Autre' => 'autre',
                ],
                'constraints' => [new NotBlank()],
            ])
            ->add('allergenes', EntityType::class, [
                'class' => Allergene::class,
                'choice_label' => 'nom',
                'query_builder' => static fn (AllergeneRepository $repository) => $repository
                    ->createQueryBuilder('a')
                    ->orderBy('a.nom', 'ASC'),
                'multiple' => true,
                'required' => false,
                'by_reference' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Plat::class,
        ]);
    }
}
