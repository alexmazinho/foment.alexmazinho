<?php 
// src/Foment/GestioBundle/Form/FormProveidor.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Foment\GestioBundle\Entity\Proveidor;
use Foment\GestioBundle\Controller\UtilsController;


class FormProveidor extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	
    	$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
    		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
    		$form = $event->getForm();
    		$proveidor = $event->getData();
    	
    		/* Check we're looking at the right data/form */
    		if ($proveidor instanceof Proveidor) {
    			$form->add('id', 'hidden', array(
    					'mapped'	=> false,
    					'data'		=> $proveidor->getId()));
    			 
    			$poblacio = $proveidor->getPoblacio();
    			if ($poblacio == null || $poblacio == '') $poblacio = 'Barcelona';
    			$form->add('poblacio', 'hidden', array(
    					'data'	=> $poblacio
    			));
    			 
    			$provincia = $proveidor->getProvincia();
    			if ($provincia == null || $provincia == '') $provincia = 'Barcelona';
    			$form->add('provincia', 'hidden', array(
    					'data'	=> $provincia
    			));
    		}
    	});
    	
        $builder->add('raosocial', 'text', array(
        		'required'  		=> true,
        ));
        
        $builder->add('cif', 'text', array(
        		'required'  		=> false,
        ));
        
        $builder->add('telffix', 'integer', array(
        		'required'  => false,
        ));
        
        $builder->add('telfmobil', 'integer', array(
        		'required'  => false,
        ));
        
        $builder->add('correu', 'email', array(
        		'required'  => false,
        ));
        
        $builder->add('adreca', 'text', array());
        
        $builder->add('cp', 'text', array());
        
        $builder->add('observacions', 'textarea');
        
        $builder->add('cercaactivitats', 'hidden', array(
        		'mapped'	=> false
        ));
        
        $builder->add('databaixa', 'date', array(
        		//'read_only' 	=> true,
        		'widget' 		=> 'single_text',
        		'input' 		=> 'datetime',
        		'empty_value' 	=> '',
        		'required'  	=> false,
        		'format' 		=> 'dd/MM/yyyy',
        ));
        
        $builder->add('filtre', 'text', array(     			// Camps formulari de filtre
        		'required' 	=> false,
        		'attr' 		=> array('class' => 'form-control filtre-text'),
        		'mapped'	=> false
        		
        ));
        
        $builder->add('midapagina', 'choice', array(
        		'required'  => true,
        		'choices'   => UtilsController::getPerPageOptions(),
        		'attr' 		=> array('class' => 'select-midapagina'),
        		'mapped'	=> false
        ));
        // Activitats creat al PRE_SET_DATA Event
        
        
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\Proveidor'
    	));
    }

    public function getName()
    {
        return 'proveidor';
    }
    
}
