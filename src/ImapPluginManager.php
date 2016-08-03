<?php

namespace Drupal\radu_imap;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class ImapPluginManager extends DefaultPluginManager {

  /**
   * Creates the discovery object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cacheBackend, ModuleHandlerInterface $moduleHandler) {
    $subdir = 'Plugin/Imap';
    $pluginInterface = 'Drupal\radu_imap\ImapInterface';
    $pluginDefinitionAnnotationName = 'Drupal\Component\Annotation\Plugin';

    parent::__construct($subdir, $namespaces, $moduleHandler, $pluginInterface, $pluginDefinitionAnnotationName);

//    $this->alterInfo('imap_info');
//    $this->setCacheBackend($cacheBackend,'sandwich_info');
  }

}