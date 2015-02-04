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

    	$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
    		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
    		$form = $event->getForm();
    		$pagament = $event->getData();
    		
    		if ($pagament instanceof Pagament) {
    			$form->add('id', 'hidden', array(
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
    					'empty_value' 	=> false,
    					'read_only'		=> !$pagament->anulat(),
    					'format' 		=> 'dd/MM/yyyy',
    			));

    			$form->add('docencia', 'entity', array(
	    			'error_bubbling'	=> true,
	    			'class' => 'FomentGestioBundle:Docencia',
	    			'query_builder' => function(EntityRepository $er) use ($pagament) {
	   					return $er->createQueryBuilder('d')
	    							->where( 'd.databaixa IS NULL AND d.proveidor = :proveidor'  )
	    							->setParameter('proveidor', $pagament->getProveidor()->getId() )
	    							->orderBy('d.dataentrada', 'ASC');
	    			},
	    			'property' 			=> 'activitat.descripcio',
	    			'multiple' 			=> false,
	    			'required'  		=> true
	   			));
    		}
    	});
    	
    	$builder->add('num', 'text', array(
    			'required'  => true
    	));
    	
    	$builder->add('proveidor', 'entity', array(
    			'error_bubbling'	=> true,
    			'class' => 'FomentGestioBundle:Proveidor',
    			'query_builder' => function(EntityRepository $er) {
   					return $er->createQueryBuilder('p')
    							->where( 'p.databaixa IS NULL'  )
    							->orderBy('p.raosocial', 'ASC');
    			},
    			'property' 			=> 'raosocial',
    			'multiple' 			=> false,
    			'required'  		=> true
	   	));
    	
		$builder->add('datapagament', 'date', array(
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
