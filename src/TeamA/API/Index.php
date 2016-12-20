<?php
namespace Netninja\TeamA\API;

use Netninja\TeamA\CommonAPI;
use Netninja\TeamA\Exceptions\UserNotFound;
use Zend\Mail\Message;

/**
 * Class Index
 * @package Netninja\TeamA\API
 */
class Index extends CommonAPI
{
    /**
     *
     */
    public function index()
    {

    }

    /**
     * @param string $recipient
     * @param string $plaxitude
     */
    protected function sendPlaxitude($recipient = '', $plaxitude = '')
    {
        try {
            $user = $this->searchSlackChannel($recipient);
        } catch (UserNotFound $ex) {
            $user = $this->searchAllUsers($recipient);
        }
        if (!empty($user['phone'])) {
            $this->sendSMS($user['phone'], $plaxitude);
        } elseif (!empty($user['email'])) {
            $this->sendEmail($user['email'], $plaxitude);
        }
    }

    /**
     * @param string $phoneNumber
     * @param string $message
     */
    protected function sendSMS($phoneNumber, $message)
    {

    }

    /**
     * @param string $address
     * @param string $messageBody
     */
    protected function sendEmail($address, $messageBody)
    {
        $message = new Message();
        $message->setBody($messageBody);
        $message->setTo($address);
        $message->setSubject('Plaxitude');
        $message->setFrom('alexa-plaxitude-bot@networkninja.com');

        $this->mailTransport->send($message);
    }
}
