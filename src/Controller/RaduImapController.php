<?php

namespace Drupal\radu_imap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\plugin_type_example\SandwichPluginManager;
use Drupal\radu_imap\ImapPluginManager;
use Drupal\radu_imap\Plugin\Imap\ImapBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RaduImapController extends ControllerBase {
  /**
   * @var \Drupal\radu_imap\ImapPluginManager $imapPluginManager
   */
  protected $imapPluginManager;

  public function test(){

    $build = array ();
    $build['intro'] = array(
      '#markup' => t("Plis work"),
    );

    $configuration = array (
      "serverPath" => "imap.mail.yahoo.com",
      "port" => 993,
      "username" => "nw407elixir@yahoo.com",
      "password" => "mmloismsahbojkbm"
    );

    $imap_plugin_definition = $this->imapPluginManager->getDefinition('radu_imap_server');
    /** @var ImapBase $plugin */
    $plugin = $this->imapPluginManager->createInstance($imap_plugin_definition['id'],$configuration);

    $plugin->setAuthentication('nw407elixit@yahoo.com','mmloismsahbojkbm');
//    kpr($plugin);

    $mailboxes = $plugin->listMailBoxes();

    $build['plugins'] = array (
      '#theme' => 'item_list',
      '#title' => 'Death plugins',
      '#items' => $mailboxes,
    );

//    $imap_plugin_definitions = $this->imapPluginManager->getDefinitions();
//    kpr($imap_plugin_definition);
//    $build['plugins'] = array (
//      '#theme' => 'item_list',
//      '#title' => 'Death plugins',
//      '#items' => $imap_plugin_definition,
//    );
    return $build;
  }

  public function __construct(ImapPluginManager $imapPluginManager) {
    $this->imapPluginManager = $imapPluginManager;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.imap'));
  }
}