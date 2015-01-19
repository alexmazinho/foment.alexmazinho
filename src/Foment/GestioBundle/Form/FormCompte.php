<?php 
// src/Foment/GestioBundle/Form/FormCompte.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityRepository;

use Foment\GestioBundle\Entity\Compte;
use Foment\GestioBundle\Entity\Soci;

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
        		'data' 		=> false,
        		'mapped'	=> false
        ));
        
        $builder->add('banc', 'integer', array(
        		'required'  => false,
        ));
        
        $builder->add('agencia', 'integer', array(
        		'required'  => false,
        ));
        
        $builder->add('dc', 'integer', array(
        		'required'  => false,
        ));
        
        $builder->add('numcompte', 'integer', array(
        		'required'  => false,
        ));
        
        $builder->add('iban', 'integer', array(
        		'required'  => false,
        ));
        
    }

    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
