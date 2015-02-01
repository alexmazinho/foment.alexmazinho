<?php 
// src/Foment/GestioBundle/Form/FormFacturacio.php
namespace Foment\GestioBundle\Form;
 
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

use Foment\GestioBundle\Form\FormFacturacio;
use Foment\GestioBundle\Entity\Compte;
use Foment\GestioBundle\Entity\Soci;
use Foment\GestioBundle\Entity\Facturacio;

class FormFacturacio extends AbstractType
{
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	parent::buildForm($builder, $options);
    	 
    	$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
    		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
    		$form = $event->getForm();
    		$facturacio = $event->getData();
    	
    		/* Check we're looking at the right data/form */
	    		if ($facturacio instanceof FormFacturacio) {
	    			//$facturacio = $facturacio->getData();
	    		}
	    		
	    		if ($facturacio instanceof Facturacio) {
	        
		    	}
    	});
    	
    	$builder->add('descripcio', 'text', array(
    			'required' 	=> true,
    	));
    	
    	$builder->add('datafacturacio', 'datetime', array(
    		//'read_only' 	=> true,
    		'required' 		=> true,
    		'widget' 		=> 'single_text',
    		//'mapped'		=> false,
    		'empty_value' 	=> false,
    		'format' 		=> 'dd/MM/yyyy',
    		//'data'			=> new \DateTime()
    	));
    		
    		
    	$builder->add('importactivitat', 'number', array(
    		'required' 	=> true,
    		//'mapped'	=> false,
    		'precision'	=> 2,
    		'constraints' => array(
    				new Type(array(
    						'type'    => 'numeric',
    						'message' => 'El preu ha de ser numèric.'
    				) ),
    				new GreaterThanOrEqual(array( 'value' => 0,  'message' => 'El preu no és vàlid.' ) )
    		)
    	));
    	
    	$builder->add('importactivitatnosoci', 'number', array(
    			'required' 	=> true,
    			//'mapped'	=> false,
    			'precision'	=> 2,
    			'constraints' => array(
    					new Type(array(
    							'type'    => 'numeric',
    							'message' => 'El preu no soci ha de ser numèric.'
    					) ),
    					new GreaterThanOrEqual(array( 'value' => 0,  'message' => 'El preu no soci no és vàlid.' ) )
    			)
    	));
    }

    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\Facturacio',
    	));
    }
    
    public function getName()
    {
        return 'facturacio';
    }
}
