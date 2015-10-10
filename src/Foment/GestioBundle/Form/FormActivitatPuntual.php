<?php 
// src/Foment/GestioBundle/Form/FormActivitatPuntual.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Foment\GestioBundle\Entity\ActivitatPuntual;
use Foment\GestioBundle\Form\FormActivitat;


class FormActivitatPuntual extends FormActivitat 
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	parent::buildForm($builder, $options);
    	
    	$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
    		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
    		$form = $event->getForm();
    		$activitat = $event->getData();
    	
    		/* Check we're looking at the right data/form */
    		if ($activitat instanceof ActivitatPuntual) {
				$form->add('dataactivitat', 'datetime', array(
	        		//'read_only' 	=> true,
	        		'widget' 		=> 'single_text',
	        		//'input' 		=> 'string',
	        		'empty_value' 	=> false,
	        		'format' 		=> 'dd/MM/yyyy HH:mm',
					'data'			=> $activitat->getDataactivitat() 
		        ));    	
				
    		}
    	});
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\ActivitatPuntual'
    	));
    }

    public function getName()
    {
        return 'puntual';
    }
}
