<?php
namespace Drupal\radu_imap;

use Symfony\Component\EventDispatcher\Event;

class ImapEvent extends Event {

  protected $messages;


  public function __construct($messages) {
    $this->messages = $messages;
  }


  public function getMessages() {
    return $this->messages;
  }
  
  public function setMessages($messages) {
    $this->messages = $messages;
  }

}