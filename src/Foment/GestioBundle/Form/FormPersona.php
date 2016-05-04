<?php 
// src/Foment/GestioBundle/Form/FormPersona.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Foment\GestioBundle\Entity\Persona;


class FormPersona extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	
    	$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
    		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
    		$form = $event->getForm();
    		$persona = $event->getData();
    		
    		/* Check we're looking at the right data/form */
    		if ($persona instanceof Persona) {
    			$form->add('id', 'hidden', array(
    					'mapped'	=> false,
    					'data'		=> $persona->getId()));
    			
    			$form->add('activitatstmp', 'hidden', array(
    					'mapped'	=> false,
    					'data'		=> implode(',',$persona->getActivitatsIds() ) ) );

    			$form->add('soci', 'checkbox', array(
    					//'required'  => false,
    					//'read_only' => false,  // ??
    					'data' 		=> $persona->esSociVigent(),
    					'mapped'	=> false
    			));
    			
    			$sexe = $persona->getSexe();
    			if ($sexe != 'H' && $sexe != 'D') $sexe = 'H';
    			$form->add('sexe', 'choice', array(
    					'choices'   => array( 'H' => 'Home', 'D' => 'Dona' ),
    					'multiple' 	=> false,
    					'expanded'	=> true,
    					'data'		=> $sexe,
    			));
    			
    			$poblacio = $persona->getPoblacio();
    			if ($poblacio == null || $poblacio == '') $poblacio = 'Barcelona';
    			$form->add('poblacio', 'hidden', array(
    					'data'	=> $poblacio
    			));
    			
    			$provincia = $persona->getProvincia();
    			if ($provincia == null || $provincia == '') $provincia = 'Barcelona';
    			$form->add('provincia', 'hidden', array(
    					'data'	=> $provincia
    			));
    		}
    	});
    	
        $builder->add('nom', 'text', array(
        		'required'  		=> true,
        ));
        
        $builder->add('cognoms', 'text', array(
        		'required'  		=> true,
        ));

        $builder->add('datanaixement', 'date', array(
        		//'read_only' 	=> true,
        		'widget' 		=> 'single_text',
        		'input' 		=> 'datetime',
        		'empty_value' 	=> false,
        		'format' 		=> 'dd/MM/yyyy',
        ));
        
        $builder->add('llocnaixement', 'text', array(
        		'required'  		=> false,
        ));
        
        $builder->add('dni', 'text', array(
        ));
        
        $builder->add('telffix', 'integer', array(
        		'required'  => false,
        ));
        
        $builder->add('telfmobil', 'integer', array(
        		'required'  => false,
        ));
        
        $builder->add('notacontacte', 'text');
        
        $builder->add('correu', 'email', array(
        		'required'  => false,
        ));
        
        $builder->add('adreca', 'text', array());
        
        $builder->add('cp', 'text', array());
        
        $builder->add('observacions', 'textarea');
        
        $builder->add('cercaactivitats', 'hidden', array(
        		'mapped'	=> false
        ));
        
        // Activitats creat al PRE_SET_DATA Event
        
        
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\Persona'
    	));
    }

    public function getName()
    {
        return 'persona';
    }
    
}
