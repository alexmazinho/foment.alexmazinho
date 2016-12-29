<?php 
// src/Foment/GestioBundle/Form/FormApunt.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Foment\GestioBundle\Entity\Apunt;
use Foment\GestioBundle\Controller\UtilsController;

class FormApunt extends AbstractType
{
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
    		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
    		$form = $event->getForm();
    		$apunt = $event->getData();
    		
    		if ($apunt instanceof Apunt) {
    			$form->add('id', 'hidden', array(
    					'mapped'	=> false,
    					'data'		=> $apunt->getId())
    			);
    			
    			$form->add('numapunt', 'text', array(
    					'required' 	=> true,
    					'read_only' => true,
    					'mapped'	=> false,
    					'data'		=> $apunt->getNumFormat()
    			));
    			$form->add('rebut', 'text', array(
    					'required'  => false,
    					'mapped'	=> false,
    					'data'		=> $apunt->getRebut()!=null?$apunt->getRebut()->getId():''
    			));
    		}
    	});
    	
    		
    	
    	$builder->add('dataapunt', 'datetime', array(
				//'read_only' 	=> true,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> '',
				'required'  	=> true,
				'format' 		=> 'dd/MM/yyyy HH:mm',
    	));
    	
    	$builder->add('tipus', 'choice', array(
    			'required'  => true,
    			'choices'   => array ( 'E' => 'entrada', 'S' => 'sortida'),
    			'expanded' 	=> true,
    			'multiple'	=> false
    	));

    	$builder->add('import', 'number', array(
    			'required'  => true,
    			'precision'	=> 2
    	)); 
    	
    	$builder->add('codi', 'choice', array(
    			'required'  => true,
    			'choices'   => UtilsController::getCodisComptables()
    	));
    	
    	$builder->add('concepte', 'text', array(
    			'required'  => false
    	));
    	 
    	/*$builder->add('rebut', 'entity', array(
    			'required'  => false,
    			'class' 	=> 'FomentGestioBundle:Rebut',
    			'property' 	=> 'numformat',
    			'data'		=> ''
    	));*/
    	
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\Apunt'
    	));
    }

    public function getName()
    {
        return 'apunt';
    }
    
}
