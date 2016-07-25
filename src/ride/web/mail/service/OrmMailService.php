<?php

namespace ride\web\mail\service;

use ride\library\mail\MailAddress;
use ride\library\mail\transport\Transport;
use ride\library\log\Log;
use ride\web\mail\service\MailParser;

/**
 * OrmMailService
 */
class OrmMailService {

    /**
     * The string to use when a token is not matched
     * @var string
     */
    private $noResultString;

    /**
     * @var Transport
     */
    protected $transport;

    /**
     * @var Log
     */
    protected $log;

    /**
     * Set the no result string via the dependency injector
     *
     * @var NoResult $noResultString
     */
    public function setNoResultString($noResultString = null) {
        $this->noResultString = $noResultString;
    }

    /**
     * Set transport via the dependency injector
     *
     * @var Transport $transport
     */
    public function setTransport(Transport $transport) {
        $this->transport = $transport;
    }

    /**
     * Set the log via the dependency injector
     *
     * @var Log $log
     */
    public function setLog(Log $log) {
        $this->log = $log;
    }

    /**
     * Send a mail
     *
     * @param string      $recipient  The recipient to send the mail to
     * @param MailEntry   $mail       The mail
     * @param mixed       $data       An array or Orm Entry containing data to parse the mail with
     * @param array       $cc         Optional array with CC addresses
     * @param array       $bcc        Optional array with BCC addresses
     * @param string|null $bcc        Optional rendered body which will be used instead of the mail's default body
     */
    public function sendMail($recipient, MailEntry $mail, $data = array(), $cc = array(), $bcc = array(), string $renderedBody = null) {
        $body = $renderedBody ? $renderedBody : $mail->getBody();
        $parsedBody = $this->parse($body, $data);
        $parsedSender = $this->parse($mail->getSender(), $data);
        $parsedSubject = $this->parse($mail->getSubject(), $data);

        $message = $this->transport->createMessage();
        $message->setTo($recipient);
        $message->setFrom($parsedSender);
        $message->setReplyTo($parsedSender);
        $message->setReturnPath($parsedSender);
        $message->setIsHtmlMessage(true);
        $message->setSubject($parsedSubject);
        $message->setMessage($parsedBody);

        $message->addCc($cc);

        $cc = $mail->getParsedCc($data);
        if ($cc) {
            foreach ($cc as $mailAddress) {
                $message->addCc((string) $mailAddress);
            }
        }

        $message->addBcc($bcc);

        $bcc = $mail->getParsedBcc($data);
        if ($bcc) {
            foreach ($bcc as $mailAddress) {
                $message->addBcc((string) $mailAddress);
            }
        }

        $this->transport->send($message);
        $this->log->logInformation('Mail sent', "From: {$sender} - To: {$recipient} - Subject: {$subject}", 'ormMail');
    }

    /**
     * Parse a subject
     *
     * @param  string $subject
     * @param  mixed $data
     *
     * @return string
     */
    public function parse($subject, $data) {
        $tokens = array();

        // Get all tokens
        preg_match_all('/\[\[[\w.-]+\]\]/', $subject, $tokens);

        // Loop over each token
        foreach($tokens[0] as $token) {
            $key = rtrim(ltrim($token, '[['), ']]');
            $value = $this->getParsedValue($key, $data);

            // Replace the keys in the subject with the value
            $subject = str_replace('[[' . $key . ']]', $value, $subject);
        }

        return $subject;
    }

    /**
     * Get the parsed value
     *
     * @param  string $key  The key to parse
     * @param  array  $data The data array
     *
     * @return string       The parsed key
     */
    protected function getParsedValue($key, $data) {
        // Set the value default
        $value = $this->noResultString ? $this->noResultString : '[[' . $key . ']]';

        // Fixed key
        if (is_array($data) && array_key_exists($key, $data)) {
            $value = $data[$key];
        } else {
            // Getter chain key
            $getters = explode('.', $key);
            $value = $data;
            while ($getter = current($getters)) {
                if (is_array($value)) {
                    if (!array_key_exists($getter, $value)) {
                        break;
                    }

                    $value = $value[$getter];
                } else if (is_object($value) && $value instanceof GenericEntry) {
                    $value = $value->$getter;
                } else {
                    break;
                }

                next($getters);
            }
        }

        return $value;
    }
}
