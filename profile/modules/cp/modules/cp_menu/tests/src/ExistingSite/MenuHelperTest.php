<?php

namespace Drupal\Tests\cp_menu\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * MenuHelper service done.
 *
 * @group kernel
 * @group cp-menu
 */
class MenuHelperTest extends OsExistingSiteTestBase {

  /**
   * @covers \Drupal\cp_menu\Services\MenuHelper::getVsiteMenuOptions
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testGetVsiteMenuOptions(): void {
    /** @var \Drupal\cp_menu\MenuHelperInterface $menu_helper */
    $menu_helper = $this->container->get('cp_menu.menu_helper');
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\Core\Entity\EntityStorageInterface $menu_storage */
    $menu_storage = $entity_type_manager->getStorage('menu');

    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $group_menu */
    $group_menu = $menu_storage
      ->create([
        'id' => $this->randomMachineName(),
        'label' => $this->randomMachineName(),
      ]);
    $group_menu->save();
    $this->markConfigForCleanUp($group_menu);

    $menu_options = $menu_helper->getVsiteMenuOptions($this->group);
    $this->assertTrue(isset($menu_options['main:'], $menu_options['footer:']));

    $this->group->addContent($group_menu, 'group_menu:menu');
    $menu_options = $menu_helper->getVsiteMenuOptions($this->group);
    $this->assertTrue(isset($menu_options["{$group_menu->id()}:"]));
  }

}
