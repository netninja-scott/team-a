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
    protected function sendPlaxitude($recipient = '', $plaxitude = '', $category = false)
    {
        $plaxitude = $this->getPlaxitude($category);

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

    protected function getPlaxitude($category) {
        $conn = pg_connect('host=localhost port=5432 dbname=plaxitude user=img password=img1') or die('connection failed.');

        $sql = "SELECT * FROM quotes WHERE TRUE ";
        if ($category) $sql .= " AND category = '" . pg_escape_string($category) . "' ";
        $sql .= " ORDER BY RANDOM() ";
        $result = pg_fetch_assoc(pg_query($conn, $sql));

        pg_close($conn);

        return $result['text'];
    }

    /**
     * @param string $phoneNumber
     * @param string $message
     */
    protected function sendSMS($phoneNumber, $message)
    {
        $from = '13128001570';
        $callback_url = 'http://plaxitude.networkninja.com/callback.php';

        $arguments ='?from='.$from.'&to='.urlencode($phoneNumber).'&body='.urlencode($message).'&callback_url='.urlencode($callback_url);
        $url .= $arguments;

        $response = file_get_contents('http://'.$url);
        $resp = json_decode($response);

        $conn = pg_connect('host=localhost port=5432 dbname=plaxitude user=img password=img1') or die('connection failed.');
        $sql = "INSERT INTO status (message_id, recipient_name)
            VALUES ('{$resp->sid}', '{$recipient}')";
        pg_query($conn, $sql);
        pg_close($conn);
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
