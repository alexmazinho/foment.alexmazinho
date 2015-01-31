<?php 
// src/Foment/GestioBundle/Form/FormRebut.php
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

use Foment\GestioBundle\Entity\Rebut;
use Foment\GestioBundle\Controller\UtilsController;

class FormRebut extends AbstractType
{
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
    		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
    		$form = $event->getForm();
    		$rebut = $event->getData();
    		
    		if ($rebut instanceof Rebut) {
    			$form->add('id', 'hidden', array(
    					'mapped'	=> false,
    					'data'		=> $rebut->getId()));
    			
    			$form->add('checkretornat', 'checkbox', array(
    					//'required'  => false,
    					//'read_only' => false,  // ??
    					'data' 		=> $rebut->retornat(),
    					'mapped'	=> false
    			));
    			
    			$form->add('dataretornat', 'date', array(
    					'widget' 		=> 'single_text',
    					'input' 		=> 'datetime',
    					'empty_value' 	=> false,
    					'read_only'		=> !$rebut->retornat(),
    					'format' 		=> 'dd/MM/yyyy',
    			));
    			
    			$form->add('checkbaixa', 'checkbox', array(
    					//'required'  => false,
    					//'read_only' => false,  // ??
    				'data' 		=> $rebut->anulat(),
    				'mapped'	=> false
    			));
    			
    			$form->add('databaixa', 'date', array(
        			'widget' 		=> 'single_text',
        			'input' 		=> 'datetime',
        			'empty_value' 	=> false,
    				'read_only'		=> !$rebut->anulat(),
        			'format' 		=> 'dd/MM/yyyy',
      			));	
      			  
    			$form->add('importcorreccio', 'number', array(
    				'required' 	=> true,
    				'precision'	=> 2,
    				'data' 		=> $rebut->getImport(),
    				'mapped'	=> false,
    				'constraints' => array(
    					new NotBlank(array(	'message' => 'Cal indicar l\'import.' ) ),
    					new Type(array(
    							'type'    => 'numeric',
    							'message' => 'L\'import ha de ser numèric.'
    					) ),
    					new GreaterThanOrEqual(array( 'value' => 0,  'message' => 'L\'import no és vàlid.' ) )
    				)
    			));
    			error_log('---->'.$rebut->esActivitat());
    			
    			$form->add('tipuspagament', 'choice', array(
    				'required'  	=> false,
    				'read_only' 	=> false,
    				'expanded'	 	=> false,
    				'multiple'		=> false,
    				'disabled'		=> $rebut->esActivitat(),
    				'choices'   	=> array(UtilsController::INDEX_FINESTRETA => 'finestreta', UtilsController::INDEX_DOMICILIACIO => 'domiciliació', )
    				//'data' 		=> ($soci->esPagamentFinestreta()),
    				//'mapped'	=> false
    			));
    		}
    	});
    	
    	$builder->add('deutor', 'entity', array(
    			'error_bubbling'	=> true,
    			'class' => 'FomentGestioBundle:Soci',
    			'query_builder' => function(EntityRepository $er) {
    				return $er->createQueryBuilder('s')
    				->where( 's.databaixa IS NULL')
    				->orderBy('s.cognoms, s.nom', 'ASC');
    			},
    			'property' 			=> 'nomcognoms',
    			'multiple' 			=> false,
    			'required'  		=> true
    	));
    	
    	$builder->add('num', 'text', array(
    			'required' 	=> true,
    			'read_only' => true
    	));
    	
    	$builder->add('dataemissio', 'date', array(
    			'widget' 		=> 'single_text',
    			'input' 		=> 'datetime',
    			'empty_value' 	=> false,
    			'format' 		=> 'dd/MM/yyyy',
    	));
    	
    	$builder->add('datapagament', 'date', array(
    			'widget' 		=> 'single_text',
    			'input' 		=> 'datetime',
    			'empty_value' 	=> false,
    			'format' 		=> 'dd/MM/yyyy',
    	));
    	
    	$builder->add('nouconcepte', 'text', array(
    			'required'  => true,
    			'mapped'	=> false
    	));

    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\Rebut'
    	));
    }

    public function getName()
    {
        return 'rebut';
    }
    
}
