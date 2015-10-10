<?php 
// src/Foment/GestioBundle/Form/FormSeccio.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

use Foment\GestioBundle\Entity\Seccio;
use Foment\GestioBundle\Controller\UtilsController;

class FormSeccio extends AbstractType
{
	private $options;
	
	public function __construct(array $options = null)
	{
		$this->options = $options;
	}
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

    	$anydades = date('Y');
    	if (isset($this->options['anydades'])) $anydades = $this->options['anydades'];
    	
    	$rebutsgenerats = false;
    	if (isset($this->options['rebutsgenerats'])) $rebutsgenerats = $this->options['rebutsgenerats'];

    	$anysSelectable = array(date('Y') => date('Y'));
    	if (isset($this->options['anysSelectable'])) $anysSelectable = $this->options['anysSelectable'];
    	 
    	
    	
    	$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use($anydades, $rebutsgenerats, $anysSelectable) {
    		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
    		$form = $event->getForm();
    		$seccio = $event->getData();
    		
    		/* Check we're looking at the right data/form */
    		if ($seccio instanceof Seccio) {
    			$id = $seccio->getId();
    			$import = $seccio->getQuotaAny($anydades, false);
    			$importjuvenil = $seccio->getQuotaAny($anydades, true);
    			
    			
    			$form->add('id', 'hidden', array(
    					'mapped'	=> false,
    					'data'		=> $id));
    			
    			$form->add('nom', 'text', array(
    					'required'  => true,
    					'attr' 		=> array('data-value-init' => $seccio->getNom( ))
    			));
    			
    			$form->add('ordre', 'integer', array(
    					'required' 	=> true,
   						'precision'	=> 0,
   						'attr' 		=> array('data-value-init' => $seccio->getOrdre())
    			));
    			
    			$form->add('semestral', 'checkbox', array(
    					'required'  => false,
    					'disabled' => ($id > 0),  
    					'attr' 		=> array('data-value-init' => $seccio->getSemestral())
    			));
    			
   				$form->add('facturacions', 'integer', array(
   						'required' 	=> true,
   						'precision'	=> 0,
   						'attr' 		=> array('data-value-init' => $seccio->getFacturacions())
   				));
    			
    			$form->add('fraccionat', 'checkbox', array(
    					'required'  => false,
    					'disabled' => ($id > 0),
    					'attr' 		=> array('data-value-init' => $seccio->getFraccionat())
    			));
    			
    			$form->add('exemptfamilia', 'checkbox', array(
    					'required'  => false,
    					'disabled' => ($id > 0),
    					'attr' 		=> array('data-value-init' => $seccio->getExemptfamilia())
    			));
    			
    			$form->add('quotaimport', 'number', array( 
    					'required' 	=> true,
    					'mapped'	=> false,
    					'precision'	=> 2,
    					'data'		=> $import,
    					'read_only'	=> $rebutsgenerats, 
    					'constraints' => array(
    							new NotBlank(array(	'message' => 'Cal indicar l\'import.' ) ),
    							new Type(array(
            						'type'    => 'numeric',
            						'message' => 'L\'import ha de ser numèric.'
        						) ),
    							new GreaterThanOrEqual(array( 'value' => 0,  'message' => 'L\'import no és vàlid.' ) )
    					),
    					'attr' 		=> array('data-value-init' => $import)
    			));

    			$form->add('quotaimportjuvenil', 'number', array(
    					'required' 	=> true,
    					'mapped'	=> false,
    					'precision'	=> 2,
    					'data'		=> $importjuvenil,
    					'read_only'	=> $rebutsgenerats,
    					'constraints' => array(
    							new NotBlank(array(	'message' => 'Cal indicar l\'import.' ) ),
    							new Type(array(
    									'type'    => 'numeric',
    									'message' => 'L\'import ha de ser numèric.'
    							) ),
    							new GreaterThanOrEqual(array( 'value' => 0, 'message' => 'L\'import no és vàlid.' ) )
    					),
    					'attr' 		=> array('data-value-init' => $importjuvenil) 
    			));
    			
    			$form->add('quotaany', 'choice', array(
    					'mapped'	=> false,
    					'choices'   => $anysSelectable,
    					'data'		=> $anydades,
    					'required'  => true,
    					'constraints' => array(
    							new NotBlank(array(	'message' => 'Cal indicar l\'any.' ) ),
    							new Type(array(
    									'type'    => 'integer',
    									'message' => 'Any incorrecte.'
    							) ),
    							new GreaterThanOrEqual(array( 'value' => $anydades, 'message' => 'No es poden modificar quotes passades' ) )
    					),
    					'attr' 		=> array('data-value-init' => $anydades)
    			));
    			
    			 
    			/*
    			$form->add('quotaany', 'integer', array(
    					'required' 	=> true,
    					'mapped'	=> false,
    					'read_only' => true,
    					'data'		=> $currentYear,
    					'constraints' => array(
    							new NotBlank(array(	'message' => 'Cal indicar l\'any.' ) ),
    							new Type(array(
    									'type'    => 'integer',
    									'message' => 'Any incorrecte.'
    							) ),
    							new GreaterThanOrEqual(array( 'value' => date('Y'), 'message' => 'No es poden modificar quotes passades' ) )
    					),
    					'attr' 		=> array('data-value-init' => $currentYear) 
    			));*/
    		}
    	});
    	
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
       
   		$builder->add('membre', 'hidden', array( // Select2 field
   			'required'	=> false,
   			'mapped'	=> false,
   			'attr' 		=> array('class' => '' )
   		));
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\Seccio'
    	));
    }

    public function getName()
    {
        return 'seccio';
    }
    
}
