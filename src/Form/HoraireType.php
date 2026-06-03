<?php

namespace App\Form;

use App\Entity\Horaire\HoraireInter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HoraireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Prépare les choix de familles : ['Dupont (GE)' => 'FAM001', …]
        $choixFamilles = [];
        foreach ($options['familles'] as $fam) {
            $label = $fam['Famille_Famille'] . ' (' . $fam['typePresta'] . ')';
            $choixFamilles[$label] = $fam['numero_Famille'] . '|' . $fam['typePresta'] . '|' . $fam['Famille_Famille'];
        }

        $builder
            ->add('famillePresta', ChoiceType::class, [
                'label'    => 'Famille',
                'choices'  => $choixFamilles,
                'mapped'   => false, // On gère manuellement numFam + nomFam + typePresta
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('datePresta', DateType::class, [
                'label'   => 'Date de la prestation',
                'widget'  => 'single_text',
                'attr'    => ['class' => 'form-control', 'max' => date('Y-m-d')],
            ])
            ->add('heureDebutPresta', TimeType::class, [
                'label'  => 'Heure de début',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control'],
            ])
            ->add('heureFinPresta', TimeType::class, [
                'label'  => 'Heure de fin',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control'],
            ])
            ->add('kmAvecEnfant', NumberType::class, [
                'label'    => 'Kilomètres avec enfant(s)',
                'required' => false,
                'scale'    => 1,
                'attr'     => ['class' => 'form-control', 'placeholder' => '0.0', 'min' => '0'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HoraireInter::class,
            'familles'   => [],
        ]);
    }
}
