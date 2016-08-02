<?php
/**
 * Created by PhpStorm.
 * User: radu
 * Date: 8/2/16
 * Time: 12:41 PM
 */

namespace Drupal\radu_imap\Tests\Plugin;

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

    public function testComplexTest()
    {

        // Test mailbox creation
        $this->imapBase->createMailBox("EDW2");
        if ($this->imapBase->hasMailBox("INBOX.EDW2")) {
            $this->pass("EDW2 mailbox was created");
        } else {
            $this->fail("EDW2 mailbox was not created");
        }

        //Get mail from mailbox
        $this->imapBase->setMailBox("INBOX.EDW");
        /** @var Message[] $messages */
        $messages = $this->imapBase->getOrderedMessages(SORTDATE, false, 1);
        array_filter($messages);
        if (!empty($messages)){
            $this->pass('Email was successfully received');
        } else {
            $this->fail('Mailbox empty or error');
        }

        //Attachment save check
        foreach ($messages as $message) {
            /** @var Attachment[] $attachments */
            $attachments = $message->getAttachments();
            foreach ($attachments as $attachment) {
                mkdir($this->publicFilesDirectory);
                $attachment->saveToDirectory($this->publicFilesDirectory);
                $path = $this->publicFilesDirectory . $attachment->getFileName();
                if (is_file($path)){
                    $this->pass("The attachment was successfully saved");
                    if (unlink($path)){
                        $this->pass("The attachment was successfully removed");
                    } else {
                        $this->fail("The attachment was not removed after being saved to:" .$path);
                    }
                    break;
                } else {
                    $this->fail("The attachment was not saved. Path:". $path);
                }
            }
        }

        //Test mailbox deletion
        $this->imapBase->deleteMailBox("INBOX.EDW2");
        if ($this->imapBase->hasMailBox("INBOX.EDW2")) {
            $this->fail("EDW2 mailbox was not deleted");
        } else {
            $this->pass("EDW2 mailbox was successfully deleted");
        }
    }
}