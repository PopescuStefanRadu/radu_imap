<?php
/**
 * Created by PhpStorm.
 * User: radu
 * Date: 8/2/16
 * Time: 12:41 PM
 */

namespace Drupal\radu_imap\Tests\Plugin;

use Drupal\Core\File\FileSystem;
use Drupal\radu_imap\Attachment;
use Drupal\radu_imap\ImapPluginManager;
use Drupal\radu_imap\Message;
use Drupal\radu_imap\Plugin\Imap\ImapBase;
use Drupal\views\Tests\Plugin\PluginTestBase;

/**
 * @group radu_imap
 */
class ImapBaseTest extends PluginTestBase
{

    /**
     * @var  ImapBase $imapBase
     */
    public $imapBase;

    protected $profile = 'testing';

    public static $modules = ['radu_imap', 'node', 'devel', 'kint'];

    public function setUp()
    {
        parent::setUp();

        $configuration = array(
            "serverPath" => "secure.emailsrvr.com",
            "port" => 993,
            "username" => "radu.popescu@eaudeweb.ro",
            "password" => "Datedreq333",
        );


        /**
         * @var ImapPluginManager $imapPluginManager
         */
        $imapPluginManager = \Drupal::service('plugin.manager.imap');
        $pluginDef = $imapPluginManager->getDefinition('radu_imap_server');
        $this->imapBase = $imapPluginManager->createInstance($pluginDef['id'], $configuration);
    }

    public function testListMailBoxes()
    {
        $serverString = $this->imapBase->getServerString();

        // Test mailbox listing
        $mailBoxes = $this->imapBase->listMailBoxes();
        if (in_array($serverString . 'INBOX.EDW', $mailBoxes)) {
            $this->pass('The INBOX.EDW folder was found');
        } else {
            $this->verbose(var_export($mailBoxes, true));
            $this->fail("The INBOX.EDW mailbox was not found");
        }
    }


    public function testGetOderedMessages()
    {

        //Test getOrderedMessages
        $this->imapBase->setMailBox("INBOX.EDW");
        /** @var Message[] $messages */
        $messages = $this->imapBase->getOrderedMessages(SORTDATE, false, 1);
        array_filter($messages);
        if (!empty($messages)) {
            $this->pass('Email was successfully received');
        } else {
            $this->fail('Mailbox empty or error');
        }
    }

    public function testAttachmentSave()
    {
        $dirPath = DRUPAL_ROOT . DIRECTORY_SEPARATOR . $this->publicFilesDirectory;

        $this->imapBase->setMailBox("INBOX.EDW");
        $messages = $this->imapBase->getOrderedMessages(SORTDATE, false, 1);
        $messages = array_values($messages);
        $message = reset($messages);

        /** @var Attachment[] $attachments */
        $attachments = $message->getAttachments();
        $attachments = array_values($attachments);
        $attachment = reset($attachments);
        $attachment->saveToDirectory($dirPath);

        $filePath = $dirPath . DIRECTORY_SEPARATOR . $attachment->getFileName();
        if (is_file($filePath)) {
            $this->pass("The attachment was successfully saved");
            if (unlink($filePath)) {
                $this->pass("The attachment was successfully removed");
            } else {
                $this->fail("The attachment was not removed after being saved to:" . $filePath);
            }
        } else {
            $this->fail("The attachment was not saved. Path:" . $filePath);
        }
    }

    public function testGetMessageByUid()
    {
        $this->imapBase->setMailBox("INBOX.EDW");
        $messages = $this->imapBase->getOrderedMessages(SORTDATE, false, 1);
        $messages = array_values($messages);
        $message = reset($messages);
        $msgUid = $message->getUid();
        $msg2 = $this->imapBase->getMessageByUid($msgUid);
        if ($msg2 == false) {
            $this->fail("Could not get message by Uid");
        } else {
            $this->pass("getMessageByUid(messageUid) works");
        }
    }

    public function testCopyToMailBox()
    {
        $this->imapBase->setMailBox("INBOX.EDW");

        //Test createMailBox
        $this->imapBase->createMailBox("EDW2");
        if ($this->imapBase->hasMailBox("INBOX.EDW2")) {
            $this->pass("EDW2 mailbox was created");
        } else {
            $this->fail("EDW2 mailbox was not created");

        }

        $messages = $this->imapBase->getOrderedMessages(SORTDATE, false, 1);
        $messages = array_values($messages);
        $message = reset($messages);

        //Test copyToMailBox
        $message->copyToMailBox("INBOX.EDW2");
        $this->imapBase->setMailBox("INBOX.EDW2");
        $msg2 = $this->imapBase->getMessages(1);
        if (!empty($msg2)) {
            $msg2 = reset($msg2);
            if ($message->getMessageBody() == $msg2->getMessageBody()) {
                $this->pass('Message was correctly copied');
            } else {
                $this->fail("Message was not correctly copied");
            }
        } else {
            $this->fail("Message was not copied");
        }

        //Test delete message
        $msg2->delete();
        $this->imapBase->setMailBox("INBOX.EDW2");
        $this->imapBase->expunge();
        $this->assertEqual(0, $this->imapBase->numMessages("INBOX.EDW2"), 'Message was successfully deleted');

        //Test mailbox deletion
        $this->imapBase->deleteMailBox("INBOX.EDW2");
        if ($this->imapBase->hasMailBox("INBOX.EDW2")) {
            $this->fail("EDW2 mailbox was not deleted");
        } else {
            $this->pass("EDW2 mailbox was successfully deleted");
        }

    }

    public function testMoveToMailBox()
    {
        $this->imapBase->createMailBox("EDW2");

        $this->imapBase->setMailBox("INBOX.EDW");
        $messages = $this->imapBase->getMessages(1);
        $messages = array_values($messages);

        /** @var Message $message */
        if (!empty($messages)) {
            $message = reset($messages);
        } else {
            throw new \Exception("no message found");
        }

        //Test message move
        $this->imapBase->setMailBox("EDW");
        $message->moveToMailBox("EDW2");

        $this->assertEqual(0, $this->imapBase->numMessages("EDW"), 'Message no longer in INBOX.EDW');
        $this->assertEqual(1, $this->imapBase->numMessages("EDW2"), 'Message moved to INBOX.EDW2');


        $this->imapBase->setMailBox('EDW2');
        $msg2 = $this->imapBase->getMessages(1);
        /** @var Message $msg2 */
        $msg2 = array_shift($msg2);
        $msg2->moveToMailBox("EDW");
        $this->assertEqual(1, $this->imapBase->numMessages("EDW"), 'Message is back in INBOX.EDW');

        $this->imapBase->deleteMailBox("INBOX.EDW2");
    }

}