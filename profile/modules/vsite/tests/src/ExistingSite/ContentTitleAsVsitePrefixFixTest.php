<?php

namespace Drupal\Tests\vsite\ExistingSite;

/**
 * Tests the case when content URL contains the vsite alias itself.
 *
 * Ref: https://github.com/openscholar/openscholar/issues/12659
 *
 * @group functional
 * @group vsite
 */
class ContentTitleAsVsitePrefixFixTest extends VsiteExistingSiteTestBase {

  /**
   * Vsite alias.
   *
   * @var string
   */
  protected $vsiteAlias;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    /** @var \Drupal\pathauto\PathautoGeneratorInterface $pathauto_generator */
    $pathauto_generator = $this->container->get('pathauto.generator');
    $this->vsiteAlias = ltrim($this->groupAlias, '/');

    $content = $this->createNode([
      'type' => 'news',
      'title' => "{$this->vsiteAlias} test",
      'field_date' => [
        'value' => '2020-01-22',
      ],
    ]);
    $this->addGroupContent($content, $this->group);
    $pathauto_generator->updateEntityAlias($content, 'update', ['force' => TRUE]);
  }

  /**
   * @covers \Drupal\group_purl\Plugin\Purl\Method\GroupPrefixMethod::alterRequest
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function test(): void {
    $this->visitViaVsite("news/{$this->vsiteAlias}-test", $this->group);
    $this->assertSession()->statusCodeEquals(200);
  }

}
