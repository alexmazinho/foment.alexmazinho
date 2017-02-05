<?php 
// src/Foment/GestioBundle/Form/FormApuntConcepte.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Foment\GestioBundle\Controller\UtilsController;

class FormApuntConcepte extends AbstractType
{
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->add('id', 'hidden', array( ));
    	
    	$builder->add('tipus', 'choice', array(
    			'required'  => true,
    			'choices'   => UtilsController::getTipusConceptesApunts()
    	));

    	$builder->add('concepte', 'text', array(
    			'required'  => true
    	));
    	
    	$builder->add('databaixa', 'datetime', array(
				//'read_only' 	=> true,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> '',
				'required'  	=> false,
				'format' 		=> 'dd/MM/yyyy',
    	));
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\ApuntConcepte'
    	));
    }

    public function getName()
    {
        return 'concepte';
    }
    
}
