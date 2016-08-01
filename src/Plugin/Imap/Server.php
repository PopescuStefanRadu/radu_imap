<?php

/*
 * This file is part of the Fetch package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drupal\radu_imap\Plugin\Imap;

use Drupal\views\Plugin\views\PluginBase;
use Drupal\radu_imap\Message;
use Drupal\radu_imap\ImapInterface;


/**
 * This library is a wrapper around the Imap library functions included in php. This class in particular manages a
 * connection to the server (imap, pop, etc) and allows for the easy retrieval of stored messages.
 *
 * @package Drupal\radu_imap\Plugin\Imap
 * @author  Robert Hafner <tedivm@tedivm.com>
 *
 * @Plugin(
 *   id = "imap_server",
 *   serverPath = "someString",
 *   port = "someString",
 *   service = "imap",
 *   )
 */
class Server extends PluginBase implements ImapInterface {
  /**
   * When SSL isn't compiled into PHP we need to make some adjustments to prevent soul crushing annoyances.
   *
   * @var bool
   */
  public static $sslEnable = TRUE;

  /**
   * These are the flags that depend on ssl support being compiled into imap.
   *
   * @var array
   */
  public static $sslFlags = array(
    'ssl',
    'validate-cert',
    'novalidate-cert',
    'tls',
    'notls'
  );

  /**
   * This is used to prevent the class from putting up conflicting tags. Both directions- key to value, value to key-
   * are checked, so if "novalidate-cert" is passed then "validate-cert" is removed, and vice-versa.
   *
   * @var array
   */
  public static $exclusiveFlags = array(
    'validate-cert' => 'novalidate-cert',
    'tls' => 'notls'
  );

  /**
   * This is the domain or server path the class is connecting to.
   *
   * @var string
   */
  protected $serverPath;

  /**
   * This is the name of the current mailbox the connection is using.
   *
   * @var string
   */
  protected $mailbox = '';

  /**
   * This is the username used to connect to the server.
   *
   * @var string
   */
  protected $username;

  /**
   * This is the password used to connect to the server.
   *
   * @var string
   */
  protected $password;

  /**
   * This is an array of flags that modify how the class connects to the server. Examples include "ssl" to enforce a
   * secure connection or "novalidate-cert" to allow for self-signed certificates.
   *
   * @link http://us.php.net/manual/en/function.imap-open.php
   * @var array
   */
  protected $flags = array();

  /**
   * This is the port used to connect to the server
   *
   * @var int
   */
  protected $port;

  /**
   * This is the set of options, represented by a bitmask, to be passed to the server during connection.
   *
   * @var int
   */
  protected $serverOptions = 0;

  /**
   * This is the set of connection parameters
   *
   * @var array
   */
  protected $params = array();

  /**
   * This is the resource connection to the server. It is required by a number of imap based functions to specify how
   * to connect.
   *
   * @var resource
   */
  protected $imapStream;

  /**
   * This is the name of the service currently being used. Imap is the default, although pop3 and nntp are also
   * options
   *
   * @var string
   */
  protected $service = 'imap';

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->serverPath = $configuration['serverPath'];
    if (array_key_exists('port', $configuration)) {
      $this->port = $configuration['port'];
      switch ($this->port){
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
  }

  /**
   * This function sets the username and password used to connect to the server.
   *
   * @param string $username
   * @param string $password
   * @param bool $tryFasterAuth tries to auth faster by disabling GSSAPI & NTLM auth methods (set to false if you use either of these auth methods)
   */
  public function setAuthentication($username, $password, $tryFasterAuth = TRUE) {
    $this->username = $username;
    $this->password = $password;
    if ($tryFasterAuth) {
      $this->setParam('DISABLE_AUTHENTICATOR', array('GSSAPI', 'NTLM'));
    }
  }

  /**
   * This function sets the mailbox to connect to.
   *
   * @param  string $mailbox
   * @return bool
   */
  public function setMailBox($mailbox = '') {
    if (!$this->hasMailBox($mailbox)) {
      return FALSE;
    }

    $this->mailbox = $mailbox;
    if (isset($this->imapStream)) {
      $this->setImapStream();
    }

    return TRUE;
  }

  public function getMailBox() {
    return $this->mailbox;
  }

  /**
   * This function sets or removes flag specifying connection behavior. In many cases the flag is just a one word
   * deal, so the value attribute is not required. However, if the value parameter is passed false it will clear that
   * flag.
   *
   * @param string $flag
   * @param null|string|bool $value
   */
  public function setFlag($flag, $value = NULL) {
    if (!self::$sslEnable && in_array($flag, self::$sslFlags)) {
      return;
    }

    if (isset(self::$exclusiveFlags[$flag])) {
      $kill = self::$exclusiveFlags[$flag];
    }
    elseif ($index = array_search($flag, self::$exclusiveFlags)) {
      $kill = $index;
    }

    if (isset($kill) && FALSE !== $index = array_search($kill, $this->flags)) {
      unset($this->flags[$index]);
    }

    $index = array_search($flag, $this->flags);
    if (isset($value) && $value !== TRUE) {
      if ($value == FALSE && $index !== FALSE) {
        unset($this->flags[$index]);
      }
      elseif ($value != FALSE) {
        $match = preg_grep('/' . $flag . '/', $this->flags);
        if (reset($match)) {
          $this->flags[key($match)] = $flag . '=' . $value;
        }
        else {
          $this->flags[] = $flag . '=' . $value;
        }
      }
    }
    elseif ($index === FALSE) {
      $this->flags[] = $flag;
    }
  }

  /**
   * This funtion is used to set various options for connecting to the server.
   *
   * @param  int $bitmask
   * @throws \Exception
   */
  public function setOptions($bitmask = 0) {
    if (!is_numeric($bitmask)) {
      throw new \RuntimeException('Function requires numeric argument.');
    }

    $this->serverOptions = $bitmask;
  }

  /**
   * This function is used to set connection parameters
   *
   * @param string $key
   * @param string $value
   */
  public function setParam($key, $value) {
    $this->params[$key] = $value;
  }

  /**
   * This function gets the current saved imap resource and returns it.
   *
   * @return resource
   */
  public function getImapStream() {
    if (empty($this->imapStream)) {
      $this->setImapStream();
    }

    return $this->imapStream;
  }

  /**
   * This function takes in all of the connection date (server, port, service, flags, mailbox) and creates the string
   * thats passed to the imap_open function.
   *
   * @return string
   */
  public function getServerString() {
    $mailboxPath = $this->getServerSpecification();

    if (isset($this->mailbox)) {
      $mailboxPath .= $this->mailbox;
    }

    return $mailboxPath;
  }

  /**
   * Returns the server specification, without adding any mailbox.
   *
   * @return string
   */
  protected function getServerSpecification() {
    $mailboxPath = '{' . $this->serverPath;

    if (isset($this->port)) {
      $mailboxPath .= ':' . $this->port;
    }

    if ($this->service != 'imap') {
      $mailboxPath .= '/' . $this->service;
    }

    foreach ($this->flags as $flag) {
      $mailboxPath .= '/' . $flag;
    }

    $mailboxPath .= '}';

    return $mailboxPath;
  }

  /**
   * This function creates or reopens an imapStream when called.
   *
   */
  protected function setImapStream() {
    if (!empty($this->imapStream)) {
      if (!imap_reopen($this->imapStream, $this->getServerString(), $this->serverOptions, 1)) {
        throw new \RuntimeException(imap_last_error());
      }
    }
    else {
      $imapStream = @imap_open($this->getServerString(), $this->username, $this->password, $this->serverOptions, 1, $this->params);

      if ($imapStream === FALSE) {
        throw new \RuntimeException(imap_last_error());
      }

      $this->imapStream = $imapStream;
    }
  }

  /**
   * This returns the number of messages that the current mailbox contains.
   *
   * @param  string $mailbox
   * @return int
   */
  public function numMessages($mailbox = '') {
    $cnt = 0;
    if ($mailbox === '') {
      $cnt = imap_num_msg($this->getImapStream());
    }
    elseif ($this->hasMailbox($mailbox) && $mailbox !== '') {
      $oldMailbox = $this->getMailBox();
      $this->setMailbox($mailbox);
      $cnt = $this->numMessages();
      $this->setMailbox($oldMailbox);
    }

    return ((int) $cnt);
  }

  /**
   * This function returns an array of ImapMessage object for emails that fit the criteria passed. The criteria string
   * should be formatted according to the imap search standard, which can be found on the php "imap_search" page or in
   * section 6.4.4 of RFC 2060
   *
   * @link http://us.php.net/imap_search
   * @link http://www.faqs.org/rfcs/rfc2060
   * @param  string $criteria
   * @param  null|int $limit
   * @return array    An array of ImapMessage objects
   */
  public function search($criteria = 'ALL', $limit = NULL) {
    if ($results = imap_search($this->getImapStream(), $criteria, SE_UID)) {
      if (isset($limit) && count($results) > $limit) {
        $results = array_slice($results, 0, $limit);
      }

      $messages = array();

      foreach ($results as $messageId) {
        $messages[] = new Message($messageId, $this);
      }

      return $messages;
    }
    else {
      return array();
    }
  }

  /**
   * This function returns the recently received emails as an array of ImapMessage objects.
   *
   * @param  null|int $limit
   * @return array    An array of ImapMessage objects for emails that were recently received by the server.
   */
  public function getRecentMessages($limit = NULL) {
    return $this->search('Recent', $limit);
  }

  /**
   * Returns the emails in the current mailbox as an array of ImapMessage objects.
   *
   * @param  null|int $limit
   * @return Message[]
   */
  public function getMessages($limit = NULL) {
    $numMessages = $this->numMessages();

    if (isset($limit) && is_numeric($limit) && $limit < $numMessages) {
      $numMessages = $limit;
    }

    if ($numMessages < 1) {
      return array();
    }

    $stream = $this->getImapStream();
    $messages = array();
    for ($i = 1; $i <= $numMessages; $i++) {
      $uid = imap_uid($stream, $i);
      $messages[] = new Message($uid, $this);
    }

    return $messages;
  }

  /**
   * Returns the emails in the current mailbox as an array of ImapMessage objects
   * ordered by some ordering
   *
   * @see    http://php.net/manual/en/function.imap-sort.php
   * @param  int $orderBy
   * @param  bool $reverse
   * @param  int $limit
   * @return Message[]
   */
  public function getOrderedMessages($orderBy, $reverse, $limit) {
    $msgIds = imap_sort($this->getImapStream(), $orderBy, $reverse ? 1 : 0, SE_UID);

    return array_map(array(
      $this,
      'getMessageByUid'
    ), array_slice($msgIds, 0, $limit));
  }

  /**
   * Returns the requested email or false if it is not found.
   *
   * @param  int $uid
   * @return Message|bool
   */
  public function getMessageByUid($uid) {
    try {
      $message = new \Drupal\radu_imap\Message($uid, $this);

      return $message;
    } catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * This function removes all of the messages flagged for deletion from the mailbox.
   *
   * @return bool
   */
  public function expunge() {
    return imap_expunge($this->getImapStream());
  }

  /**
   * Checks if the given mailbox exists.
   *
   * @param $mailbox
   *
   * @return bool
   */
  public function hasMailBox($mailbox) {
    return (boolean) $this->getMailBoxDetails($mailbox);
  }

  /**
   * Return information about the mailbox or mailboxes
   *
   * @param $mailbox
   *
   * @return array
   */
  public function getMailBoxDetails($mailbox) {
    return imap_getmailboxes(
      $this->getImapStream(),
      $this->getServerString(),
      $this->getServerSpecification() . $mailbox
    );
  }

  /**
   * Creates the given mailbox.
   *
   * @param $mailbox
   *
   * @return bool
   */
  public function createMailBox($mailbox) {
    return imap_createmailbox($this->getImapStream(), $this->getServerSpecification() . $mailbox);
  }

  /**
   * List available mailboxes
   *
   * @param string $pattern
   *
   * @return array
   */
  public function listMailBoxes($pattern = '*') {
    return imap_list($this->getImapStream(), $this->getServerSpecification(), $pattern);
  }

  /**
   * Deletes the given mailbox.
   *
   * @param $mailbox
   *
   * @return bool
   */
  public function deleteMailBox($mailbox) {
    return imap_deletemailbox($this->getImapStream(), $this->getServerSpecification() . $mailbox);
  }

  public function moveMailToMailBox($msglist, $mailbox, $options = 0) {
    // TODO: Implement moveMailToMailBox() method.
  }

  public function copyMailToMailBox($msglist, $mailbox, $options = 0) {
    // TODO: Implement copyMailToMailBox() method.
  }

  public function deleteMail($msg_number, $options = 0) {
    // TODO: Implement deleteMail() method.
  }

  public function undeleteMail($msg_number, $options = 0) {
    // TODO: Implement undeleteMail() method.
  }
}
