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

abstract class lcMailer extends lcResidentObj implements iProvidesCapabilities
{
    const LOG_CHANNEL = 'mail';
    const MAIL_CHARSET = 'utf-8';
    const MAIL_CONTENT_TYPE = 'text/html';
    const MAIL_ENCODING = '8bit';
    const ATTACHMENT_ENCODING = 'base64';
    /**
     * @var lcMailRecipient[]
     */
    protected $recipients;
    /**
     * @var lcMailRecipient
     */
    protected $sender;
    /**
     * @var lcMailAttachment[]
     */
    protected $attachments;
    protected $body;
    protected $subject;

    /** @var lcMailRecipient|null */
    protected $default_sender;

    public function initialize()
    {
        parent::initialize();

        $this->parseDefaultSender();

        $this->recipients = [];
        $this->attachments = [];
    }

    public function parseDefaultSender()
    {
        $sender = $this->configuration->get('mailer.default_sender');
        $email = null;
        $name = null;

        if ($sender) {
            $sender = lcStrings::splitEmail($sender);

            if ($sender) {
                $email = (isset($sender['email']) ? $sender['email'] : null);
                $name = (isset($sender['name']) ? $sender['name'] : null);
            }
        }

        $email = $email ? $email : $this->configuration->getDefaultEmailSender();

        if (!$email) {
            return null;
        }

        $this->default_sender = new lcMailRecipient($email, $name);
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
        return [
            'mailer',
        ];
    }

    public function getRecipients()
    {
        if (!count($this->recipients)) {
            return null;
        }

        return $this->recipients;
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function setSender(lcMailRecipient $sender)
    {
        $this->sender = $sender;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
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

    public function setAttachments(array $attachments = null)
    {
        $this->attachments = $attachments;
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

        if ($from) {
            $sender = new lcMailRecipient($from);
        } else {
            $sender = $this->default_sender;
        }

        $this->setSender($sender);

        $res = $this->send();

        return $res;
    }

    // TODO: Add a capability to test sending emails!
    // as we have now hidden the exceptions here!

    public function clear()
    {
        $this->recipients = [];
        $this->attachments = [];

        $this->body = null;
        $this->subject = null;
        $this->sender = null;
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

    public function send()
    {
        if (!$this->sender) {
            $this->sender = $this->getDefaultSender();
        }

        $error_message = null;

        try {
            // filter event to allow stop the sending / change the email's contents
            $res = [
                'allow_sending' => true,
                'attachments' => $this->attachments,
                'body' => $this->body,
                'recipients' => $this->recipients,
                'sender' => $this->sender,
                'subject' => $this->subject,
            ];

            // notify about this forward
            $event = new lcEvent('mailer.send_mail', $this,
                [
                    'attachments' => $this->attachments,
                    'body' => $this->body,
                    'recipients' => $this->recipients,
                    'sender' => $this->sender,
                    'subject' => $this->subject,
                ]);

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
                $this->attachments = (isset($res['attachments']) && is_array($res['attachments'])) ? $res['attachments'] : [];
                $this->body = isset($res['body']) ? $res['body'] : null;
                $this->recipients = (isset($res['recipients']) && is_array($res['recipients'])) ? $res['recipients'] : [];
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

    public function getDefaultSender()
    {
        return $this->default_sender;
    }

    abstract protected function sendMailInternal();

    abstract public function getLastError();
}