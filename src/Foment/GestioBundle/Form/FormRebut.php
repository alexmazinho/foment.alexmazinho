<?php
// src/Foment/GestioBundle/Form/FormRebut.php
namespace Foment\GestioBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Doctrine\ORM\EntityRepository;
use Foment\GestioBundle\Entity\Rebut;
use Foment\GestioBundle\Controller\UtilsController;
use Foment\GestioBundle\Entity\Facturacio;
use Foment\GestioBundle\Entity\FacturacioSeccio;
use Foment\GestioBundle\Entity\FacturacioActivitat;
use Foment\GestioBundle\Entity\Activitat;

class FormRebut extends AbstractType implements EventSubscriberInterface {
	
	// Mètode per carregar dades relacionades amb l'activitat
	public function activitatsLoad(FormInterface $form, $rebut, $activitat) {
		
		$deutors = array();
		$facturacions = array();
		if ($rebut != null && $rebut->getId() != 0) { // Rebut existent
			$deutors[] = $rebut->getDeutor();
			$facturacions[] = $rebut->getFacturacio();
		} else {
			if ($activitat != null) {
				$participants = $activitat->getParticipantsSortedByCognom(true);
			
				foreach ($participants as $participant) $deutors[] = $participant->getPersona();
			
				$facturacions = $activitat->getFacturacionsSortedByDatafacturacio ();
				
			} 
		}
		
		$form->add ( 'deutor', 'entity', array (
				'error_bubbling' => true,
				'class' => 'FomentGestioBundle:Persona',
				'choices' => $deutors,
				'data' => $rebut->getDeutor(),
				'choice_label' => 'nomcognoms',
				'multiple' => false,
				'required' => false,
				'empty_data' => null,
				'disabled' => $rebut->getId() != 0
		) );
	
		$form->add ( 'facturacio', 'entity', array (
				'class' => 'FomentGestioBundle:FacturacioActivitat',
				'choice_label' => 'descripcioCompleta',
				'choices' => $facturacions,
				'data' => $rebut->getFacturacio(),
				'multiple' => false,
				'required' => false,
				'empty_data' => null,
				'disabled' => $rebut->getId() != 0
		));
		
		
		
	}
	
	
	// Mètode del subscriptor => implements EventSubscriberInterface
	public static function getSubscribedEvents() {
		
		// Tells the dispatcher that you want to listen on the form.pre_set_data
		// event and that the preSetData method should be called.
		return array (
				/*FormEvents::POST_SUBMIT => array('postSubmitData', 900),  // Desactiva validació
				FormEvents::SUBMIT => array('submitData', 900),*/
				FormEvents::PRE_SET_DATA => 'preSetData' 
		);
	}
	public function preSetData(FormEvent $event) {
		// Abans de posar els valors de la entitat al formulari. Permet evaluar-los per modificar el form. Ajax per exemple
		$form = $event->getForm ();
		$rebut = $event->getData ();
		
		if ($rebut instanceof Rebut) {
			$form->add ( 'id', 'hidden', array (
					'mapped' => false,
					'data' => $rebut->getId () 
			));
			
			// Rebut associat a una activitat o a una secció?
			if ($rebut->esSeccio ()) {
				/*$seccions = $rebut->getSeccions();
				
				$form->add ( 'origen', 'entity', array (
						'error_bubbling' => true,
						'class' => 'FomentGestioBundle:Seccio',
						'query_builder' => function (EntityRepository $er) {
							return $er->createQueryBuilder ( 's' )
							->where ( 's.databaixa IS NULL' )
							->orderBy ( 's.id', 'DESC' );
						},
						'choice_label' => 'nom',
						'multiple' => true,
						'required' => false,
						'data' => $seccions,
						'empty_data' => null,
						'mapped' => false
				));*/
				
				$facturacio = null;

				if ($rebut->getId() != 0) { // Rebut existent
					$facturacio = $rebut->getFacturacio();
				}	
				
				$form->add ( 'tipuspagament', 'choice', array (
						'required' => false,
						'read_only' => false,
						'expanded' => false,
						'multiple' => false,
						'disabled' => ($rebut->getTipusrebut() == UtilsController::TIPUS_SECCIO_NO_SEMESTRAL),
						'choices' => array (
								UtilsController::INDEX_FINESTRETA => 'finestreta',
								UtilsController::INDEX_DOMICILIACIO => 'domiciliació',
								UtilsController::INDEX_FINES_RETORNAT => 'finetreta retornat'
						),
						'placeholder' => false
						// 'data' => ($soci->esPagamentFinestreta()),
						// 'mapped' => false
				) );
				
				$seccionsArray = array();
				foreach ($rebut->getDetallsSortedByNum() as $d) {
					if ($d->getSeccio() != null) $seccionsArray[$d->getSeccio()->getId()] = $d->getConcepte();
				}
				
				
				if ($rebut->getTipusrebut() == UtilsController::TIPUS_SECCIO_NO_SEMESTRAL) {
					$form->add ( 'origen', 'choice', array (
							'error_bubbling' => true,
							'choices' => $seccionsArray,
							'multiple' => false,
							'required' => true,
							'mapped' => false,
					));
					
					$form->add( 'facturacio', 'hidden');
						
				} else {
					$form->add ( 'origen', 'choice', array (
							'error_bubbling' => true,
							'choices' => $seccionsArray,
							'multiple' => true,
							'required' => false,
							'mapped' => false,
					));
						
					$form->add ( 'facturacio', 'entity', array (
							'class' => 'FomentGestioBundle:FacturacioSeccio',
							'choice_label' => 'descripcioCompleta',
							'query_builder' => function (EntityRepository $er) {
								return $er->createQueryBuilder ( 'f' )
								->where('f.databaixa IS NULL')->orderBy ( 'f.datafacturacio', 'DESC' ); // Última primer
							},
							'data' => $facturacio,
							'multiple' => false,
							'required' => false,
							'empty_data' => null,
							'disabled' => $rebut->getId() != 0
					));
				}
				$form->add ( 'deutor', 'entity', array (
						'error_bubbling' => true,
						'class' => 'FomentGestioBundle:Soci',
						'query_builder' => function (EntityRepository $er) {
							return $er->createQueryBuilder ( 's' )
							//->where('s.databaixa IS NULL and s.id = s.socirebut')
							->orderBy ( 's.cognoms', 'ASC' ); // A càrrec de rebuts
						},
						'choice_label' => 'nomcognoms',
						'multiple' => false,
						'required' => true,
						'empty_data' => null,
						//'disabled' => true,
				));
				
			} else {
				
				$form->add ( 'tipuspagament', 'choice', array (
						'required' => false,
						'read_only' => false,
						'expanded' => false,
						'multiple' => false,
						'choices' => array (
								UtilsController::INDEX_FINESTRETA => 'finestreta'
						),
						'placeholder' => false,
						'read_only' => true
				) );
				
				$activitat = null;
				if ($rebut->getFacturacio() != null) $activitat = $rebut->getFacturacio()->getActivitat();
				
				

				if ($activitat != null) {
					$form->add ( 'origen', 'entity', array (
							'error_bubbling' => true,
							'class' => 'FomentGestioBundle:Activitat',
							'choices' => array ( $activitat ),
							'choice_label' => 'descripcio',
							'multiple' => false,
							'required' => true,
							'data' => $activitat,
							'read_only' => true,
							'mapped' => false 
					) );
				} else {
					$form->add ( 'origen', 'entity', array (
							'error_bubbling' => true,
							'class' => 'FomentGestioBundle:Activitat',
							'query_builder' => function (EntityRepository $er) {
								return $er->createQueryBuilder ( 'a' )->where ( 'a.databaixa IS NULL' )->orderBy ( 'a.id', 'DESC' );
							},
							'choice_label' => 'descripcio',
							'multiple' => false,
							'required' => false,
							'empty_data' => null,
							'mapped' => false 
					) );
				}
			
				$this->activitatsLoad($form, $rebut, $activitat);
			}
			
			$form->add ( 'tipusrebut', 'hidden', array (
					// 'required' => false,
					// 'read_only' => false, // ??
					/*'data' => $rebut->tipusrebut(),
					'mapped' => false,
					'disabled' => ($rebut->esActivitat() == true)*/
			) );
			
			$form->add ( 'checkretornat', 'checkbox', array (
					// 'required' => false,
					// 'read_only' => false, // ??
					'data' => $rebut->retornat (),
					'mapped' => false,
					'disabled' => ($rebut->esActivitat() == true)
			) );
			
			$form->add ( 'dataretornat', 'date', array (
					'widget' => 'single_text',
					'input' => 'datetime',
					'placeholder' => false,
					'read_only' => !$rebut->retornat(),
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
					'placeholder' => false,
					'read_only' => ! $rebut->anulat (),
					'format' => 'dd/MM/yyyy' 
			) );
			
			$form->add ( 'nouconcepte', 'text', array (
					'required' => true,
					'mapped' => false,
					'data' => $rebut->getNouconcepte () 
			) );
			
			$form->add ( 'importcorreccio', 'number', array (
					'required' 	=> true,
					'scale' 	=> 2,
					'data' 		=> $rebut->getImport(),
					'mapped' 	=> false,
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
		}
	}
	
	// No propagar, evita validacions
	/*
	public function postSubmitData(FormEvent $event) {
		
		$event->stopPropagation();
	}
	
	public function submitData(FormEvent $event) {
		// It's important here to fetch $event->getForm()->getData(), as
		// $event->getData() will get you the client data (that is, the ID)
		$rebut = $event->getForm()->getData();
		$form = $event->getForm ();
		
		$origen = $form->get('origen')->getData();


		if ($origen instanceof Activitat) { // Canvi d'activitat, actualitza camps associats: deutors, facturacions
		//if ($rebut instanceof Rebut) {
			$activitat = $origen;
			
			$this->activitatsLoad($event->getForm (), $rebut, $rebut->getFacturacio()->getActivitat());
		}
	}*/
	
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
				'placeholder' => false,
				'format' => 'dd/MM/yyyy' 
		) );
		
		$builder->add ( 'datapagament', 'date', array (
				'widget' => 'single_text',
				'input' => 'datetime',
				'placeholder' => false,
				'format' => 'dd/MM/yyyy' 
		) );
	}
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults ( array (
				'data_class' => 'Foment\GestioBundle\Entity\Rebut' 
		) );
	}
	public function getName() {
		return 'rebut';
	}
}
