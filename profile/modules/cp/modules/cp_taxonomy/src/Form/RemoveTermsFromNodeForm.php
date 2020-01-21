<?php

namespace Drupal\cp_taxonomy\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\cp_taxonomy\Plugin\Action\RemoveTermsNodeAction;

/**
 * Remove terms from node entities form.
 */
class RemoveTermsFromNodeForm extends ManageTermsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remove_terms_to_node_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    $this->tempStore = $this->tempStoreFactory->get(RemoveTermsNodeAction::TEMPSTORE_KEY);
    $form['#title'] = $this->t('Remove Terms from Content');
    $form = parent::buildForm($form, $form_state, $entity_type_id);
    $form['entities']['#title'] = $this->t('The selected terms above will be removed from the following content:');
    $form['actions']['submit']['#value'] = $this->t('Remove');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    if ($button['#type'] == 'submit' && !empty($this->entityInfo)) {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $storage = $this->entityTypeManager->getStorage($this->entityTypeId);
      $terms_to_remove = $form_state->getValue('terms');
      $terms = $term_storage->loadMultiple($terms_to_remove);
      $term_names = [];
      foreach ($terms as $term) {
        $term_names[] = $term->label();
      }
      $entities = $storage->loadMultiple(array_keys($this->entityInfo));
      $skipped_titles = [];
      $applied_titles = [];
      foreach ($entities as $entity) {
        /** @var \Drupal\Core\Field\FieldItemList $current_terms */
        $current_terms = $entity->get('field_taxonomy_terms');
        $is_modified = FALSE;
        foreach ($current_terms->getValue() as $index => $value) {
          if (in_array($value['target_id'], $terms_to_remove)) {
            $current_terms->removeItem($index);
            $is_modified = TRUE;
          }
        }

        if (!$is_modified) {
          $skipped_titles[] = $entity->label();
          continue;
        }
        $entity->set('field_taxonomy_terms', $current_terms->getValue());
        $entity->save();
        $applied_titles[] = $entity->label();
      }

      $params = [
        '%terms' => implode(", ", $term_names),
      ];
      // Notify the user on the skipped nodes (nodes whose bundle is not
      // associated with the selected vocabulary).
      if (!empty($skipped_titles)) {
        $message = [
          [
            '#markup' => $this->formatPlural(count($terms_to_remove), 'No term was removed from the content:', 'No terms were removed from the content:', $params),
          ],
          [
            '#theme' => 'item_list',
            '#items' => $skipped_titles,
          ],
        ];
        $this->messenger()->addWarning($this->renderer->renderPlain($message));
      }

      // Notify the user on the applied nodes.
      if (!empty($applied_titles)) {
        $message = [
          [
            '#markup' => $this->formatPlural(count($terms_to_remove), 'Taxonomy term %terms was removed from the content:', '@count taxonomy terms %terms were removed from the content:', $params),
          ],
          [
            '#theme' => 'item_list',
            '#items' => $applied_titles,
          ],
        ];

        $this->messenger()->addStatus($this->renderer->renderPlain($message));
      }

      $this->tempStore->delete($this->currentUser->id());
    }

    $form_state->setRedirect('cp.content.collection');
  }

}
