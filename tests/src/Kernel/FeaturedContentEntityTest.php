<?php

/**
 * @file
 * Contains \Drupal\Tests\featured_content\Kernel\FeaturedContentEntityTest.
 */

namespace Drupal\Tests\featured_content\Kernel;

use Drupal\block\Entity\Block;
use Drupal\featured_content\Entity\FeaturedContent;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\User;

/**
 * Tests 'featured_content' entity.
 *
 * @group featured_content
 */
class FeaturedContentEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['featured_content', 'featured_content_test', 'taxonomy', 'text', 'block', 'node', 'field', 'user', 'system', 'views'];

  /**
   * Tests 'featured_content' entity CRUD ops.
   */
  public function testFeaturedContentEntity() {
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('featured_content');
    $this->installConfig('featured_content');
    $this->installConfig('featured_content_test');
    $this->installSchema('system', ['sequences']);

    NodeType::create(['type' => 'page'])->save();

    // Install test fields.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'test_topic',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'taxonomy_term'],
    ])->setStatus(TRUE)->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => 'test_topic',
      'settings' => [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => ['test_topics' => 'test_topics'],
          'sort' => ['field' => '_none'],
          'auto_create' => FALSE,
        ],
      ],
    ])->setStatus(TRUE)->save();

    FieldStorageConfig::load('featured_content.content')
      ->setSetting('target_type', 'node')
      ->save();

    Vocabulary::create(['vid' => 'topics'])->save();
    $term = Term::create(['name' => 'foo', 'vid' => 'topics']);
    $term->save();

    $user = User::create(['name' => 'jdoe', 'status' => TRUE]);
    $user->save();

    /** @var \Drupal\block\BlockInterface $block */
    $block = Block::load('featured_content_block_test');

    $entities = [];
    for ($i = 0; $i < 2; $i++) {
      $entity = Node::create(['title' => "Content $i", 'type' => 'page']);
      $entity->save();
      $entities[$i] = $entity;
    }

    /** @var \Drupal\featured_content\Entity\FeaturedContent $relation */
    $relation = FeaturedContent::create();
    $relation->uid = $user;
    $relation->term = $term;
    $relation->block_plugin->value = $block->getPluginId();
    $relation->content = $entities;
    $relation->save();

    // Reload the entity.
    $relation = FeaturedContent::load($relation->id());

    // Check that the term has been set correctly.
    $this->assertSame('foo', $relation->term->entity->label());
    // Check that the user has been set correctly.
    $this->assertSame('jdoe', $relation->uid->entity->label());
    // Check that the block has been set correctly.
    $this->assertSame('views_block:featured_content_test-block_1', $relation->block_plugin->value);
    // Check that the featured content label is correct.
    $this->assertSame("Content featured content: topics 'foo', block: 'views_block:featured_content_test-block_1'", (string) $relation->label());
    // Check that the content has been set correctly.
    foreach ($entities as $delta => $entity) {
      $this->assertSame($entity->label(), $relation->content[$delta]->entity->label());
    }

    // Test entity validation.

    // Duplicate relation.
    $relation_duplicate = clone $relation;
    // Prevent id collision.
    $relation_duplicate->set('id', $relation->id() + 1);

    // Checks that a duplicate relation is not validated.
    $violations = $relation_duplicate->validate();

    $this->assertSame(1, $violations->count());
    $this->assertSame("Relation term 'foo', block 'views_block:featured_content_test-block_1' already exists.", (string) $violations->get(0)->getMessage());

    // Invalid block plugin.
    $block_broken = Block::create(['id' => 'invalid_block', 'plugin' => 'broken']);
    $block_broken->save();
    $relation->block_plugin->value = $block_broken->getPluginId();

    // Checks that an invalid block type cannot be involved in a relation.
    $violations = $relation->validate();
    $this->assertSame("The block 'broken' is not a Views block.", (string) $violations->get(0)->getMessage());
  }

}
