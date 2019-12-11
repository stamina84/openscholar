<?php

namespace Drupal\Tests\os_wysiwyg\ExistingSite;

use Drupal\filter\FilterPluginCollection;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Class OswysiwygPurifyHtmlTest.
 *
 * @package Drupal\Tests\os_wysiwyg\ExistingSite
 * @group kernel
 * @group wysiwyg
 */
class OswysiwygPurifyHtmlTest extends OsExistingSiteTestBase {

  /**
   * PurifyHtml.
   *
   * @var \Drupal\os_wysiwyg\Plugin\Filter\PurifyHtml
   */
  protected $filter;

  /**
   * Purify Html Helper.
   *
   * @var \Drupal\os_wysiwyg\PurifyHtmlHelper
   */
  private $helper;

  /**
   * A set up for all tests.
   */
  public function setUp() {
    parent::setUp();

    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, []);
    $this->filter = $bag->get('purifyhtml');
    $this->helper = $this->container->get('os_wysiwyg.os_purifyhtml');
  }

  /**
   * Test for Malicious Code.
   */
  public function testMaliciousCode() {
    $input = 'Lorem<script type="application/javascript">var bad_code;</script>Ipsum';
    $processed = $this->filter->process($input, 'und')->getProcessedText();
    $this->assertSame('LoremIpsum', $processed);
  }

  /**
   * Test for in line styles.
   */
  public function testInlineStyles() {
    $input = '<a style="width:100px"></a>';
    $processed = $this->filter->process($input, 'und')->getProcessedText();
    $this->assertContains('width:100px', $processed);
    $this->assertContains('style', $processed);
  }

  /**
   * Test for helper Purify Html service.
   */
  public function testPurifyHtml() {
    $input = 'Lorem<script type="application/javascript">var bad_code;</script>Ipsum';
    $processed = $this->helper->getPurifyHtml($input, [])->getProcessedText();
    $this->assertSame('LoremIpsum', $processed);

    $input = '<a style="width:100px"></a>';
    $processed = $this->helper->getPurifyHtml($input, [])->getProcessedText();
    $this->assertContains('width:100px', $processed);
    $this->assertContains('style', $processed);
  }

}
