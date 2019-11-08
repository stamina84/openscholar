<?php

namespace Drupal\os_widgets\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the dataverse search box form.
 */
class DataverseSearchBoxForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dataverse_search_box_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['search'] = [
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#title_display' => 'invisible',
    // TODO customize placeholder.
      '#placeholder' => $this->t('Search My Dataverse'),
      '#attributes' => [
    // TODO set actual css class.
        'class' => ['todo-for-JS'],
        'title' => $this->t('Enter the terms you wish to search for.'),
      ],
      // TODO: Maybe allow empty strings too?
      '#required' => TRUE,
    ];
    // TODO: Discuss with team how bootstrap's `Plugin/Process/Search.php`
    // forces a visible description.
    // TODO: Learn from team what to do about long file paths in comments
    // in reference to coding standards.
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
    ];
    // TODO: Maybe just add regular button with JS attached, not an action
    // button of type submit?
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Do we want server handling for this form if JS is turned off?
  }

}
