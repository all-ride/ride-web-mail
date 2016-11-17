<?php

namespace ride\web\mail\service;

use ride\library\i18n\I18n;
use ride\library\mail\transport\Transport;
use ride\library\log\Log;
use ride\library\config\Config;
use ride\library\mvc\Request;
use ride\library\system\System;
use ride\library\template\TemplateFacade;
use ride\web\mail\orm\entry\MailEntry;
use ride\library\mail\exception\MailException;

/**
 * OrmMailService
 */
class OrmMailService {

    /**
     * The string to use when a token is not matched
     * @var string
     */
    protected $noResultString;

    /**
     * The main mail template
     * @var string
     */
    protected $mainTemplate;

    /**
     * @var Transport
     */
    protected $transport;

    /**
     * @var Log
     */
    protected $log;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var TemplateFacade
     */
    protected $templateFacade;

    /**
     * @var System
     */
    protected $system;

    /**
     * @var I18n
     */
    private $i18n;

    /**
     * @var Request
     */
    private $request;

    /**
     * OrmMailService constructor.
     *
     * @param Transport      $transport
     * @param Log            $log
     * @param Config         $config
     * @param TemplateFacade $templateFacade
     * @param System         $system
     * @param I18n           $i18n
     * @param Request        $request
     */
    public function __construct(Transport $transport, Log $log, Config $config, TemplateFacade $templateFacade, System $system, I18n $i18n, Request $request) {
        $this->transport = $transport;
        $this->log = $log;
        $this->config = $config;
        $this->templateFacade = $templateFacade;
        $this->system = $system;
        $this->i18n = $i18n;
        $this->request = $request;
    }

    /**
     * Set the no result string via the dependency injector
     *
     * @var string $noResultString
     */
    public function setNoResultString($noResultString = null) {
        $this->noResultString = $noResultString;
    }

    /**
     * @param null $mainTemplate
     */
    public function setMainTemplate($mainTemplate = null) {
        $this->mainTemplate = $mainTemplate;
    }

    /**
     * Send a mail
     *
     * @param string    $recipient The recipient to send the mail to
     * @param MailEntry $mail      The mail
     * @param mixed     $data      An array or Orm Entry containing data to parse the mail with
     * @param array     $cc        Optional array with CC addresses
     * @param array     $bcc       Optional rendered body which will be used instead of the mail's default body
     */
    public function sendMail($recipient, MailEntry $mail, $data = array(), $cc = array(), $bcc = array()) {
        $body = $mail->getBody();
        $parsedBody = $this->parse($body, $data);
        $renderedBody = $this->renderBody($parsedBody);
        $parsedSender = $this->parse((string) $mail->getSender(), $data);
        $parsedSubject = $this->parse($mail->getSubject(), $data);

        $message = $this->transport->createMessage();
        $message->setTo($recipient);
        $message->setFrom($parsedSender);
        $message->setReplyTo($parsedSender);
        $message->setReturnPath($parsedSender);
        $message->setIsHtmlMessage(true);
        $message->setSubject($parsedSubject);
        $message->setMessage($renderedBody);

        $message->addCc($cc);

        // $cc = $this->parse($mail->getCc(), $data);
        // if ($cc) {
        //     foreach ($cc as $mailAddress) {
        //         $message->addCc((string) $mailAddress);
        //     }
        // }

        $message->addBcc($bcc);

        // $bcc = $this->parse($mail->getBcc(), $data);
        // if ($bcc) {
        //     foreach ($bcc as $mailAddress) {
        //         $message->addBcc((string) $mailAddress);
        //     }
        // }

        $mailLog = "From: {$parsedSender} - Subject: {$parsedSubject} - To: {$recipient}";
        if ($this->config->get('orm.mail.send')) {
            try {
                $this->transport->send($message);
                $this->log->logInformation('Mail sent', $mailLog, 'orm.mail');
            } catch (MailException $e) {
                $this->log->logInformation('Mail failed to send', $e, 'orm.mail');
            }
        } else {
            $mailLog .= "\n";
            $mailLog .= $renderedBody;
            $mailLog .= "\n";

            $this->log->logDebug('Dummy mail sent', $mailLog, 'orm.mail');
        }
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
     * @param $body
     *
     * @return string
     */
    public function renderBody($body) {
        // TODO: This should probably be a separate service
        if (!$this->mainTemplate) {
            return $body;
        }

        $variables = array (
            'app' => array (
                'locale' => $this->i18n->getLocale()->getCode(),
                'url' => $this->request->getBaseUrl(),
                'system' => $this->system,
            ),
            'content' => $body,
        );

        return $this->templateFacade->render($this->templateFacade->createTemplate($this->mainTemplate, $variables));
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
        $value = $this->noResultString ? $this->noResultString : '[[' . $key . ']]';

        if (is_array($data) && array_key_exists($key, $data)) {
            $value = $data[$key];
        }

        return $value;
    }
}
