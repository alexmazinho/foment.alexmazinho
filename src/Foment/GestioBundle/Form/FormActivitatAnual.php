<?php 
// src/Foment/GestioBundle/Form/FormActivitatAnual.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

use Foment\GestioBundle\Entity\ActivitatAnual;
use Foment\GestioBundle\Form\FormActivitat;
use Foment\GestioBundle\Form\FormFacturacio;
use Foment\GestioBundle\Controller\UtilsController;


class FormActivitatAnual extends FormActivitat
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	parent::buildForm($builder, $options);
    	
    	$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
    		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
    		$form = $event->getForm();
    		$activitat = $event->getData();
    		
    		/* Check we're looking at the right data/form */
    		if ($activitat instanceof ActivitatAnual) { 
    			
    			$form->add('curs', 'datetime', array(
    					//'read_only' 	=> true,
    					'widget' 		=> 'single_text',
    					//'input' 		=> 'string',
    					'empty_value' 	=> false,
    					'format' 		=> 'dd/MM/yyyy',
    					//'data'			=> $datainici
    			));
				
    			$form->add('curs', 'choice', array(
    					'required'  => false,
    					'choices'   => UtilsController::getCursosCreables(),	// 2015-16 ...
    					'data'		=> $activitat->getCurs(),
    					'expanded' 	=> false,
    					'multiple'	=> false,
    					'empty_value' 	=> false,
    			));
    			
    			// Setmanal
    			$setmanaCompleta = $activitat->getDadesDiesSetmanal();
    			
    			$form->add('diessetmana', 'choice', array(
    					'required'  => true,
    					'choices'   => UtilsController::getDiesSetmana(),	// dilluns, dimarts...
    					'mapped'	=> false,
    					'expanded' 	=> true,
    					'multiple'	=> true,
    					'data'		=> $activitat->getDiesSetmanal()
    			));
    			$form->add('dlhorainici', 'time', array(
    					'input'  => 'datetime', // o string
    					'widget' => 'single_text', // choice, text, single_text
    					'mapped'	=> false,
    					'attr' 		=> array('class' => 'select-hora form-control'),
    					'data'		=> $setmanaCompleta[UtilsController::INDEX_DILLUNS]['hora']
    			));
    			$form->add('dmhorainici', 'time', array(
    					'input'  => 'datetime', // o string
    					'widget' => 'single_text', // choice, text, single_text
    					'mapped'	=> false,
    					'attr' 		=> array('class' => 'select-hora form-control'),
    					'data'		=> $setmanaCompleta[UtilsController::INDEX_DIMARTS]['hora']
    			));
    			$form->add('dxhorainici', 'time', array(
    					'input'  => 'datetime', // o string
    					'widget' => 'single_text', // choice, text, single_text
    					'mapped'	=> false,
    					'attr' 		=> array('class' => 'select-hora form-control'),
    					'data'		=> $setmanaCompleta[UtilsController::INDEX_DIMECRES]['hora']
    			));
    			$form->add('djhorainici', 'time', array(
    					'input'  => 'datetime', // o string
    					'widget' => 'single_text', // choice, text, single_text
    					'mapped'	=> false,
    					'attr' 		=> array('class' => 'select-hora form-control'),
    					'data'		=> $setmanaCompleta[UtilsController::INDEX_DIJOUS]['hora']
    			));
    			$form->add('dvhorainici', 'time', array(
    					'input'  => 'datetime', // o string
    					'widget' => 'single_text', // choice, text, single_text
    					'mapped'	=> false,
    					'attr' 		=> array('class' => 'select-hora form-control'),
    					'data'		=> $setmanaCompleta[UtilsController::INDEX_DIVENDRES]['hora']
    			));
    			
    			$form->add('dlhorafinal', 'time', array(
    					'input'  => 'datetime', // o string
    					'widget' => 'single_text', // choice, text, single_text
    					'mapped'	=> false,
    					'attr' 		=> array('class' => 'select-hora form-control'),
    					'data'		=> $setmanaCompleta[UtilsController::INDEX_DILLUNS]['final']
    			));
    			$form->add('dmhorafinal', 'time', array(
    					'input'  => 'datetime', // o string
    					'widget' => 'single_text', // choice, text, single_text
    					'mapped'	=> false,
    					'attr' 		=> array('class' => 'select-hora form-control'),
    					'data'		=> $setmanaCompleta[UtilsController::INDEX_DIMARTS]['final']
    			));
    			$form->add('dxhorafinal', 'time', array(
    					'input'  => 'datetime', // o string
    					'widget' => 'single_text', // choice, text, single_text
    					'mapped'	=> false,
    					'attr' 		=> array('class' => 'select-hora form-control'),
    					'data'		=> $setmanaCompleta[UtilsController::INDEX_DIMECRES]['final']
    			));
    			$form->add('djhorafinal', 'time', array(
    					'input'  => 'datetime', // o string
    					'widget' => 'single_text', // choice, text, single_text
    					'mapped'	=> false,
    					'attr' 		=> array('class' => 'select-hora form-control'),
    					'data'		=> $setmanaCompleta[UtilsController::INDEX_DIJOUS]['final']
    			));
    			$form->add('dvhorafinal', 'time', array(
    					'input'  => 'datetime', // o string
    					'widget' => 'single_text', // choice, text, single_text
    					'mapped'	=> false,
    					'attr' 		=> array('class' => 'select-hora form-control'),
    					'data'		=> $setmanaCompleta[UtilsController::INDEX_DIVENDRES]['final']
    			));
    			
    		}
    	});
    	
    	// Camps relacionats amb el calendari
    	/* 3 opcions
    	 * 
    	 * Data inici i data final del periode del curs
    	 * 
    	 * semanal dl, dm, dx, dj, dv amb horari per cada dia
    	 * 
    	 * mensual  selector primer/segon/tercer/quart 
    	 *          selector dl, dm, dx, dj, dv 
    	 *  
    	 * per sessions Indicar dia / hora un a un => anar afegint al calendari en forma de llista
    	 *   
    	 */
    	
    	$builder->add('tipusprogramacio', 'choice', array(
    		'required'  => true,
    		'choices'   => UtilsController::getTipusProgramacions(),	// Per sessions, setmanal,mensual => radio
    		'mapped'	=> false,
    		'expanded' 	=> true,
    		'multiple'	=> false,
    		'data'		=> UtilsController::INDEX_PROG_SETMANAL
    	));
    	
    	$builder->add('setmanal', 'hidden', array());
    	$builder->add('mensual', 'hidden', array());
    	$builder->add('persessions', 'hidden', array());
    	
    	 
    	//...
    	
    	// Mensual 
    	$builder->add('setmanadelmes', 'choice', array(
    			'required'  => false,
    			'choices'   => UtilsController::getDiesDelMes(),	// select primer, segon...
    			'mapped'	=> false,
    			'expanded' 	=> false,
    			'multiple'	=> false,
    	));
    	
    	$builder->add('diadelmes', 'choice', array(
    			'required'  => false,
    			'choices'   => UtilsController::getDiesSetmana(),	// select dilluns, dimarts...
    			'mapped'	=> false,
    			'expanded' 	=> false,
    			'multiple'	=> false,
    	));
    	$builder->add('horainicidiadelmes', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'mapped'	=> false,
    			'attr' 		=> array('class' => 'select-hora form-control')
    	));
    	$builder->add('horafinaldiadelmes', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'mapped'	=> false,
    			'attr' 		=> array('class' => 'select-hora form-control')
    	));
    	
    	// per sessions
    	$builder->add('datahorasessio', 'text', array( 'mapped'	=> false, ) );
    	
    	$builder->add('horafinalsessio', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'mapped'	=> false,
    			'attr' 		=> array('class' => 'select-hora form-control')
    	));
    		
    	// Camps relacionats amb la docència
		// La llista de docents del curs serà una taula de lectura només
	    	
    	// select2 render
    	$builder->add('cercardocent', 'entity', array(
    		'error_bubbling'	=> true,
    		'read_only' 		=> false,
    		'mapped'			=> false,
    		'class' 			=> 'FomentGestioBundle:Proveidor',
    		'query_builder' => function(EntityRepository $er) {
    			return $er->createQueryBuilder('p')
    			->orderBy('p.raosocial', 'ASC');
    		},
    		'property' 			=> 'raosocial',
    		'multiple' 			=> false,
    		'empty_value' 		=> ''
   		));
    		 
    	
    	$builder->add('hores', 'number', array(
    			'required' 	=> false,
    			'mapped'	=> false,
    			'precision'	=> 0,
    			'constraints' => array(
    					new Type(array(
    							'type'    => 'numeric',
    							'message' => 'El nombre d\'hores ha de ser numèric.'
    					) ),
    					new GreaterThanOrEqual(array( 'value' => 0,  'message' => 'Les hores han de ser positives' ) )
    			)
    	));
    	
    	$builder->add('preuhora', 'number', array(
    			'required' 	=> false,
    			'mapped'	=> false,
    			'precision'	=> 2,
    			'constraints' => array(
    					new Type(array(
    							'type'    => 'numeric',
    							'message' => 'El preu ha de ser numèric.'
    					) ),
    					new GreaterThanOrEqual(array( 'value' => 0,  'message' => 'El preu no és vàlid.' ) )
    			)
    	));
    	
    	$builder->add('preutotal', 'number', array(
    		'required' 	=> true,
    		'mapped'	=> false,
    		'precision'	=> 2,
    		'grouping'	=> true,
    		'constraints' => array(
    				new Type(array(
    						'type'    => 'numeric',
    						'message' => 'L\'import ha de ser numèric.'
    				) ),
    				new GreaterThanOrEqual(array( 'value' => 0,  'message' => 'L\'import no és vàlid.' ) )
    		)
    	));
    	
    	$builder->add('docenciestmp', 'hidden', array(
    			'required' 	=> true,
    			'mapped'	=> false,
    	));
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\ActivitatAnual'
    	));
    }

    public function getName()
    {
        return 'activitatanual';
    }
}
