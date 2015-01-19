<?php 
// src/Foment/GestioBundle/Form/FormActivitat.php
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

use Foment\GestioBundle\Entity\Activitat;
use Foment\GestioBundle\Controller\UtilsController;

abstract class FormActivitat extends AbstractType
{
	
	private $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
    		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
    		$form = $event->getForm();
    		$activitat = $event->getData();
    		
    		/* Check we're looking at the right data/form */
    		if ($activitat instanceof Activitat) {
    			
    			$form->add('id', 'hidden', array(
    					'mapped'	=> false,
    					'data'		=> $activitat->getId()));
    			
    			$form->add('tipus', 'choice', array(
    					'required'  => true,
    					'choices'   => array(Activitat::TIPUS_PUNTUAL => 'Taller o activitat puntual (un dia)', Activitat::TIPUS_ANUAL => 'Curs anual (any escolar)'),
    					'mapped'	=> false,
    					'expanded' 	=> true,
    					'multiple'	=> false,
    					'data'		=> $activitat->getTipus(),
    					'disabled'	=> true 
    			));
    			
    			$form->add('pcalculquota', 'integer', array(
    					'required' 	=> true,
    					'mapped'	=> false,
    					'data'		=> $activitat->getMaxparticipants()  // Init value
    			));
    			 
    		}
    		
    		
    	});
    	
    	$builder->add('descripcio', 'text', array(
    			'required'  => true
    	));

    	$builder->add('estimadespeses', 'number', array(
    			'required' 	=> true,
    			'precision'	=> 2
    	));
    	
    	$builder->add('quotaparticipant', 'number', array(
    			'required' 	=> true,
    			'precision'	=> 2
    	));
    	
    	$builder->add('totalhores', 'integer', array(
    			'required' 	=> true
    			
    	));
    	
    	$builder->add('maxparticipants', 'integer', array(
    			'required' 	=> true
    			 
    	));

    	$builder->add('lloc', 'textarea', array(
    			'required'  => true
    	)); 
    	
    	$builder->add('observacions', 'textarea', array(
    			'required'  => true
    	)); 
    	
    	$builder->add('participant', 'hidden', array(  // Select2 field
    			'required'	=> false, 
    			'mapped'	=> false, 
    			'attr' 		=> array('class' => '' )
    	));
    	
        $builder->add('filtre', 'text', array(
        		'required'  => true,
        		'mapped'	=> false,
        		'data'		=> (isset($this->options['filtre'])?$this->options['filtre']:''),
        		'attr' 		=> array('class' => 'form-control filtre-text')
        ));

        $builder->add('midapagina', 'choice', array(
        		'required'  => true,
        		'choices'   => UtilsController::getPerPageOptions(),
        		'mapped'	=> false,
        		'data'		=> (isset($this->options['perpage'])?$this->options['perpage']:UtilsController::DEFAULT_PERPAGE),
    			'attr' 		=> array('class' => 'select-midapagina')
        ));
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\Activitat'
    	));
    }

    public function getName()
    {
        return 'activitat';
    }
    
}
