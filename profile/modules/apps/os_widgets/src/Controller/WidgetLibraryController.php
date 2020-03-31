<?php

namespace Drupal\os_widgets\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\StatusMessages;

/**
 * Methods for managing a site's Widget Library.
 */
class WidgetLibraryController extends ControllerBase {

  /**
   * Returns ajax commands after the block is saved.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   State for the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax commands.
   */
  public static function ajaxSubmitSave(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    /** @var \Drupal\block_content\BlockContentInterface $block_content */
    if ($form_state->getErrors()) {
      $messages = StatusMessages::renderMessages(NULL);
      $output[] = $messages;
      $output[] = $form;
      $form_class = '.' . str_replace(['content_', '_'], ['', '-'], $form_state->getFormObject()->getFormId());
      // Remove any previously added error messages.
      $response->addCommand(new RemoveCommand('#drupal-modal .messages--error'));
      // Replace old form with new one and with error message.
      $response->addCommand(new ReplaceCommand($form_class, $output));
    }
    elseif ($block_content = $form_state->getFormObject()->getEntity()) {
      $instances = $block_content->getInstances();
      $block_type = $block_content->bundle();
      $block_type_label = $block_content->type->entity->label();
      $block_contextual_links = [];

      if (!$instances) {
        $uuid = $block_content->uuid();
        $plugin_id = 'block_content:' . $uuid;
        $block_id = 'block_content|' . $uuid;
        $block = \Drupal::entityTypeManager()->getStorage('block')->create(['plugin' => $plugin_id, 'id' => $block_id]);
        $block->save();

        // Create block instance and fetch contextual links.
        $block_manager = \Drupal::service('plugin.manager.block');
        $block_u = $block_manager->createInstance('block_content:' . $uuid);
        $block_contextual_links = $block_u->build()['#contextual_links'];
      }
      else {
        $block = reset($instances);
      }
      $block_markup = \Drupal::entityTypeManager()->getViewBuilder('block')->view($block);

      $markup = [
        '#type' => 'inline_template',
        '#template' => '<div class="block block-active display-contextual" data-block-type="{{ type }}" data-block-id="{{ id }}" tabindex="0"><h3 class="block-title">{{ title }}</h3>{{ content }}</div>',
        '#context' => [
          'id' => $block->id(),
          'type' => $block_type,
          'title' => $block->label(),
          'content' => $block_markup,
        ],
        '#contextual_links' => $block_contextual_links,
      ];

      $response->addCommand(new PrependCommand('#block-list', $markup));
      $response->addCommand(new CloseModalDialogCommand());
      $response->addCommand(new InvokeCommand('#block-list', 'sortable', ['refresh']));
      $response->addCommand(new InvokeCommand('#factory-wrapper .close', 'click'));
      $response->addCommand(new InvokeCommand(NULL, 'updateWidgetType', [$block_type_label]));

      $status_messages = ['#type' => 'status_messages'];
      $messages = \Drupal::service('renderer')->renderRoot($status_messages);

      if ($messages) {
        $response->addCommand(new PrependCommand('div.region-content', $messages));
      }
    }

    return $response;
  }

  /**
   * Return Ajax commands after editting a widget.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   State for the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax commands.
   */
  public static function ajaxSubmitEdit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    /** @var \Drupal\block_content\BlockContentInterface $block_content */
    if ($block_content = $form_state->getFormObject()->getEntity()) {
      $instances = $block_content->getInstances();
      $block = reset($instances);

      $block_markup = \Drupal::entityTypeManager()->getViewBuilder('block')->view($block);

      $response->addCommand(new ReplaceCommand('section[class*="block-' . Html::getId($block->id()) . '"]', $block_markup));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

  /**
   * Return Ajax commands after deleting a widget.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   State for the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax commands.
   */
  public static function ajaxDelete(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

}
