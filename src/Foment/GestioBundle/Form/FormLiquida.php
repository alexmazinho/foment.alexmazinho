<?php 
// src/Foment/GestioBundle/Form/FormLiquida.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
//use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityRepository;

use Foment\GestioBundle\Entity\Pagament;

class FormPagament extends AbstractType
{
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

    	$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
    		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
    		$form = $event->getForm();
    		$pagament = $event->getData();
    		
    		if ($pagament instanceof Pagament) {
    			/*$form->add('id', 'hidden', array(
    					'mapped'	=> false,
    					'data'		=> $pagament->getId()));
    			
    			$form->add('checkbaixa', 'checkbox', array(
    					//'required'  => false,
    					//'read_only' => false,  // ??
    					'data' 		=> $pagament->anulat(),
    					'mapped'	=> false
    			));
    			 
    			$form->add('databaixa', 'date', array(
    					'widget' 		=> 'single_text',
    					'input' 		=> 'datetime',
    					'placeholder' 	=> false,
    					'read_only'		=> !$pagament->anulat(),
    					'format' 		=> 'dd/MM/yyyy',
    			));*/

    			
    		}
    	});
    	
    	$builder->add('num', 'text', array(
    			'required'  => false
    	));
    	
    	$builder->add('proveidor', 'text', array(
    			'disabled' 			=> true,
    			'required'  		=> true
	   	));
    	
    	$form->add('docencia', 'choice', array(
    			'error_bubbling'	=> true,
    			'class' => 'FomentGestioBundle:Docencia',
    			'query_builder' => function(EntityRepository $er) use ($pagament) {
    			return $er->createQueryBuilder('d')
    			->where( 'd.databaixa IS NULL AND d.proveidor = :proveidor'  )
    			->setParameter('proveidor', $pagament->getProveidor()->getId() )
    			->orderBy('d.dataentrada', 'ASC');
    			},
    			'choice_label' 		=> 'activitat.descripcio',
    			'multiple' 			=> false,
    			'required'  		=> true
    			));
    	
		$builder->add('datapagament', 'date', array(
        		'widget' 		=> 'single_text',
        		'input' 		=> 'datetime',
        		'placeholder' 	=> false,
        		'format' 		=> 'dd/MM/yyyy',
        ));

		$builder->add('concepte', 'text', array(
				'required'  => true
		));
		
		$builder->add('import', 'number', array(
				'required' 	=> true,
				'scale'		=> 2
		));
		
    }
    
    /*public function configureOptions(OptionsResolver $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\Pagament'
    	));
    }*/

    public function getName()
    {
        return 'liquidacio';
    }
    
}
