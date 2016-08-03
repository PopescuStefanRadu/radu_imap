<?php

namespace Drupal\radu_imap;

interface ImapInterface {

  /**
   * This function sets the username and password used to connect to the server.
   *
   * @param string $username
   * @param string $password
   * @param bool $tryFasterAuth tries to auth faster by disabling GSSAPI & NTLM auth methods (set to false if you use either of these auth methods)
   */
  public function setAuthentication($username, $password, $tryFasterAuth = TRUE);

  /**
   * Set mailbox to connect to (e.g. INBOX, SENT)
   *
   * @param string $mailbox
   * @return bool
   */
  public function setMailBox($mailbox);


  /**
   * @return string
   */
  public function getMailBox();

  /**
   * This function sets or removes flag specifying connection behavior. In many cases the flag is just a one word
   * deal, so the value attribute is not required. However, if the value parameter is passed false it will clear that
   * flag.
   *
   * @param string $flag
   * @param null|string|bool $value
   * @return mixed
   */
  public function setFlag($flag, $value = NULL);

  /**
   * This funtion is used to set various options for connecting to the server.
   *
   * @param  int $bitmask
   * @throws \Exception
   */
  public function setOptions($bitmask = 0);

  /**
   * This function is used to set connection parameters
   *
   * @param string $key
   * @param string $value
   */
  public function setParam($key, $value);

  /**
   * This function takes in all of the connection date (server, port, service, flags, mailbox) and creates the string
   * thats passed to the imap_open function.
   *
   * @return string
   */
  public function getServerString();

  /**
   * This returns the number of messages that the current mailbox contains.
   *
   * @param  string $mailbox
   * @return int
   */
  public function numMessages($mailbox = '');

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
  public function search($criteria = 'ALL', $limit = NULL);

  /**
   * This function returns the recently received emails as an array of ImapMessage objects.
   *
   * @param  null|int $limit
   * @return array    An array of ImapMessage objects for emails that were recently received by the server.
   */
  public function getRecentMessages($limit = NULL);

  /**
   * Returns the emails in the current mailbox as an array of ImapMessage objects.
   *
   * @param  null|int $limit
   * @return Message[]
   */
  public function getMessages($limit = NULL);

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
  public function getOrderedMessages($orderBy, $reverse, $limit);

  /**
   * Returns the requested email or false if it is not found.
   *
   * @param  int $uid
   * @return Message|bool
   */
  public function getMessageByUid($uid);

  /**
   * This function does all the actions for the messages that were flagged for
   * the actions.
   *
   * @return bool
   */
  public function expunge();

  /**
   * Checks if the given mailbox exists.
   *
   * @param $mailbox
   *
   * @return bool
   */
  public function hasMailBox($mailbox);

  /**
   * Return information about the mailbox or mailboxes
   *
   * @param $mailbox
   *
   * @return array
   */
  public function getMailBoxDetails($mailbox);

  /**
   * Creates the given mailbox.
   *
   * @param $mailbox
   *
   * @return bool
   */
  public function createMailBox($mailbox);

  /**
   * List available mailboxes
   *
   * @param string $pattern
   *
   * @return array
   */
  public function listMailBoxes($pattern = '*');

  /**
   * Deletes the given mailbox.
   *
   * @param $mailbox
   *
   * @return bool
   */
  public function deleteMailBox($mailbox);

  /**
   * Moves one or a selection of mails to the given mailbox
   *
   * @param string $msglist "1,2:4" reads as mails 1,2,3,4
   * @param string $mailbox
   * @param int $options FT_UID
   * @return bool
   */
  public function moveMailToMailBox($msglist, $mailbox, $options = 0);

  /**
   * Copies one or a selection of mails to the given mailbox
   *
   * @param string $msglist "1,2:4" reads as mails 1,2,3,4
   * @param string $mailbox
   * @param int $options FT_UID
   * @return bool
   */
  public function copyMailToMailBox($msglist, $mailbox, $options = 0);

  /**
   * Mark one or a selection of emails for deletion
   *
   * @param String $msg_number "1,2:4" reads as mails 1,2,3,4
   * @param int $options FT_UID
   * @return bool
   */
  public function deleteMail($msg_number, $options = 0);


  /**
   * Unmark one or a selection of emails for deletion
   *
   * @param string $msg_number $msg_number "1,2:4" reads as mails 1,2,3,4
   * @param int $options FT_UID
   * @return bool
   */
  public function undeleteMail($msg_number, $options = 0);
}