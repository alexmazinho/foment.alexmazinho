<?php 
// src/Foment/GestioBundle/Form/FormApuntConcepte.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityRepository;

use Foment\GestioBundle\Controller\UtilsController;
use Foment\GestioBundle\Entity\ApuntConcepte;

class FormApuntConcepte extends AbstractType
{
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
    		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
    		$form = $event->getForm();
    		$concepte = $event->getData();
    	
    		if ($concepte instanceof ApuntConcepte) {
    			$form->add('cercaseccio', 'entity', array(
    					'mapped'	=> false,
    					'required'  => false,
    					'class' 	=> 'FomentGestioBundle:Seccio',
    					'query_builder' => function(EntityRepository $er) {
    					return $er->createQueryBuilder('s')
    					->where('s.databaixa IS NULL')
    					->orderBy('s.nom', 'ASC');
    					},
    					'property' 	=> 'nom',
    			));
    			$form->add('cercaactivitat', 'entity', array(
    					'mapped'	=> false,
    					'required'  => false,
    					'class' 	=> 'FomentGestioBundle:Activitat',
    					'query_builder' => function(EntityRepository $er) {
    					return $er->createQueryBuilder('a')
    					->where('a.databaixa IS NULL')
    					->orderBy('a.descripcio', 'ASC');
    					},
    					'property' 	=> 'descripcio',
    			));
    		}
    	});
    	
    	$builder->add('id', 'hidden', array( ));
    	
    	$builder->add('seccions', 'hidden', array( ));
    	
    	$builder->add('activitats', 'hidden', array( ));
    	
    	$builder->add('tipus', 'choice', array(
    			'required'  => true,
    			'choices'   => UtilsController::getTipusConceptesApunts()
    	));

    	$builder->add('concepte', 'text', array(
    			'required'  => true
    	));
    	
    	$builder->add('databaixa', 'datetime', array(
				//'read_only' 	=> true,
				'widget' 		=> 'single_text',
				'input' 		=> 'datetime',
				'empty_value' 	=> '',
				'required'  	=> false,
				'format' 		=> 'dd/MM/yyyy',
    	));
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\ApuntConcepte'
    	));
    }

    public function getName()
    {
        return 'concepte';
    }
    
}
