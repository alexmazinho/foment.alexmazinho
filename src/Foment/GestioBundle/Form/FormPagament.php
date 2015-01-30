<?php 
// src/Foment/GestioBundle/Form/FormPagament.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Doctrine\ORM\EntityRepository;

use Foment\GestioBundle\Entity\Pagament;
use Foment\GestioBundle\Controller\UtilsController;

class FormPagament extends AbstractType
{
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

    	/*	
    	$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
    		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
    		$form = $event->getForm();
    		$pagament = $event->getData();
    		
    		if ($pagament instanceof Pagament) {

    		}
    	});*/
    	
    	
    	$builder->add('id', 'hidden', array(
    			'required'  => true
    	));
    		
    	$builder->add('num', 'text', array(
    			'required'  => true
    	));
    	
    	$builder->add('proveidor', 'entity', array(
    			'error_bubbling'	=> true,
    			'class' => 'FomentGestioBundle:Proveidor',
    			'query_builder' => function(EntityRepository $er) {
   					return $er->createQueryBuilder('p')
    							->where( 'p.databaixa != 1'  )
    							->orderBy('p.raosocial', 'ASC');
    			},
    			'property' 			=> 'raosocial',
    			'multiple' 			=> false,
    			'required'  		=> true
	   	));
    	
		$builder->add('datapagament', 'date', array(
        		'read_only' 	=> true,
        		'widget' 		=> 'single_text',
        		'input' 		=> 'datetime',
        		'empty_value' 	=> false,
        		'format' 		=> 'dd/MM/yyyy',
        ));

		$builder->add('concepte', 'text', array(
				'required'  => true
		));
		
		$builder->add('import', 'number', array(
				'required' 	=> true,
				'precision'	=> 2
		));
		
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\Pagament'
    	));
    }

    public function getName()
    {
        return 'pagament';
    }
    
}
