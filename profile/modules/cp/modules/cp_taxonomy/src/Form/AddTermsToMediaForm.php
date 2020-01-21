<?php

namespace Drupal\cp_taxonomy\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\cp_taxonomy\Plugin\Action\AddTermsMediaAction;

/**
 * Add terms to media entities form.
 */
class AddTermsToMediaForm extends ManageTermsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_terms_to_media_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    $this->tempStore = $this->tempStoreFactory->get(AddTermsMediaAction::TEMPSTORE_KEY);
    $form['#title'] = $this->t('Apply Terms to Media');
    return parent::buildForm($form, $form_state, $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    if ($button['#type'] == 'submit' && !empty($this->entityInfo)) {
      $this->applyTermsSubmit($form_state);
      $this->tempStore->delete($this->currentUser->id());
    }
  }

}
