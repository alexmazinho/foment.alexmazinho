<?php 
// src/Foment/GestioBundle/Form/FormJunta.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Foment\GestioBundle\Entity\Junta;
use Foment\GestioBundle\Controller\UtilsController;

class FormJunta extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	
    	$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
    		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
    		$form = $event->getForm();
    		$junta = $event->getData();
    	
    		/* Check we're looking at the right data/form */
    		if ($junta instanceof Junta) {
    			
    			$form->add('idsoci', 'hidden', array(
    					'mapped'	=> false,
    					'attr' 		=> array('class' => 'idmembre-junta'),
    					'data'		=> $junta->getSoci()->getId()
    			));
    			
    		}
    	});
    	
        $builder->add('carrec', 'choice', array(
        		'choices'   => UtilsController::getArrayCarrecsJunta(),
        		/*'mapped'	=> false,*/
        		'attr' 		=> array('class' => 'carrecs-junta')
        ));

        $builder->add('area', 'text', array(
        		'attr' 		=> array('class' => 'areacarrec-junta'),
        		
        ));
        
        
        $builder->add('id', 'hidden', array(
        		'attr' 		=> array('class' => 'idjunta')
        ));
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\Junta'
    	));
    }

    public function getName()
    {
        return 'junta';
    }
    
}
