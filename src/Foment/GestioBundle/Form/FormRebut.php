<?php
// src/Foment/GestioBundle/Form/FormRebut.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Doctrine\ORM\EntityRepository;
use Foment\GestioBundle\Entity\Rebut;
use Foment\GestioBundle\Controller\UtilsController;
use Foment\GestioBundle\Entity\Facturacio;
use Foment\GestioBundle\Entity\Activitat;

class FormRebut extends AbstractType implements EventSubscriberInterface {
	
	// Mètode per carregar deutors en funció de la selecció de l'activitat o la secció
	public function deutorsLoad(FormInterface $form, $deutor = null, $deutors = array()) {
	
		if (count($deutors) == 0) {
			$form->add ( 'deutor', 'entity', array (
					'error_bubbling' => true,
					'class' => 'FomentGestioBundle:Persona',
					'choices' => $deutors,
					'data' => $deutor,
					'property' => 'nomcognoms',
					'multiple' => false,
					'required' => true,
					'empty_data' => null,
					//'disabled' => true,
			));
		} else {
			$form->add ( 'deutor', 'entity', array (
					'error_bubbling' => true,
					'class' => 'FomentGestioBundle:Persona',
					'choices' => $deutors,
					'data' => $deutor,
					'property' => 'nomcognoms',
					'multiple' => false,
					'required' => true,
					'empty_data' => null
			) );
		}
	
	}
	
	// Mètode per carregar facturacions en funció de la selecció de l'activitat o la secció
	public function facturacionsLoad(FormInterface $form, $facturacio = null, $facturacions = array()) {
		
		if (count($facturacions) == 0) {
			$form->add ( 'facturacio', 'entity', array (
				'class' => 'FomentGestioBundle:Facturacio',
				'property' => 'descripcio',
				'choices' => $facturacions,
				'data' => $facturacio,
				'multiple' => false,
				'required' => false,
				//'disabled' => true,
				'empty_data' => null
			));
		} else {
			$form->add ( 'facturacio', 'entity', array (
				'class' => 'FomentGestioBundle:Facturacio',
				'property' => 'descripcio',
				'choices' => $facturacions,
				'data' => $facturacio,
				'multiple' => false,
				'required' => false,
				'empty_data' => null
			));
		}
	}
	
	// Mètode del subscriptor => implements EventSubscriberInterface
	public static function getSubscribedEvents() {
		
		// Tells the dispatcher that you want to listen on the form.pre_set_data
		// event and that the preSetData method should be called.
		return array (
				FormEvents::POST_SUBMIT => array('postSubmitData', 900),  // Desactiva validació
				FormEvents::SUBMIT => array('submitData', 900),
				FormEvents::PRE_SET_DATA => 'preSetData' 
		);
	}
	public function preSetData(FormEvent $event) {
		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
		$form = $event->getForm ();
		$rebut = $event->getData ();
		$facturacions = array();
		$deutors = array();
		
		
		if ($rebut instanceof Rebut) {
			$form->add ( 'id', 'hidden', array (
					'mapped' => false,
					'data' => $rebut->getId () 
			) );
			
			// Rebut associat a una activitat o a una secció?
			if ($rebut->esSeccio ()) {
				$seccio = null;
				
				$form->add ( 'origen', 'entity', array (
						'error_bubbling' => true,
						'class' => 'FomentGestioBundle:Seccio',
						'query_builder' => function (EntityRepository $er) {
							return $er->createQueryBuilder ( 's' )
							->where ( 's.databaixa IS NULL AND s.semestral = 1' )
							->orderBy ( 's.id', 'DESC' );
						},
						'property' => 'nom',
						'multiple' => false,
						'required' => false,
						'data' => $seccio,
						'empty_data' => null,
						'mapped' => false
				) );
				
				// Entren com no facturats
				$form->add ( 'periodenf', 'entity', array (
						'error_bubbling' => true,
						'class' => 'FomentGestioBundle:Periode',
						'query_builder' => function (EntityRepository $er) {
							return $er->createQueryBuilder ( 'p' )
							->orderBy ( 'a.id', 'DESC' );
						},
						'property' => 'titol',
						'multiple' => false,
						'required' => true,
						//'data' => $activitat,
						//'empty_data' => null,
				) );
				
			
				
			} else {
				$form->add ( 'periodenf', 'hidden');
				
				$activitat = null;
				
				if ($rebut->getFacturacio() != null) $activitat = $rebut->getFacturacio()->getActivitat ();
				
				$form->add ( 'origen', 'entity', array (
						'error_bubbling' => true,
						'class' => 'FomentGestioBundle:Activitat',
						'query_builder' => function (EntityRepository $er) {
							return $er->createQueryBuilder ( 'a' )->where ( 'a.databaixa IS NULL' )->orderBy ( 'a.id', 'DESC' );
						},
						'property' => 'descripcio',
						'multiple' => false,
						'required' => false,
						'data' => $activitat,
						//'empty_data' => null,
						'mapped' => false 
				) );
				if ($activitat != null) {
					$participants = $activitat->getParticipantsSortedByCognom(false);
					
					foreach ($participants as $participant) $deutors[] = $participant->getPersona();
					 
					$facturacions = $activitat->getFacturacionsSortedByDatafacturacio ();
				}

			}
			
			$this->deutorsLoad($event->getForm (), $rebut->getDeutor(), $deutors);
			$this->facturacionsLoad ( $event->getForm (), $rebut->getFacturacio(), $facturacions );
			
			
			$form->add ( 'checkretornat', 'checkbox', array (
					// 'required' => false,
					// 'read_only' => false, // ??
					'data' => $rebut->retornat (),
					'mapped' => false 
			) );
			
			$form->add ( 'dataretornat', 'date', array (
					'widget' => 'single_text',
					'input' => 'datetime',
					'empty_value' => false,
					'read_only' => ! $rebut->retornat (),
					'format' => 'dd/MM/yyyy' 
			) );
			
			$form->add ( 'checkbaixa', 'checkbox', array (
					// 'required' => false,
					// 'read_only' => false, // ??
					'data' => $rebut->anulat (),
					'mapped' => false 
			) );
			
			$form->add ( 'databaixa', 'date', array (
					'widget' => 'single_text',
					'input' => 'datetime',
					'empty_value' => false,
					'read_only' => ! $rebut->anulat (),
					'format' => 'dd/MM/yyyy' 
			) );
			
			$form->add ( 'importcorreccio', 'number', array (
					'required' => true,
					'precision' => 2,
					'data' => $rebut->getImport (),
					'mapped' => false,
					'constraints' => array (
							new NotBlank ( array (
									'message' => 'Cal indicar l\'import.' 
							) ),
							new Type ( array (
									'type' => 'numeric',
									'message' => 'L\'import ha de ser numèric.' 
							) ),
							new GreaterThanOrEqual ( array (
									'value' => 0,
									'message' => 'L\'import no és vàlid.' 
							) ) 
					) 
			) );
			
			$form->add ( 'nouconcepte', 'text', array (
					'required' => true,
					'mapped' => false,
					'data' => $rebut->getNouconcepte () 
			) );
			
			$form->add ( 'tipuspagament', 'choice', array (
					'required' => false,
					'read_only' => false,
					'expanded' => false,
					'multiple' => false,
					'disabled' => $rebut->esActivitat (),
					'choices' => array (
							UtilsController::INDEX_FINESTRETA => 'finestreta',
							UtilsController::INDEX_DOMICILIACIO => 'domiciliació',
							UtilsController::INDEX_FINES_RETORNAT => 'finetreta retornat' 
					),
					'empty_value' => false 
			// 'data' => ($soci->esPagamentFinestreta()),
			// 'mapped' => false
						) );
		}
	}
	
	// No propagar, evita validacions
	public function postSubmitData(FormEvent $event) {
		$event->stopPropagation();
	}
	
	public function submitData(FormEvent $event) {
		// It's important here to fetch $event->getForm()->getData(), as
		// $event->getData() will get you the client data (that is, the ID)
		
		$rebut = $event->getForm()->getData();
		$form = $event->getForm ();
		
		$origen = $form->get('origen')->getData();
		
		$facturacio = null;
		
		if ($rebut != null) $facturacio = $rebut->getFacturacio();
		
		$facturacions = array();
		$deutors = array();
		if ($origen instanceof Activitat) {

			$activitat = $origen;
			
			$participants = $activitat->getParticipantsSortedByCognom(false);
				
			foreach ($participants as $participant) $deutors[] = $participant->getPersona();
			
			$facturacions = $activitat->getFacturacionsSortedByDatafacturacio();
			
		}
		
		$this->deutorsLoad($event->getForm (), $rebut->getDeutor(), $deutors);
		$this->facturacionsLoad ( $form, $facturacio, $facturacions );
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->addEventSubscriber ( new FormRebut () );
		
		/*
		 * $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
		 *
		 * });
		 */
		
		$builder->add ( 'num', 'text', array (
				'required' => true,
				'read_only' => true 
		) );
		
		$builder->add ( 'dataemissio', 'date', array (
				'widget' => 'single_text',
				'input' => 'datetime',
				'empty_value' => false,
				'format' => 'dd/MM/yyyy' 
		) );
		
		$builder->add ( 'datapagament', 'date', array (
				'widget' => 'single_text',
				'input' => 'datetime',
				'empty_value' => false,
				'format' => 'dd/MM/yyyy' 
		) );
	}
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults ( array (
				'data_class' => 'Foment\GestioBundle\Entity\Rebut' 
		) );
	}
	public function getName() {
		return 'rebut';
	}
}
