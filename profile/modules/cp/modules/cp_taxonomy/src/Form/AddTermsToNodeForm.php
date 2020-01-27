<?php

namespace Drupal\cp_taxonomy\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\cp_taxonomy\Plugin\Action\AddTermsNodeAction;

/**
 * Add terms to node entities form.
 */
class AddTermsToNodeForm extends ManageTermsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_terms_to_node_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    $this->tempStore = $this->tempStoreFactory->get(AddTermsNodeAction::TEMPSTORE_KEY);
    $form['#title'] = $this->t('Apply Terms to Content');
    return parent::buildForm($form, $form_state, $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    if ($button['#name'] == 'process_terms' && !empty($this->entityInfo)) {
      $this->applyTermsSubmit($form_state);
      $this->tempStore->delete($this->currentUser->id());
    }
  }

}
