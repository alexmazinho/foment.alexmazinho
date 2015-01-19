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

abstract class FormRebut extends AbstractType
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
    		$rebut = $event->getData();
    		
    		/* Check we're looking at the right data/form */
    		if ($rebut instanceof Rebut) {

    		}
    		
    		
    	});
    	
    	$builder->add('id', 'hidden');
    	
    	
    	$builder->add('deutor', 'text', array(
    			'required'  => true,
    			'read_only' => true
    	));

    	$builder->add('num', 'text', array(
    			'required' 	=> true,
    			'property' 	=> 'numFormat',
    			'read_only' => true
    	));
    	
    	$builder->add('total', 'text', array(
    			'required' 	=> true,
    			'read_only' => true
    	));
    	
    	$builder->add('dataemisio', 'text', array(
    			'required' 	=> true,
    			'read_only' => false
    	));
    	
    	$builder->add('datavenciment', 'text', array(
    			'required' 	=> false,
    			'read_only' => false
    	));
    	
    	$builder->add('dataretornat', 'text', array(
    			'required' 	=> false,
    			'read_only' => false
    	));
    	
    	$builder->add('tipuspagament', 'choice', array(
    			'required'  => false,
    			'read_only' => false,
    			'expanded' => false,
    			'multiple' => false,
    			'choices'   => array(UtilsController::INDEX_FINESTRETA => 'finestreta', UtilsController::INDEX_DOMICILIACIO => 'domiciliació', )
    			//'data' 		=> ($soci->esPagamentFinestreta()),
    			//'mapped'	=> false
    	));
    	
    	$builder->add('datapagament', 'text', array(
    			'required' 	=> false,
    			'read_only' => false
    	));
    	
    	$builder->add('databaixa', 'text', array(
    			'required' 	=> false,
    			'read_only' => false
    	));
    	
    	$builder->add('importcorrecció', 'number', array(
    			'required' 	=> true,
    			'precision'	=> 2
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
