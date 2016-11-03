<?php

/**
 * @file
 * Contains \Drupal\multiversion\Tests\WorkspaceTest.
 */

namespace Drupal\multiversion\Tests;

use Drupal\KernelTests\KernelTestBase;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Entity\WorkspaceInterface;

/**
 * Test the workspace entity.
 *
 * @group multiversion
 */
class WorkspaceTest extends MultiversionWebTestBase {

  protected $strictConfigSchema = FALSE;

  public static $modules = ['multiversion', 'key_value'];

  public function testOperations() {
    $default = Workspace::load(1);
    $this->assertTrue(!empty($default), 'Default workspace was created when installing Multiversion module.');
    $machine_name = $this->randomMachineName();
    $entity = Workspace::create(['machine_name' => $machine_name, 'label' => $machine_name]);

    $this->assertTrue($entity instanceof WorkspaceInterface, 'Workspace entity was created.');

    $entity->save();
    $this->assertEqual($machine_name, $entity->get('machine_name')->value, 'Workspace entity was saved.');

    $entity = Workspace::load($entity->id());
    $this->assertEqual($machine_name, $entity->get('machine_name')->value, 'Workspace entity was loaded by ID.');

    $entity = $this->entityManager->loadEntityByUuid('workspace', $entity->uuid());
    $this->assertEqual($machine_name, $entity->get('machine_name')->value, 'Workspace entity was loaded by UUID.');
    $this->assertEqual($machine_name, $entity->label(), 'Label method returns the workspace name.');

    $created = $entity->getStartTime();
    $this->assertNotNull($created, "The value for 'created' field is not null.");

    $new_created_time = microtime(TRUE) * 1000000;
    $entity->setCreatedTime((int) $new_created_time);
    $this->assertEqual($new_created_time, $entity->getStartTime(), "Correct value for 'created' field.");
  }

}
