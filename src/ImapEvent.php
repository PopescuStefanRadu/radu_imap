<?php
namespace Drupal\radu_imap;
use Symfony\Component\EventDispatcher\Event;
class ImapEvent extends Event {

  protected $messages;

  /**
   * Constructor.
   *
   * @param Config $config
   */
  public function __construct($messages) {
    $this->messages = $messages;
  }

  /**
   * @return mixed
   */
  public function getMessages() {
    return $this->messages;
  }

  /**
   * @param mixed $messages
   */
  public function setMessages($messages) {
    $this->messages = $messages;
  }

}