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

use PHPMailer\PHPMailer\PHPMailer;

class lcPHPMailer extends lcMailer
{
    const DEFAULT_LANGUAGE = 'en';
    const DEFAULT_SMTP_HOST = '127.0.0.1';
    const DEFAULT_SMTP_PORT = 25;
    /** @var lcMailRecipient[] */
    protected $cc_recipients;
    /** @var lcMailRecipient[] */
    protected $bcc_recipients;
    /** @var lcMailRecipient */
    protected $reply_to_address;
    /** @var string */
    protected $alt_body;
    private $last_error;
    private $enable_debugging;

    public function initialize()
    {
        parent::initialize();

        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            throw new lcSystemException('SystemMailer requires PHPMailer');
        }

        $this->enable_debugging = (bool)$this->configuration['mailer.debug'];
    }

    public function setEnableDebugging($enabled = true)
    {
        $this->enable_debugging = $enabled;
    }

    public function getLastError()
    {
        return $this->last_error;
    }

    public function setCCRecipients(array $recipients)
    {
        $this->cc_recipients = $recipients;
    }

    public function setBCCRecipients(array $recipients)
    {
        $this->bcc_recipients = $recipients;
    }

    public function setReplyTo(lcMailRecipient $recipient)
    {
        $this->reply_to_address = $recipient;
    }

    public function setAltBody($alt_body)
    {
        $this->alt_body = $alt_body;
    }

    public function clear()
    {
        parent::clear();

        $this->cc_recipients = null;
        $this->bcc_recipients = null;
        $this->reply_to_address = null;
        $this->alt_body = null;
    }

    protected function sendMailInternal()
    {
        if (!$this->getRecipients()) {
            throw new lcMailException('No Recipients set');
        }

        if (!$this->getSender()) {
            throw new lcMailException('Sender not set');
        }

        // check if in testing mode
        if ((bool)$this->configuration['mailer.testing_mode']) {
            return true;
        }

        ///////// PHP Mailer extension
        $mailer = new PHPMailer(true /* exceptions instead of echo - die; */);

        if (isset($this->configuration['mailer.language']) && ($this->configuration['mailer.language'])) {
            $mailer->SetLanguage(
                (string)$this->configuration['mailer.language'],
                $this->configuration->getThirdPartyDir() . DS .
                'PHPMailer' . DS . 'language' . DS);
        }

        // set configuration
        if (isset($this->configuration['mailer.charset']) && ($this->configuration['mailer.charset'])) {
            $mailer->CharSet = (string)$this->configuration['mailer.charset'];
        }

        if (isset($this->configuration['mailer.content_type']) && ($this->configuration['mailer.content_type'])) {
            $mailer->ContentType = (string)$this->configuration['mailer.content_type'];
        }

        if (isset($this->configuration['mailer.encoding']) && ($this->configuration['mailer.encoding'])) {
            $mailer->Encoding = (string)$this->configuration['mailer.encoding'];
        }

        $mailer->From = $this->getSender()->getEmail();
        $mailer->Sender = $this->getSender()->getEmail();
        $mailer->FromName = $this->getSender()->getName();
        $mailer->Subject = $this->getSubject();
        $mailer->IsHTML(true);
        $mailer->AltBody = ($this->alt_body ? $this->alt_body : strip_tags($this->getBody()));

        $mailer_type = $this->getMailerType();
        $mailer_type = $mailer_type == 'php' || $mailer_type == 'phpmail' ? 'mail' : $mailer_type;
        $mailer->Mailer = $mailer_type;

        // check mail type
        if ($mailer->Mailer == 'smtp' && !function_exists('socket_create')) {
            $this->warning('Mailer was unable to use SMTP mode as there is no PHP sockets support on this system - falling back to default php mail function');

            $mailer->Mailer = 'mail';
        }

        // set confirmation to
        $mailer->ConfirmReadingTo = isset($this->configuration['mailer.confirm_to']) && ($this->configuration['mailer.confirm_to']) ?
            $this->configuration['mailer.confirm_to'] :
            null;

        # parse global variables
        $mailer->Body = $this->getBody();

        // obtain the hostname
        $hostname = lcSys::getHostname();

        assert(!empty($hostname));

        if ($hostname) {
            $mailer->Hostname = $hostname;
            $mailer->Helo = $hostname;
        }

        if ($mailer->Mailer == 'smtp') {
            // set security
            $mailer->SMTPSecure = isset($this->configuration['mailer.security']) && is_string($this->configuration['mailer.security']) ?
                (string)$this->configuration['mailer.security'] :
                null;

            $mailer->Host = isset($this->configuration['mailer.smtp_host']) && $this->configuration['mailer.smtp_host'] ?
                (string)$this->configuration['mailer.smtp_host'] : self::DEFAULT_SMTP_HOST;
            $mailer->Port = isset($this->configuration['mailer.smtp_port']) && $this->configuration['mailer.smtp_port'] ?
                (int)$this->configuration['mailer.smtp_port'] : self::DEFAULT_SMTP_PORT;

            if (isset($this->configuration['mailer.smtp_user']) && $this->configuration['mailer.smtp_user']) {
                $mailer->SMTPAuth = true;
                $mailer->Username = (string)$this->configuration['mailer.smtp_user'];

                // check if there is an username which is required in this case
                $mailer->Password = $mailer->Username && isset($this->configuration['mailer.smtp_pass']) && ($this->configuration['mailer.smtp_pass']) ?
                    (string)$this->configuration['mailer.smtp_pass'] : null;
            }
        }

        $mailer->SMTPDebug = $this->enable_debugging;

        # add attachments if any
        if ($attachments = $this->getAttachments()) {
            $attachment_encoding = isset($this->configuration['mailer.attachment_encoding']) && ($this->configuration['mailer.attachment_encoding']) ?
                (string)$this->configuration['mailer.attachment_encoding'] :
                'base64';

            foreach ($attachments as $attachment) {
                $mailer->AddAttachment(
                    $attachment->getFilePath(),
                    $attachment->getFilename(),
                    $attachment_encoding,
                    $attachment->getMimetype()
                );
            }

            unset($attachment);
        }

        unset($attachments);

        // add the recipients
        $recipients = $this->getRecipients();

        foreach ($recipients as $recipient) {
            $mailer->AddAddress($recipient->getEmail(), $recipient->getName());

            unset($recipient);
        }

        unset($recipients);

        // cc
        if ($this->cc_recipients) {
            foreach ($this->cc_recipients as $recipient) {
                $mailer->addCC($recipient->getEmail(), $recipient->getName());
                unset($recipient);
            }
        }

        // bcc
        if ($this->bcc_recipients) {
            foreach ($this->bcc_recipients as $recipient) {
                $mailer->addBCC($recipient->getEmail(), $recipient->getName());
                unset($recipient);
            }
        }

        // reply-to
        if ($this->reply_to_address) {
            $mailer->addReplyTo($this->reply_to_address->getEmail(), $this->reply_to_address->getName());
        }

        // PHPMail outputs raw content in case of errors! we must hide it
        ob_start();

        // send the email
        $ret = $mailer->Send();

        ob_end_clean();

        $this->last_error = $mailer->ErrorInfo;

        unset($mailer);

        return $ret;
    }

    private function getMailerType()
    {
        return $this->configuration['mailer.use'] ? $this->configuration['mailer.use'] : 'mail';
    }
}
