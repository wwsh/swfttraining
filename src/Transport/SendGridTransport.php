<?php

namespace App\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Swift_Events_EventListener as SwiftEventListener;
use Swift_Mime_SimpleMessage as SwiftSimpleMessage;

/**
 * SendGrid support in SwiftMailer
 * @package App\Transport
 */
class SendGridTransport implements \Swift_Transport
{
    /** @var Client */
    protected $httpConnector;

    /**
     * @param Client $httpConnector
     */
    public function __construct(Client $httpConnector)
    {
        $this->httpConnector = $httpConnector;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        // TODO: Implement isStarted() method.
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        // TODO: Implement start() method.
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        // TODO: Implement stop() method.
    }

    /**
     * {@inheritdoc}
     */
    public function ping()
    {
        // TODO: Implement ping() method.
    }

    /**
     * {@inheritdoc}
     */
    public function send(SwiftSimpleMessage $message, &$failedRecipients = null)
    {
        $this->httpConnector->request(
            'POST',
            '/mail/send',
            [
                RequestOptions::JSON => $this->buildPostSendRequest($message)
            ]
        );

        return $this->countSentEmails($message);
    }

    /**
     * {@inheritdoc}
     */
    public function registerPlugin(SwiftEventListener $plugin)
    {
        // TODO: Implement registerPlugin() method.
    }

    /**
     * Returning an assoc array of a POST request body.
     *
     * @param SwiftSimpleMessage $message
     * @return array
     */
    protected function buildPostSendRequest(SwiftSimpleMessage $message)
    {
        $tos = (array)$message->getTo();
        $ccs = (array)$message->getCc();
        $bccs = (array)$message->getBcc();

        $subject = $message->getSubject();

        $personalizations = [];

        $tos = $this->buildEmailNameArray($tos);
        $ccs = $this->buildEmailNameArray($ccs);
        $bccs = $this->buildEmailNameArray($bccs);

        $this->buildPersonalizationEnvelope($personalizations, 'to', $tos, $subject);
        $this->buildPersonalizationEnvelope($personalizations, 'cc', $ccs, $subject);
        $this->buildPersonalizationEnvelope($personalizations, 'bcc', $bccs, $subject);

        $from = $this->buildFrom($message);

        $reply_to = [
            'email' => $message->getReplyTo()
        ];

        $content = [
            [
                'type' => 'text/html',
                'value' => $message->getBody()
            ]
        ];

        return compact('personalizations', 'from', 'reply_to', 'subject', 'content');
    }

    /**
     * @param array $recipients
     * @return array|null
     */
    protected function buildEmailNameArray(array $recipients)
    {
        if (empty($recipients)) {
            return null;
        }

        $result = [];

        foreach ($recipients as $email => $name) {
            $result[] = compact('email', 'name');
        }

        return $result;
    }

    /**
     * @param $personalizations
     * @param $envelopeName
     * @param $recipients
     * @param $subject
     */
    protected function buildPersonalizationEnvelope(
        array &$personalizations,
        $envelopeName,
        $recipients,
        $subject
    ) {
        if (empty($recipients)) {
            return;
        }

        $personalizations[] = [
            $envelopeName => $recipients,
            'subject' => $subject
        ];
    }

    /**
     * @param SwiftSimpleMessage $message
     * @return array
     */
    protected function buildFrom(SwiftSimpleMessage $message)
    {
        $email = key($message->getFrom());
        $name = current($message->getFrom());

        return compact('email', 'name');
    }

    /**
     * Returns number of sent emails, based on request data.
     *
     * @param SwiftSimpleMessage $message
     * @return int
     */
    protected function countSentEmails(SwiftSimpleMessage $message)
    {
        $count = (count((array)$message->getTo()) + count((array)$message->getCc()) + count((array)$message->getBcc()));

        return $count;
    }
}
