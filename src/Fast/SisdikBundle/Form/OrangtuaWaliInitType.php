<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OrangtuaWaliInitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('nama', null,
                        array(
                                'label_render' => true, 'required' => true,
                                'label' => 'label.name.parent.or.guardian',
                        ))
                ->add('ponsel', null,
                        array(
                                'label' => 'label.mobilephone.parent', 'required' => true,
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'label_render' => true,
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\OrangtuaWali'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_orangtuawalitype';
    }
}