<?php

namespace Drupal\Tests\os_publications\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests publications metadata access for vsite roles.
 *
 * @group functional-javascript
 * @group publications
 * @covers ::os_publications_bibcite_reference_form_alter_metadata_access
 * @covers ::os_publications_form_bibcite_reference_form_alter
 */
class PublicationsMetadataAccessTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Tests access for enhanced basic member role.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testGroupMemberAccess(): void {
    // Setup.
    $group_member = $this->createUser();
    $this->addGroupEnhancedMember($group_member, $this->group);
    $artwork_title = $this->randomMachineName();
    $artwork_alias = $this->randomMachineName();
    $reference = $this->createReference([
      'html_title' => $artwork_title,
    ]);
    $reference->setOwnerId($group_member->id())->save();
    $this->group->addContent($reference, 'group_entity:bibcite_reference');

    // Do changes.
    $this->drupalLogin($group_member);

    $this->visitViaVsite("bibcite/reference/{$reference->id()}/edit", $this->group);

    $url_alias_edit_option = $this->getSession()->getPage()->find('css', '[href="#edit-path-0"]');
    $this->assertNotNull($url_alias_edit_option);
    $url_alias_edit_option->click();

    $this->submitForm([
      'path[0][pathauto]' => 0,
      'path[0][alias]' => "/$artwork_alias",
    ], 'Save');

    // Tests.
    $this->visitViaVsite($artwork_alias, $this->group);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($artwork_title);
  }

  /**
   * Tests access for administrator role.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function testGroupAdmin(): void {
    // Setup.
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $group_member = $this->createUser();
    $this->group->addMember($group_member);
    $artwork_title = $this->randomMachineName();
    $artwork_alias = $this->randomMachineName();
    $reference = $this->createReference([
      'html_title' => $artwork_title,
    ]);
    $this->group->addContent($reference, 'group_entity:bibcite_reference');

    // Do changes.
    $this->drupalLogin($group_admin);

    $this->visitViaVsite("bibcite/reference/{$reference->id()}/edit", $this->group);

    $url_alias_edit_option = $this->getSession()->getPage()->find('css', '[href="#edit-path-0"]');
    $this->assertNotNull($url_alias_edit_option);
    $url_alias_edit_option->click();

    $this->submitForm([
      'path[0][pathauto]' => 0,
      'path[0][alias]' => "/$artwork_alias",
    ], 'Save');

    $this->visitViaVsite("bibcite/reference/{$reference->id()}/edit", $this->group);

    $author_edit_option = $this->getSession()->getPage()->find('css', '[href="#edit-authoring-info"]');
    $this->assertNotNull($author_edit_option);
    $author_edit_option->click();

    $this->submitForm([
      'uid[0][target_id]' => "{$group_member->getAccountName()} ({$group_member->id()})",
      'created[0][value][date]' => '2019-08-15',
      'created[0][value][time]' => '00:00:00',
    ], 'Save');

    // Tests.
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $reference_storage */
    $reference_storage = $entity_type_manager->getStorage('bibcite_reference');
    /** @var \Drupal\bibcite_entity\Entity\ReferenceInterface $fresh_entity */
    $fresh_entity = $reference_storage->load($reference->id());

    $this->visitViaVsite($artwork_alias, $this->group);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($artwork_title);
    $this->assertEquals($group_member->id(), $fresh_entity->get('uid')->first()->getValue()['target_id']);
    $this->assertEquals('15/08/2019', date('d/m/Y', $fresh_entity->get('created')->first()->getValue()['value']));
  }

}
