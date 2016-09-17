<?php

namespace Drupal\featured_content\ParamConverter;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\views\Views;
use Symfony\Component\Routing\Route;

/**
 * Provides upcasting for a views block plugin.
 */
class ViewsBlockPluginConverter implements ParamConverterInterface {

  /**
   * The block plugin manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockPluginManager;

  /**
   * Constructs a views block plugin converter.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_plugin_manager
   *   The block plugin manager service.
   */
  public function __construct(BlockManagerInterface $block_plugin_manager) {
    $this->blockPluginManager = $block_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    /** @var \Drupal\Core\Block\BlockPluginInterface $plugin */
    if ($plugin = $this->blockPluginManager->createInstance("views_block:$value")) {
      if ($plugin->getBaseId() === 'views_block') {
        // Get the view and display ID.
        list($view_id, $display_id) = explode('-', $plugin->getDerivativeId(), 2);
        $view = Views::getView($view_id);
        if ($view && $view->setDisplay($display_id)) {
          if ($view->getDisplay()->getPluginId() == 'featured_content_block') {
            return $plugin;
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'views_block_plugin');
  }

}
