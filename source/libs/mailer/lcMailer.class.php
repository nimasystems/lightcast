<?php
/*
 * Lightcast - A PHP MVC Framework
* Copyright (C) 2005 Nimasystems Ltd
*
* This program is NOT free software; you cannot redistribute and/or modify
* it's sources under any circumstances without the explicit knowledge and
* agreement of the rightful owner of the software - Nimasystems Ltd.
*
* This program is distributed WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
* PURPOSE.  See the LICENSE.txt file for more information.
*
* You should have received a copy of LICENSE.txt file along with this
* program; if not, write to:
* NIMASYSTEMS LTD
* Plovdiv, Bulgaria
* ZIP Code: 4000
* Address: 95 "Kapitan Raycho" Str.
* E-Mail: info@nimasystems.com
*/

// TODO: 1.5 - Remove recipients, sender, attachments, body, subject from here and move them to an atomic lcMailMessage object which gets passed
// to the send method here

/**
 * File Description
 * @package File Category
 * @subpackage File Subcategory
 * @changed $Id: lcMailer.class.php 1547 2014-06-24 14:51:46Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1547 $
 */
abstract class lcMailer extends lcSysObj implements iProvidesCapabilities
{
    const LOG_CHANNEL = 'mail';

    protected $recipients;
    protected $sender;

    protected $attachments;

    protected $body;
    protected $subject;

    const MAIL_CHARSET = 'utf-8';
    const MAIL_CONTENT_TYPE = 'text/html';
    const MAIL_ENCODING = '8bit';
    const ATTACHMENT_ENCODING = 'base64';

    public function initialize()
    {
        parent::initialize();

        $this->recipients = array();
        $this->attachments = array();
    }

    public function shutdown()
    {
        $this->recipients =
        $this->attachments =
            null;

        parent::shutdown();
    }

    public function getCapabilities()
    {
        return array(
            'mailer'
        );
    }

    public function addRecipient(lcMailRecipient $recipient)
    {
        $email = $recipient->getEmail();

        if (!$email || !$recipient) {
            assert(false);
            return;
        }

        $this->recipients[$email] = $recipient;
    }

    public function getRecipients()
    {
        if (!count($this->recipients)) {
            return null;
        }

        return $this->recipients;
    }

    public function setSender(lcMailRecipient $sender)
    {
        $this->sender = $sender;
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function addAttachment(lcMailAttachment $attachment)
    {
        $this->attachments[] = $attachment;
    }

    public function getAttachments()
    {
        if (!count($this->attachments)) {
            return null;
        }

        return $this->attachments;
    }

    // TODO: Remove or rework this
    // It is just temporary here because lcApp sendMail method is no longer there
    public function sendMail(array $to, $message, $subject = null, $from = null)
    {
        if (!count($to) || !$message) {
            assert(false);
            return false;
        }

        $this->clear();

        foreach ($to as $email) {
            $this->addRecipient(new lcMailRecipient($email));
            unset($email);
        }

        $this->setBody($message);

        if (isset($subject)) {
            $this->setSubject($subject);
        }

        $this->setSender(new lcMailRecipient($from ? $from : $this->configuration['settings.admin_email']));

        $res = $this->send();

        return $res;
    }

    // TODO: Add a capability to test sending emails!
    // as we have now hidden the exceptions here!
    public function send()
    {
        if (!$this->sender) {
            if (isset($this->configuration['mailer']['default_sender']) && $this->configuration['mailer']['default_sender']) {
                $this->setSender(new lcMailRecipient($this->configuration['mailer']['default_sender']));
            } elseif (ini_get('sendmail_from')) {
                $this->setSender(new lcMailRecipient(ini_get('sendmail_from')));
            } else {
                $this->setSender(new lcMailRecipient('Webmaster <root@localhost>'));
            }
        }

        $error_message = null;
        $res = false;

        try {
            // filter event to allow stop the sending / change the email's contents
            $res = array(
                'allow_sending' => true,
                'attachments' => $this->attachments,
                'body' => $this->body,
                'recipients' => $this->recipients,
                'sender' => $this->sender,
                'subject' => $this->subject
            );

            // notify about this forward
            $event = new lcEvent('mailer.send_mail', $this,
                array(
                    'attachments' => $this->attachments,
                    'body' => $this->body,
                    'recipients' => $this->recipients,
                    'sender' => $this->sender,
                    'subject' => $this->subject
                ));

            $evn = $this->event_dispatcher->filter($event, $res);

            unset($event);

            if ($evn->isProcessed()) {
                $res = $evn->getReturnValue();

                // check if enabled
                if (!isset($res['allow_sending']) || !(bool)$res['allow_sending']) {
                    $this->warning('Email sending was cancelled by a notification filter');
                    return false;
                }

                // process change fields
                $this->attachments = (isset($res['attachments']) && is_array($res['attachments'])) ? $res['attachments'] : array();
                $this->body = isset($res['body']) ? $res['body'] : null;
                $this->recipients = (isset($res['recipients']) && is_array($res['recipients'])) ? $res['recipients'] : array();
                $this->sender = (isset($res['sender']) && $res['sender'] instanceof lcMailRecipient) ? $res['sender'] : null;
                $this->subject = isset($res['subject']) ? $res['subject'] : null;

                if (DO_DEBUG) {
                    $this->debug('E-Mail contents were altered by a notification filter');
                }
            }

            // send the email now
            $res = $this->sendMailInternal();
        } catch (Exception $e) {
            $error_message = $e->getMessage() . ' (' . $e->getCode() . ')';
        }

        // log it
        $logstr = '';

        foreach ($this->recipients as $email => $recipient) {
            $logstr .= $email . ', ';
            unset($email, $recipient);
        }

        $logstr = 'Mail sent ' . ($res ? 'successfully' : 'unsuccessfully') .
            ': subject: \'' . $this->subject . '\', From: ' . $this->sender->getEmail() . ', Recipient(s): ' .
            $logstr . (isset($error_message) ? ' | Error: ' . $error_message : null);

        $this->info($logstr, self::LOG_CHANNEL);

        $this->clear();

        return $res;
    }

    public function clear()
    {
        $this->recipients = array();
        $this->attachments = array();

        $this->body = null;
        $this->subject = null;
        $this->sender = null;
    }

    abstract protected function sendMailInternal();

    abstract public function getLastError();
}

?>