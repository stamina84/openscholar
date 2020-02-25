<?php

namespace Drupal\vsite\Plugin;

use Drupal\Core\Database\Connection;
use Drupal\Core\State\State;
use Drupal\group\Entity\GroupInterface;
use Drupal\vsite\Event\VsiteActivatedEvent;
use Drupal\vsite\VsiteEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Manages and stores active vsites.
 *
 * Other classes declare a vsite is active to this manager, and this
 * class responds and dispatches an event for other modules to listen to.
 */
class VsiteContextManager implements VsiteContextManagerInterface {

  public const VSITE_CSS_JS_QUERY_STRING_STATE_KEY_PREFIX = 'vsite.css_js_query_string.';

  /**
   * The active vsite.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $activeGroup = NULL;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $dbConnection;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection.
   */
  public function __construct(EventDispatcherInterface $dispatcher, Connection $connection) {
    $this->dispatcher = $dispatcher;
    $this->dbConnection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function activateVsite(GroupInterface $group) {
    if (!$group->id()) {
      return;
    }

    if (is_null($this->activeGroup) || $this->activeGroup->id() !== $group->id()) {
      $this->activeGroup = $group;

      $event = new VsiteActivatedEvent($group);
      $this->dispatcher->dispatch(VsiteEvents::VSITE_ACTIVATED, $event);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveVsite() : ?GroupInterface {
    return $this->activeGroup;
  }

  /**
   * {@inheritdoc}
   */
  public function getActivePurl() {
    /** @var \Drupal\group\Entity\GroupInterface|null $group */
    $group = $this->getActiveVsite();

    if (!$group) {
      return '';
    }

    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->dbConnection->select('url_alias', 'ua')
      ->fields('ua', ['alias'])
      ->condition('ua.source', "/group/{$group->id()}")
      ->range(0, 1);
    /** @var \Drupal\Core\Database\StatementInterface|null $result */
    $result = $query->execute();

    if (!$result) {
      return '';
    }

    $item = $result->fetchAssoc();

    return trim($item['alias'], '/');
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveVsiteAbsoluteUrl(string $path = ''): string {
    /** @var \Drupal\group\Entity\GroupInterface|null $active_vsite */
    $active_vsite = $this->getActiveVsite();
    if (!$active_vsite) {
      return $path;
    }

    return '/' . $this->getActivePurl() . '/' . ltrim($path, '/');
  }

  /**
   * {@inheritdoc}
   */
  public static function vsiteFlushCssJs(GroupInterface $vsite): void {
    \Drupal::state()->set(self::VSITE_CSS_JS_QUERY_STRING_STATE_KEY_PREFIX . $vsite->id(), base_convert(\Drupal::time()->getCurrentTime(), 10, 36));
  }

}
