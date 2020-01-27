<?php

namespace Drupal\Tests\openscholar\ExistingSite;

use Drupal\Tests\openscholar\Traits\ExistingSiteTestTrait;
use Drupal\Tests\openscholar\Traits\OsCleanupClassTestTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * OS kernel and functional test base.
 */
abstract class OsExistingSiteTestBase extends ExistingSiteBase {

  use ExistingSiteTestTrait;
  use OsCleanupClassTestTrait;

  /**
   * Test group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Group Plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManager
   */
  protected $pluginManager;

  /**
   * Vsite Plugin manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManager
   */
  protected $vsiteContextManager;

  /**
   * Test group alias.
   *
   * @var string
   */
  protected $groupAlias;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->group = $this->createGroup();
    $this->groupAlias = $this->group->get('path')->first()->getValue()['alias'];
    $this->pluginManager = $this->container->get('plugin.manager.group_content_enabler');
    $this->vsiteContextManager = $this->container->get('vsite.context_manager');
    $this->configFactory = $this->container->get('config.factory');
    $this->runCount = 0;
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->cleanupEntities = array_reverse($this->cleanupEntities);
    parent::tearDown();

    foreach ($this->cleanUpConfigs as $config_entity) {
      $config_entity->delete();
    }
    // This is part of the test cleanup.
    // If this is not done, then it leads to database deadlock error in the
    // test. The test is performing nested db operations during cleanup.
    $installed = $this->pluginManager->getInstalledIds($this->group->getGroupType());
    foreach ($this->pluginManager->getAll() as $plugin_id => $plugin) {
      if (in_array($plugin_id, $installed)) {
        $contents = $this->group->getContent($plugin_id);
        foreach ($contents as $content) {
          $content->delete();
        }
      }
    }
    $this->group->delete();

    $this->cleanUpProperties(self::class);
  }

  /**
   * Get formatter instance.
   *
   * @param string $entity_type_id
   *   Entity type id.
   * @param string $bundle
   *   Bundle.
   * @param string $field_name
   *   Field name value.
   * @param string $formatter
   *   Formatter id.
   * @param array $settings
   *   Formatter settings.
   *
   * @return \Drupal\Core\Field\FormatterInterface
   *   Formatter instance.
   */
  public function getFormatterInstance(string $entity_type_id, string $bundle, string $field_name, string $formatter, array $settings) {
    $field_definitions = $this->container->get('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);
    $formatter_plugin_manager = $this->container->get('plugin.manager.field.formatter');

    $options = [
      'field_definition' => $field_definitions[$field_name],
      'configuration' => [
        'type' => $formatter,
        'settings' => $settings,
      ],
      'view_mode' => 'default',
    ];
    return $formatter_plugin_manager->getInstance($options);
  }

}
