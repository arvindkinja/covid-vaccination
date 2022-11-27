<?php

namespace Drupal\vaccination_appointment\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'vaccinationAppointmentSlotFormBlock' block.
 *
 * @Block(
 *   id = "vaccination_appointment_slot_form_block",
 *   admin_label = @Translation("Vaccination Appointment form block"),
 *   category = @Translation("Covid block")
 * )
 */
class vaccinationAppointmentSlotFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\vaccination_appointment\Form\vaccinationAppointmentSlotForm');
    return $form;
  }

}
