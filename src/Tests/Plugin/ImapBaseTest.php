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

    public function testComplexTest()
    {

        //Test createMailBox
        $this->imapBase->createMailBox("EDW2");
        if ($this->imapBase->hasMailBox("INBOX.EDW2")) {
            $this->pass("EDW2 mailbox was created");
        } else {
            $this->fail("EDW2 mailbox was not created");
        }

        //Test getOrderedMessages
        $this->imapBase->setMailBox("INBOX.EDW");
        /** @var Message[] $messages */
        $messages = $this->imapBase->getOrderedMessages(SORTDATE, false, 1);
        array_filter($messages);
        if (!empty($messages)){
            $this->pass('Email was successfully received');
        } else {
            $this->fail('Mailbox empty or error');
        }

        //Test attachment save
        foreach ($messages as $message) {
            /** @var Attachment[] $attachments */
            $attachments = $message->getAttachments();
            foreach ($attachments as $attachment) {
                $dirPath = DRUPAL_ROOT .DIRECTORY_SEPARATOR . $this->publicFilesDirectory;
                $attachment->saveToDirectory($dirPath);
                $filePath = $dirPath . DIRECTORY_SEPARATOR . $attachment->getFileName();

                if (is_file($filePath)){
                    $this->pass("The attachment was successfully saved");
                    if (unlink($filePath)){
                        $this->pass("The attachment was successfully removed");
                    } else {
                        $this->fail("The attachment was not removed after being saved to:" . $filePath);
                    }
                    break;
                } else {
                    $this->fail("The attachment was not saved. Path:". $filePath);
                }
            }
        }


        //Test getMessageByUid
        foreach ($messages as $message){
            $msgUid = $message->getUid();
            $msg2 =$this->imapBase->getMessageByUid($msgUid);
            if ($msg2 == false) {
                $this->fail("Could not get message by Uid");
            } else {
                $this->pass("getMessageByUid(messageUid) works");
            }
        }


        //Test mail copy to mailbox
        foreach ($messages as $message){
            $message->copyToMailBox("INBOX.EDW2");
            $this->imapBase->setMailBox("INBOX.EDW2");
            $msg2=$this->imapBase->getMessages(1);
            if (!empty($msg2)){
                $msg2 = reset($msg2);
                if ($message->getMessageBody() == $msg2->getMessageBody()) {
                    $this->pass('Message was correctly copied');
                } else {
                    $this->fail("Message was not correctly copied");
                }
            } else {
                $this->fail("Message was not copied");
            }


            //Test message delete
            $msg2->delete();
            $this->imapBase->setMailBox("INBOX.EDW2");
            $this->imapBase->expunge();
            $this->assertEqual(0,$this->imapBase->numMessages("INBOX.EDW2"),'Message was successfully deleted');

            //Test message move
            $this->imapBase->setMailBox("EDW");
            $result = $message->moveToMailBox("EDW2");
            $this->pass("movetomailbox " .$result);
            $this->assertEqual(0,$this->imapBase->numMessages("EDW"), 'Message no longer in INBOX.EDW');
            $this->assertEqual(1,$this->imapBase->numMessages("EDW2"),'Message moved to INBOX.EDW2');
//            $message->moveToMailBox("EDW");
        }

        //Test mailbox deletion
//        $this->imapBase->deleteMailBox("INBOX.EDW2");
//        if ($this->imapBase->hasMailBox("INBOX.EDW2")) {
//            $this->fail("EDW2 mailbox was not deleted");
//        } else {
//            $this->pass("EDW2 mailbox was successfully deleted");
//        }
    }
}