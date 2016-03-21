<?php

/**
 * @file
 * Contains \Drupal\featured_content\Plugin\views\display\FeaturedContentBlock.
 */

namespace Drupal\featured_content\Plugin\views\display;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\featured_content\Entity\FeaturedContent;
use Drupal\views\Plugin\views\display\Block;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends the standard 'block' display plugin.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "featured_content_block",
 *   title = @Translation("Featured Content"),
 *   help = @Translation("Display the view as a block that features content."),
 *   theme = "views_view",
 *   register_theme = FALSE,
 *   uses_hook_block = TRUE,
 *   contextual_links_locations = {"block"},
 *   admin = @Translation("Featured Content")
 * )
 *
 * @see \Drupal\views\Plugin\views\display\Block
 */
class FeaturedContentBlock extends Block {

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Constructs a featured content display plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The currentroute match service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, RouteMatchInterface $current_route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager);
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockSettings(array $settings) {
    return parent::blockSettings($settings) + [
      // There's no way to access the view and the display from the block. We
      // store the display plugin id in block settings to be used later by the
      // block plugin for identifying this display plugin.
      // @see featured_content_block_view_alter()
      'featured_content_display_plugin_id' => $this->getPluginDefinition()['id'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (empty($term_id = $this->getTaxonomyTermContext())) {
      $this->view->query->addWhereExpression(0, '1 = 2');
      return;
    }
    $plugin_id = $this->getBlockPluginId();
    if (!$featured_content = FeaturedContent::loadByContext($plugin_id, $term_id)) {
      $this->view->query->addWhereExpression(0, '1 = 2');
      return;
    }

    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->view->query;

    $configuration = [
      'type' => 'INNER',
      'table' => 'featured_content__content',
      'field' => 'content_target_id',
      'left_table' => $this->view->storage->get('base_table'),
      'left_field' => $this->view->storage->get('base_field'),
      'operator' => '=',
    ];
    /** @var \Drupal\views\Plugin\views\join\Standard $join */
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $query->addRelationship('fcc', $join, 'featured_content__content');

    $query->addWhere(0, 'fcc.entity_id', $featured_content->id());
    $query->addWhere(0, 'fcc.deleted', 0);
    $query->addField('fcc', 'delta', 'featured_content_weight');
    // We want to add our orderBy at the top.
    $query->orderby = array_merge(
      [['field' => 'featured_content_weight', 'direction' => 'ASC']],
      $query->orderby
    );

    // This block cache should be cleared when the corresponding featured
    // content entity is saved.
    // @todo The tag is correctly added to block render cache but the cache is
    //   not invalidated. Why?
    $this->display['cache_metadata']['tags'][] = "featured_content:{$featured_content->id()}";
    $this->display['cache_metadata']['contexts'][] = 'route.taxonomy_term';
  }

  /**
   * Gets the taxonomy term context.
   *
   * @return int|null
   *   The taxonomy term id or NULL if we're not on context.
   */
  protected function getTaxonomyTermContext() {
    $route_name = $this->currentRouteMatch->getRouteName();

    // We are only showing this block on taxonomy term page.
    if ($route_name != 'entity.taxonomy_term.canonical') {
      return NULL;
    }

    return $this->currentRouteMatch->getRawParameter('taxonomy_term');
  }

  /**
   * Returns the block plugin id of this display.
   *
   * @return string
   */
  protected function getBlockPluginId() {
    return 'views_block:' . $this->view->storage->id() . '-' . $this->display['id'];
  }

}
