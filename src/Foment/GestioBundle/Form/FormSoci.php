<?php 
// src/Foment/GestioBundle/Form/FormSoci.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityRepository;

use Foment\GestioBundle\Form\FormCompte;
use Foment\GestioBundle\Entity\Soci;
use Foment\GestioBundle\Controller\UtilsController;


class FormSoci extends FormPersona
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	parent::buildForm($builder, $options);
    	$builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
    		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
    		
    		$form = $event->getForm();
    		$soci = $event->getData();
    		
    		// Check we're looking at the right data/form 
    		if ($soci instanceof Soci) {
    			$numsoci = $soci->getNumSoci();
    		
    			$form->add('num', 'text', array(
    					'mapped'  		=> false,
    					'disabled' 		=> true,
    					'data'			=> $numsoci 
    			));
    			
    			$form->add('vistiplau', 'checkbox', array(
    					'required'  => false,
    					'read_only' => false,  // ??
    					'data'			=> $soci->getVistiplau()
    			));
    			
    			$form->add('exempt', 'checkbox', array(
    					'required'  => false,
    					'read_only' => false
    			));

    			$form->add('quota', 'text', array(
    					'mapped'  		=> false,
    					'disabled' 		=> true,
    					'data'			=> number_format($soci->getQuotaAny(date('Y')), 2, ',', '.')
    			));
    			
    			$form->add('tipus', 'choice', array(
    					'choices'   => Soci::getTipusDeSoci(),
    					'data'		=> $soci->getTipus()
    			));
    			
    			$seccionssoci = array( 0 );  // Array no buit
    			foreach ($soci->getSeccionsSortedById() as $m) $seccionssoci[] = $m->getId();
    			
    			$form->add('membrede', 'entity', array(
    					'error_bubbling'	=> true,
    					'class' => 'FomentGestioBundle:Seccio',
    					'query_builder' => function(EntityRepository $er) use ($seccionssoci, $soci) {
    					if ($soci->getId()) {
    						return $er->createQueryBuilder('s')
    						->where( $er->createQueryBuilder('s')->expr()
    						->In('s.id', ':seccionssoci'))
    						->setParameter('seccionssoci', $seccionssoci )
    							
    						->orderBy('s.id', 'ASC');
    					} else {
    						return $er->createQueryBuilder('s')
	    						->where( 's.id = 1'  ) // Foment
	    						->orderBy('s.id', 'ASC');
    						}
    					},
    					'property' 			=> 'infopreu',
    					'multiple' 			=> true,
    					'required'  		=> true,
    					'mapped'			=> false
    			));
    			
    			$form->add('seccions', 'entity', array(
    				//'error_bubbling'	=> true,
    				'class' => 'FomentGestioBundle:Seccio',
    				'query_builder' => function(EntityRepository $er) use ($seccionssoci, $soci) {
    					
    					if ($soci->getId()) {
    						return $er->createQueryBuilder('s')
    						->where( $er->createQueryBuilder('s')->expr()
    						->notIn('s.id', ':seccionssoci'))
    						->setParameter('seccionssoci', $seccionssoci )
    							
    						->orderBy('s.id', 'ASC');
    						
    						
    					} else {
    						return $er->createQueryBuilder('s')
    						->where( 's.id != 1'  )
    							
    						->orderBy('s.id', 'ASC');
    					}
    				},
    				'property' 			=> 'infopreu',
    				'multiple' 			=> true,
    				'required'  		=> false,
    				'mapped'			=> false,
    			));
    			
    			$form->add('membredeadded', 'hidden', array( 
    					'mapped'	=> false,
    					'data'		=> ($soci->getId() > 0?'':1)  // Foment per defecte socis nous
    			));  // Els select només envien les opcions seleccionades
    			$form->add('seccionsremoved', 'hidden', array(
    					'mapped'	=> false,
    					'data'		=> '' 
    			));
    			
    			$form->add('deudorrebuts', 'choice', array(
    					'required'  => true,
    					'read_only' => false,
    					'expanded' => true,
    					'multiple' => false,
    					'choices'   => array('1' => 'Soci a càrrec dels rebuts', '2' => 'Rebuts a càrrec d\'altre', ),
    					'data' 		=> ($soci->esDeudorDelGrup()?'1':'2'),
    					'mapped'	=> false
    			));
    			 
    			$form->add('socirebut', 'entity', array(
    					'required'  => false,
    					'class' => 'FomentGestioBundle:Soci',
    					'query_builder' => function(EntityRepository $er) {
    						return $er->createQueryBuilder('s')
    						->where('s.socirebut = s.id')
    						->orderBy('s.cognoms', 'ASC');
    					},
    					'data' 		=> ($soci->getSocirebut()),
    					'property' 	=> 'nomcognoms',
    					
    					//'mapped'	=> false
    			));

    			$form->add('compte', new FormCompte() );
    			
    			$form->get('compte')->setData( $soci->getCompte() );
    			
    			
    			// Camps només disponibles per al deutor dels rebuts
    			$form->add('descomptefamilia', 'checkbox', array(
    					'required'  => false,
    					'read_only' => !$soci->esDeudorDelGrup()
    			));
    			 
    			
    			$form->add('pagamentfinestreta', 'choice', array(
    					'required'  => true,
    					'disabled' => !$soci->esDeudorDelGrup(),
    					'expanded' => true,
    					'multiple' => false,
    					'choices'   => array(UtilsController::INDEX_FINESTRETA => 'finestreta', UtilsController::INDEX_DOMICILIACIO => 'domiciliació', ),
    					'data' 		=> $soci->getTipusPagament(),
    					'mapped'	=> false
    			));
    			
    			$form->add('pagamentfraccionat', 'choice', array(
    					'required'  => true,
    					'read_only' => !$soci->esDeudorDelGrup(),
    					'expanded' => true,
    					'multiple' => false,
    					'choices'   => array('0' => 'anual', '1' => 'semestral'),
    					'data' 		=> ($soci->getPagamentfraccionat())
    			));
    			
    			//$compte = $this->compte;
    			$form->add('socisacarrec', 'entity', array(
    				'error_bubbling'	=> true,
    				'read_only' 		=> true,
    				'class' 			=> 'FomentGestioBundle:Soci',
    				'query_builder' => function(EntityRepository $er) use ($soci) {
    					return $er->createQueryBuilder('s')
    					->where('s.socirebut = :socirebut AND s.databaixa IS NULL')
    					->setParameter('socirebut', $soci->getSocirebut())
    					->orderBy('s.cognoms', 'ASC');
    				},
    				'property' 			=> 'nomcognoms',
    				'multiple' 			=> true,
    				'empty_value' 		=> '',
    				//'mapped'			=> false
    			));
    			
    			// Avaladors
    			$avaladors = $soci->getAvaladors();
    			$form->add('avalador1', 'entity', array(
    					'error_bubbling'	=> true,
	    				'read_only' 		=> true,
	    				'class' 			=> 'FomentGestioBundle:Soci',
	    				'query_builder' => function(EntityRepository $er) use ($soci) {
	    					return $er->createQueryBuilder('s')
	    					->where('s.databaixa IS NULL')
	    					->orderBy('s.cognoms', 'ASC');
	    				},
	    				'property' 			=> 'nomcognoms',
	    				'multiple' 			=> false,
	    				'empty_value' 		=> '',
	    				'mapped'			=> false,
	    				'data' 		=> (isset($avaladors[0])?$avaladors[0]:'')
    			));
    			
    			$form->add('avalador2', 'entity', array(
    					'error_bubbling'	=> true,
	    				'read_only' 		=> true,
	    				'class' 			=> 'FomentGestioBundle:Soci',
	    				'query_builder' => function(EntityRepository $er) use ($soci) {
	    					return $er->createQueryBuilder('s')
	    					->where('s.databaixa IS NULL')
	    					->orderBy('s.cognoms', 'ASC');
	    				},
	    				'property' 			=> 'nomcognoms',
	    				'multiple' 			=> false,
	    				'empty_value' 		=> '',
	    				'mapped'			=> false,
	    				'data' 		=> (isset($avaladors[1])?$avaladors[1]:'')
    			));
    			
    		}
    	});
       
    	
    	$builder->add('soci', 'checkbox', array(
    			'required'  => false,
    			'read_only' => false,  // ??
    			'data' 		=> true,
    			'mapped'	=> false
    	));
    		
       
        
        
        
        
        
       
        // Membrede creat al PRE_SET_DATA Event
        
        // Seccions creat al PRE_SET_DATA Event
        
        /*
        $builder->add('professio', 'entity', array('class' => 'FomentGestioBundle:AuxProfessio',
        		'property' 			=> 'descripcio',
        		'multiple' 			=> false,
        		'required'  		=> false,
        		'preferred_choices' => array(),
        		'empty_value' 		=> ' ... professió ',
        ));
        
        $builder->add('idioma', 'choice', array(
        		'choices'   => array( 1 => 'català', 2 => 'castellà', 3 => 'català i castellà' )
        ));
        
        //protected $datavistiplau;
        //protected $dataalta;
        //protected $avalats;  // Socis als quals avala
        //protected $avaladors;   // Socis que l'avalen
         
        //   protected $databaixa;
        //   protected $motiu;
        
        $builder->add('observacions', 'textarea', array(
        		'error_bubbling'	=> true
        ));
        */
        
        $builder->add('foto', 'file', array(
        		'mapped' => false, 'attr' => array('accept' => 'image/*'))
        );
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\Soci'
    	));
    }

    public function getName()
    {
        return 'soci';
    }
}
