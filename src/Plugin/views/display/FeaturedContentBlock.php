<?php

namespace Drupal\featured_content\Plugin\views\display;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\featured_content\Entity\FeaturedContent;
use Drupal\featured_content\Entity\FeaturedContentType;
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
   *   The current route match service.
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
  public function defineOptions() {
    return [
      'featured_content_type' => ['default' => NULL]
    ] + parent::defineOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function blockSettings(array $settings) {
    return parent::blockSettings($settings) + [
      'featured_content_type' => $this->getOption('featured_content_type'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $label = $this->t('None');
    if ($id = $this->getOption('featured_content_type')) {
      $label = FeaturedContentType::load($id)->label();
    }

    $options['featured_content_type'] = array(
      'category' => 'block',
      'title' => $this->t('Featured content type'),
      'value' => $label,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if ($form_state->get('section') == 'featured_content_type') {
      $options = [];
      foreach (FeaturedContentType::loadMultiple() as $id => $type) {
        $options[$id] = $type->label();
      }
      $form['#title'] .= $this->t('Featured contrent type');
      $form['featured_content_type'] = array(
        '#type' => 'select',
        '#options' => $options,
        '#description' => $this->t('Select the featured content type to be used.'),
        '#default_value' => $this->getOption('featured_content_type'),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);
    if ($form_state->get('section') == 'featured_content_type') {
      $type = $form_state->getValue('featured_content_type');
      if (empty($type) || empty(FeaturedContentType::load($type))) {
        $form_state->setError($form['featured_content_type'], $this->t('A featured content type should be configured for this type of display.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    if ($section == 'featured_content_type') {
      $this->setOption($section, $form_state->getValue($section));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    $type = $this->getOption('featured_content_type');
    if (empty($type) || empty(FeaturedContentType::load($type))) {
      $errors[] = $this->t('A featured content type should be configured for this type of display.');
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->currentRouteMatch->getParameter('taxonomy_term');
    if ($type_id = $this->getOption('featured_content_type')) {
      $type = FeaturedContentType::load($type_id);
    }
    if (empty($term) || empty($type)) {
      $this->view->query->addWhereExpression(0, '1 = 2');
      return;
    }

    $plugin_id = 'views_block:' . $this->view->storage->id() . '-' . $this->display['id'];

    $featured_content = FeaturedContent::loadByContext($type->id(), $plugin_id, $term->id());
    if (!$featured_content) {
      $this->view->query->addWhereExpression(0, '1 = 2');
      return;
    }

    /* @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->view->query;

    $base_table = $this->view->storage->get('base_table');
    $join_field = ($base_table == 'featured_content') ? 'entity_id' : 'content_target_id';

    $configuration = [
      'type' => 'LEFT',
      'table' => 'featured_content__content',
      'field' => $join_field,
      'left_table' => $base_table,
      'left_field' => $this->view->storage->get('base_field'),
      'operator' => '=',
    ];
    /* @var \Drupal\views\Plugin\views\join\Standard $join */
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);

    // Naming the relation "featured_content__content" ensure no duplication in
    // case the same relation is included by field handlers later in the query
    // building process.
    $query->addRelationship('featured_content__content', $join, 'featured_content__content');

    $query->addWhere(0, 'featured_content__content.entity_id', $featured_content->id());
    $query->addWhere(0, 'featured_content__content.deleted', 0);
    $query->addField('featured_content__content', 'delta', 'featured_content_weight');
    // We want to add our orderBy at the top.
    $query->orderby = array_merge(
      [['field' => 'featured_content_weight', 'direction' => 'ASC']],
      $query->orderby
    );

    // This block cache should be cleared when the corresponding featured
    // content entity is saved.
    $this->display['cache_metadata']['tags'][] = "featured_content:{$featured_content->id()}";
    $this->display['cache_metadata']['tags'][] = "featured_content_type:{$featured_content->bundle()}";
    $this->display['cache_metadata']['contexts'][] = 'route.taxonomy_term';
  }

  /**
   * {@inheritdoc}
   */
  public function calculatePluginDependencies(PluginInspectionInterface $instance) {
    parent::calculatePluginDependencies($instance);
    if ($type_id = $this->getOption('featured_content_type')) {
      if ($type = FeaturedContentType::load($type_id)) {
        $this->addDependency($type->getConfigDependencyKey(), $type->getConfigDependencyName());
      }
    }
  }

}
