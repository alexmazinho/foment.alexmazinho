<?php 
// src/Foment/GestioBundle/Form/FormCompte.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormCompte extends AbstractType
{
	/*protected $compte;
	
	public function __construct($compte)
	{
		$this->compte = $compte;
	}
	*/
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //$builder->add('id', 'hidden');
        
        $builder->add('titular', 'text');
        
        $builder->add('showiban', 'checkbox', array(
        		'required'  => false,
        		'read_only' => false,  // ??
        		'data' 		=> true,
        		'mapped'	=> false
        ));
        
        $builder->add('banc', 'text', array(
        		'required'  => false,
        ));
        
        $builder->add('agencia', 'text', array(
        		'required'  => false,
        ));
        
        $builder->add('dc', 'text', array(
        		'required'  => false,
        ));
        
        $builder->add('numcompte', 'text', array(
        		'required'  => false,
        ));
        
        $builder->add('iban', 'text', array(
        		'required'  => false,
        ));
        
    }

    
    public function configureOptions(OptionsResolver $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\Compte',
    	));
    }
    
    public function getName()
    {
        return 'compte';
    }
}
