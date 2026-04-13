<?php

namespace App\Form;

use App\Entity\CommuneLivraison;
use App\Repository\CommuneLivraisonRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomPrenomClient', TextType::class, [
                'mapped' => false,
                'data' => (string) $options['nom_prenom_client'],
                'disabled' => true,
                'label' => 'Nom / Prenom client',
            ])
            ->add('gsmClient', TextType::class, [
                'mapped' => false,
                'data' => (string) $options['gsm_client'],
                'disabled' => true,
                'label' => 'GSM client',
            ])
            ->add('adressePrestation', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 5, max: 255),
                ],
            ])
            ->add('datePrestation', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [new NotBlank()],
            ])
            ->add('heurePrestation', TimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [new NotBlank()],
            ])
            ->add('communeLivraison', EntityType::class, [
                'class' => CommuneLivraison::class,
                'choice_label' => static fn (CommuneLivraison $commune): string => sprintf(
                    '%s (%s) - %s km',
                    (string) $commune->getNom(),
                    (string) $commune->getCodePostal(),
                    (string) $commune->getDistanceKm()
                ),
                'query_builder' => static fn (CommuneLivraisonRepository $repository) => $repository
                    ->createQueryBuilder('c')
                    ->andWhere('c.actif = :actif')
                    ->setParameter('actif', true)
                    ->orderBy('c.nom', 'ASC'),
                'constraints' => [new NotBlank()],
            ])
            ->add('nbPersonnes', IntegerType::class, [
                'constraints' => [
                    new NotBlank(),
                    new GreaterThanOrEqual(1),
                ],
            ])
            ->add('pretMateriel', CheckboxType::class, [
                'required' => false,
                'label' => 'Pret de materiel',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'nom_prenom_client' => '',
            'gsm_client' => '',
            'csrf_protection' => true,
        ]);

        $resolver->setAllowedTypes('nom_prenom_client', 'string');
        $resolver->setAllowedTypes('gsm_client', 'string');
    }
}
