<?php
namespace Drupal\radu_imap\Plugin\Imap;


/**
 *
 * @Plugin(
 *   id = "radu_imap_server",
 *   serverPath = "someString",
 *   port = "someString",
 *   service = "imap",
 *   username = "someUsername",
 *   password = "somePassword",
 *   port = 993,
 *   )
 */
class ImapBase extends Server {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->serverPath = $configuration['serverPath'];
    if (array_key_exists('port', $configuration)) {
      $this->port = $configuration['port'];
      switch ($this->port) {
        case 143:
          $this->setFlag('novalidate-cert');
          break;
        case 993:
          $this->setFlag('ssl');
          break;
      }
    }
    if (array_key_exists('service', $configuration)) {
      $this->service = $configuration['service'];
    }

    if (array_key_exists('username', $configuration) && array_key_exists('password', $configuration)) {
      $this->setAuthentication($configuration['username'], $configuration['password']);
    }
  }
}