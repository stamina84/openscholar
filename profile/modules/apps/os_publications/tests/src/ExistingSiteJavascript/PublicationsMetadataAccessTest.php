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
    $group_member = $this->createUser();
    $this->addGroupEnhancedMember($group_member, $this->group);
    $artwork_title = $this->randomMachineName();
    $artwork_alias = $this->randomMachineName();
    $reference = $this->createReference([
      'html_title' => $artwork_title,
    ]);
    $reference->setOwnerId($group_member->id())->save();
    $this->group->addContent($reference, 'group_entity:bibcite_reference');

    $this->drupalLogin($group_member);

    $this->visitViaVsite("bibcite/reference/{$reference->id()}/edit", $this->group);

    // Tests.
    $url_alias_edit_option = $this->getSession()->getPage()->find('css', '[href="#edit-path-0"]');
    $this->assertNotNull($url_alias_edit_option);
    $url_alias_edit_option->click();

    $this->submitForm([
      'path[0][pathauto]' => 0,
      'path[0][alias]' => "/$artwork_alias",
    ], 'Save');

    $this->visitViaVsite($artwork_alias, $this->group);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($artwork_title);
  }

}
