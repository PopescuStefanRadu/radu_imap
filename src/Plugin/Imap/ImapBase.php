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

    if (in_array('port', $configuration)) {
      $this->port = $configuration['port'];
    }

    if (in_array('service', $configuration)) {
      $this->service = $configuration['service'];
    }
  }

  public function moveMailToMailBox($msglist, $mailbox, $options = 0) {
    $result = imap_mail_move($this->getImapStream(),
      $msglist,
      $this->getServerSpecification() . $mailbox,
      $options
    );
    return $result;
  }

  public function copyMailToMailBox($msglist, $mailbox, $options = 0) {
    $result = imap_mail_copy(
      $this->getImapStream(),
      $msglist,
      $this->getServerSpecification() . $mailbox,
      $options
    );
    return $result;
  }

  public function deleteMail($msg_number, $options = 0) {
    $result = imap_delete($this->getImapStream(), $msg_number, $options);
    return $result;
  }

  public function undeleteMail($msg_number, $options = 0) {
    $result = imap_undelete($this->getImapStream(), $msg_number, $options);
    return $result;
  }

}