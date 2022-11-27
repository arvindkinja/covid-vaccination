<?php

namespace Drupal\vaccination_appointment\Form;

use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * The vaccinationAppointmentSlotForm class.
 */
class vaccinationAppointmentSlotForm extends FormBase {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * DateDiffForm constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * The create method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container service.
   *
   * @return object
   *   The container object.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vaccination_appointment_slot_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $currentUser = User::load(\Drupal::currentUser()->id());
    $node = \Drupal::routeMatch()->getParameter('node');
    $nid = '';
    if ($node instanceof NodeInterface) {
      $nid = $node->id();
    }
    $available_slot = $node->get('field_available_slot')->value;

    if ($currentUser->field_appointment_information->entity->get('field_date')->value) {
      return $form['markup'] = [
        '#type' => 'markup',
        '#markup' => '<h3>You have already scheduled a appointment. Please check the details on <a href="/user">My account</a> page</h3>',
      ];
    }
    if ($available_slot == 0 || $available_slot == '0') {
      return $form['markup'] = [
        '#type' => 'markup',
        '#markup' => '<h3>There is no time slot available for this location. Please check other location.</h3>',
      ];
    }
    $form['appointment_date'] = [
      '#type' => 'date',
      '#title' => t('Appointment Date'),
      '#date_date_format' => 'Y-m-d',
      '#date_year_range' => '0',
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('appointment_date') < date('Y-m-d', time())) {
      $form_state->setErrorByName('end_date', $this->t('The Appointment Date should be grater than current date.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $node = \Drupal::routeMatch()->getParameter('node');
    $nid = '';
    if ($node instanceof NodeInterface) {
      $nid = $node->id();
    }
    $available_slot = $node->get('field_available_slot')->value;
    $node->set('field_available_slot', $available_slot - 1)->save();
    $currentUser = User::load(\Drupal::currentUser()->id());

    // Create new slot_time paragraph.
    $slot_time = Paragraph::create(['type' => 'slot_time']);
    $slot_time->field_date = $form_state->getValue('appointment_date');
    $slot_time->field_vaccination_centre->target_id = $nid;
    $slot_time->save();

    $currentUser->field_appointment_information = [
      'target_id' => $slot_time->id(),
      'target_revision_id' => $slot_time->getRevisionId(),
    ];
    $currentUser->save();
    $this->messenger->addMessage(t("Your appointment has been booked successfully. Please visit My account page to check the details."));
  }

}
