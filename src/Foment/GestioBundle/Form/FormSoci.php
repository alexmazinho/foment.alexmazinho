<?php 
// src/Foment/GestioBundle/Form/FormSoci.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
    		
    			$form->add('numsoci', 'text', array(
    					'read_only' 	=> true,
    					'mapped'		=> false,
    					'data'			=> $numsoci 
    			));
    			
    			$form->add('databaixa', 'date', array(
    					//'read_only' 	=> true,
    					'widget' 		=> 'single_text',
    					'input' 		=> 'datetime',
    					'placeholder' 	=> '',
    					'required'  	=> false,
    					'format' 		=> 'dd/MM/yyyy',
    			));
    			
    			$form->add('vistiplau', 'checkbox', array(
    					'required'  => false,
    					'read_only' => false,  // ??
    					'data'			=> $soci->getVistiplau()
    			));
    			
    			$form->add('exempt', 'integer', array(
    					'required'  => true,
    					'read_only' => false,
    					'attr' 		=> array('data-value-init' => $soci->getExempt())
    			));

    			$form->add('quotajuvenil', 'checkbox', array(
    					'required'  => false,
    					'disabled' => $soci->esJuvenil() && !$soci->getQuotajuvenil()	// Menors desactivat, camp fals. Sempre són menors
    			));
    			
    			$form->add('familianombrosa', 'checkbox', array(
    					'required'  => false
    			));
    			
    			$form->add('quota', 'text', array(
    					'mapped'  		=> false,
    					'disabled' 		=> true,
    					'data'			=> number_format($soci->getQuotaAny(date('Y')), 2, ',', '.')
    			));
    			
    			$form->add('tipus', 'choice', array(
    					'choices'   => UtilsController::getTipusDeSoci(),
    					'data'		=> $soci->getTipus()
    			));
    			
    			$seccionssoci = array( 0 );  // Array no buit
    			foreach ($soci->getMembreDeSortedById( false ) as $m) $seccionssoci[] = $m->getSeccio()->getId();
    			
    			$form->add('membrede', 'entity', array(
    					'error_bubbling'	=> true,
    					'class' => 'FomentGestioBundle:Seccio',
    					'query_builder' => function(EntityRepository $er) use ($seccionssoci, $soci) {
    					if ($soci->getId() > 0) {
    						return $er->createQueryBuilder('s')
    						->where( $er->createQueryBuilder('s')->expr()
    						->In('s.id', ':seccionssoci'))
    						->setParameter('seccionssoci', $seccionssoci )
    							
    						->orderBy('s.ordre', 'ASC');
    					} else {
    						return $er->createQueryBuilder('s')
	    						/*->where( 's.id = 1'  ) // Foment*/
    							/*->where( 's.id = 0'  ) // Cap
	    						->orderBy('s.ordre', 'ASC');*/
    							->where( $er->createQueryBuilder('s')->expr()
    								->In('s.id', ':seccionssoci'))
    								->setParameter('seccionssoci', $seccionssoci )
    									
    								->orderBy('s.ordre', 'ASC');
    						}
    					},
    					'choice_label' 			=> 'infopreu',
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
    							
    						->orderBy('s.ordre', 'ASC');
    						
    						
    					} else {
    						return $er->createQueryBuilder('s')
    						/*->where( 's.id != 1'  )*/
    						/*->orderBy('s.ordre', 'ASC');*/
    						->where( $er->createQueryBuilder('s')->expr()
    								->notIn('s.id', ':seccionssoci'))
    								->setParameter('seccionssoci', $seccionssoci )
    									
    								->orderBy('s.ordre', 'ASC');
    					}
    				},
    				'choice_label' 			=> 'infopreu',
    				'multiple' 			=> true,
    				'required'  		=> false,
    				'mapped'			=> false,
    			));
    			
    			$form->add('membredetmp', 'hidden', array(
    					'mapped'	=> false,
    					'data'		=> $soci->getLlistaIdsSeccions()  
    			));
    			
    			$form->add('deudorrebuts', 'choice', array(
    					'required'  => true,
    					'read_only' => false,
    					'expanded' => true,
    					'multiple' => false,
    					'choices'   => array('1' => 'soci a càrrec dels rebuts', '2' => 'rebuts a càrrec d\'altre', ),
    					'data' 		=> ($soci->esDeudorDelGrup()?'1':'2'),
    					'mapped'	=> false
    			));
    			 
    			$form->add('socirebut', 'entity', array(
    					'required'  => false,
    					'class' => 'FomentGestioBundle:Soci',
    					'query_builder' => function(EntityRepository $er) {
	    					return $er->createQueryBuilder('s')
	    					->where('s.socirebut = s.id AND s.databaixa IS NULL')
	    					->orderBy('s.cognoms', 'ASC');
	    				},
    					'data' 			=> $soci->getSocirebut(),
    					'choice_label' 	=> 'numnomcognoms',
    			));
	    		
    			$form->add('socisacarrec', 'entity', array(
    					'error_bubbling'	=> true,
	   					//'read_only' 		=> true,
    					'class' 			=> 'FomentGestioBundle:Soci',
    					'query_builder' => function(EntityRepository $er) use ($soci) {
	    					return $er->createQueryBuilder('s')
	    					->where('s.socirebut = :socirebut AND s.databaixa IS NULL')
	    					->setParameter('socirebut', $soci->getId() == 0?0:$soci->getSocirebut())
	    					->orderBy('s.cognoms', 'ASC');
    					},
	    				'choice_label' 		=> 'numnomcognoms',
	    				'multiple' 			=> true,
	    				'placeholder' 		=> '',
	    				'mapped'			=> false
	    		));
    			
    			$form->add('socisdesvincular', 'hidden', array( 'mapped' => false, 'data' => '' ));
    			
    			$form->add('compte', new FormCompte() );
    			
    			$form->get('compte')->setData( $soci->getCompte() );
    			
    			// Camps només disponibles per al deutor dels rebuts
    			$form->add('descomptefamilia', 'checkbox', array(
    					'required'  => false,
    					'read_only' => !$soci->esDeudorDelGrup()
    			));
    			 
    			$form->add('tipuspagament', 'choice', array(
    					'required'  => true,
    					'read_only' => !$soci->esDeudorDelGrup(),
    					'expanded' => true,
    					'multiple' => false,
    					'choices'   => array(UtilsController::INDEX_FINESTRETA => 'finestreta', UtilsController::INDEX_DOMICILIACIO => 'domiciliació', ),
    					//'data' 		=> $pagament,
    					'data'		=> $soci->getTipuspagament(),
    					//'mapped'	=> false
    			));
    			
    			$form->add('pagamentfraccionat', 'choice', array(
    					'required'  => true,
    					'read_only' => !$soci->esDeudorDelGrup(),
    					'expanded' => true,
    					'multiple' => false,
    					'choices'   => array('0' => 'anual', '1' => 'semestral'),
    					'data' 		=> ($soci->getPagamentfraccionat())
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
	    				'choice_label' 		=> 'numnomcognoms',
	    				'multiple' 			=> false,
	    				'placeholder' 		=> '',
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
	    				'choice_label' 		=> 'nomcognoms',
	    				'multiple' 			=> false,
	    				'placeholder' 		=> '',
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
    		
    	$builder->add('num', 'hidden');
       
        // Membrede creat al PRE_SET_DATA Event
        
        // Seccions creat al PRE_SET_DATA Event
        
        $builder->add('foto', 'file', array(
        		'mapped' => false, 'attr' => array('accept' => 'image/*'))
        );
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
    	$resolver->setDefaults(array(
    			'data_class' => 'Foment\GestioBundle\Entity\Soci',
    			'cascade_validation' => true,
    	));
    }

    public function getName()
    {
        return 'soci';
    }
}
