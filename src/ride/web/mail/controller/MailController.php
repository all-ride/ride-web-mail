<?php

namespace ride\web\mail\controller;

use ride\application\system\System;

use ride\library\orm\OrmManager;
use ride\library\validation\exception\ValidationException;
use ride\library\template\TemplateFacade;

use ride\web\base\controller\AbstractController;
use ride\web\form\WebForm;
use ride\web\mail\service\OrmMailService;

/**
 * Class MailController
 */
class MailController extends AbstractController {

    /**
     * @var string
     */
    const PROPERTY_EMAILS = 'emails';

    /**
     * @var string
     */
    const PROPERTY_FROM_NAME = 'from_name';

    /**
     * @var string
     */
    const PROPERTY_SUBJECT = 'subject';

    /**
     * @var string
     */
    const PROPERTY_FROM = 'from';

    /**
     * @var string
     */
    const PROPERTY_BODY = 'body';

    /**
     * @var string
     */
    const PARAM_REDIRECT = 'referer';

    /**
     * @var string
     */
    const PARAM_EMAILS = 'emails';

    /**
     * @var string
     */
    const VARIABLES = 'variables';

    /**
     * Cached data
     *
     * @var array
     */
    private $data;

    /**
     * @var OrmManager
     */
    protected $orm;

    /**
     * @var OrmMailService
     */
    protected $ormMailService;

    /**
     * @var System
     */
    protected $system;

    /**
     * @var TemplateFacade
     */
    protected $templateFacade;

    /**
     * Construct
     * @param OrmManager     $orm
     * @param OrmMailService $ormMailService
     * @param System         $system
     * @param TemplateFacade $templateFacade
     */
    public function __construct(OrmManager $orm, OrmMailService $ormMailService, System $system, TemplateFacade $templateFacade) {
        $this->orm = $orm;
        $this->ormMailService = $ormMailService;
        $this->system = $system;
        $this->templateFacade = $templateFacade;
    }

    /**
     * @var array
     */
    protected $providers;

    /**
     * Set the available variable providers via the dependency injector
     *
     * @var array $providers
     */
    public function setVariableProviders(array $providers) {
        $this->providers = $providers;
    }

    /**
     * sendAction
     */
    public function sendAction() {
        $form = $this->buildMailForm();

        if ($this->handleMailForm($form)) {
            if ($this->getData()[self::PARAM_REDIRECT]) {
                $url = $this->getData()[self::PARAM_REDIRECT];
            } else {
                $url = $this->request->getUrl();
            }

            $this->addSuccess('success.mails.sent');
            $this->response->setRedirect($url);

            return;

        }

        $view = $this->setTemplateView('web/mail/index', array(
            'form' => $form->getView(),
            'variables' => $this->getData()[self::VARIABLES],
            'redirect' => $this->getData()[self::PARAM_REDIRECT],
        ));

        $form->processView($view);
    }

    /**
     * Build the mailform
     *
     * @return WebForm
     */
    protected function buildMailForm() {
        $translator = $this->getTranslator();
        $form = $this->createFormBuilder($this->getData());
        $mails = $this->orm->getMailModel()->find();

        $form->addRow(self::PROPERTY_SUBJECT, 'string', array(
            'label' => $translator->translate('label.subject'),
            'validators' => array(
                'required' => array(),
            ),
        ));

        $form->addRow(self::PROPERTY_FROM_NAME, 'string', array(
            'label' => $translator->translate('label.mail.from.name'),
            'description' => $translator->translate('label.mail.from.name.description'),
        ));

        $form->addRow(self::PROPERTY_FROM, 'string', array(
            'label' => $translator->translate('label.mail.from'),
            'description' => $translator->translate('label.mail.from.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));

        $form->addRow(self::PROPERTY_EMAILS, 'text', array(
            'label' => $translator->translate('label.mail.subjects'),
            'description' => $translator->translate('label.mail.subjects.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));

        $form->addRow(self::PROPERTY_BODY, 'wysiwyg', array(
            'label' => $translator->translate('label.mail.body'),
            'validators' => array(
                'required' => array(),
            ),
        ));

        return $form->build();
    }

    /**
     * Handle the mail form
     *
     * @param  WebForm $form
     *
     * @return boolean
     */
    protected function handleMailForm(WebForm $form) {
        if (!$form->isSubmitted() || $this->request->getBodyParameter('cancel')) {
            return false;
        }

        try {
            $form->validate();
            $data = $form->getData();

            $sender = $this->orm->getMailAddressModel()->createEntry();
            $sender->setEmail($data[self::PROPERTY_FROM]);
            $senderName = $data[self::PROPERTY_FROM_NAME] ? $data[self::PROPERTY_FROM_NAME] : $data[self::PROPERTY_FROM];
            $sender->setDisplayName($senderName);

            $mail = $this->orm->getMailModel()->createEntry();
            $mail->setSender($sender);
            $mail->setSubject($data[self::PROPERTY_SUBJECT]);
            $mail->setBody($data[self::PROPERTY_BODY]);

            $emails = $data[self::PROPERTY_EMAILS];
            foreach ($emails as $email) {

                // $subscription = $this->orm->getEventSubscriptionModel()->getBy(array('filter' => array('event' => $event, 'subscriber.email' => trim($email))));
                // if ($subscription) {
                //     $variables += $this->subscriptionDecorator->decorate($subscription);
                // }

                // $this->sendMail($email, $variables, 'cenweb/mail/index', $mail);
            }

            return true;
        } catch (ValidationException $e) {
            $this->setValidationException($e, $form);
        }

        return false;
    }

    /**
     * Get the data
     *
     * @return array
     */
    protected function getData() {
        if (!$this->data) {
            $this->data = array(
                self::PARAM_REDIRECT => null,
                self::PROPERTY_EMAILS => array(),
                self::PROPERTY_FROM_NAME => $this->config->get('mail.default.from.name'),
                self::PROPERTY_FROM => $this->config->get('mail.default.from.email'),
            );

            $emails = array();

            if ($emails = $this->request->getQueryParameter(self::PARAM_EMAILS)) {
                $this->data[self::PROPERTY_EMAILS] = explode(', ', $emails);
            }

            foreach ($this->providers as $provider) {
                $this->data[self::VARIABLES][$provider->getModel()] = $provider->getAvailableVariables();
            }
        }

        return $this->data;
    }

    /**
     * Send the mail
     *
     * @param  string      $recipient
     * @param  MailEntry   $mail
     * @param  array       $data
     * @param  string|null $template
     */
    protected function sendMail(string $recipient, MailEntry $mail, array $data, string $template = null) {
        $renderedBody = null;

        if ($template) {
            $variables = array(
                'app' => array(
                    'locale' => $this->getTranslator()->getLocale(),
                    'url' => $this->request->getBaseUrl(),
                    'system' => $this->system,
                ),
                'content' => $mail->getBody(),
            );

            $renderedBody = $this->templateFacade->render($this->templateFacade->createTemplate($template, $variables));
        }

        $this->ormMailService->sendMail($recipient, $mail, $data, $renderedBody);
    }
}
