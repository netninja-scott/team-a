<?php
namespace Netninja\TeamA;

use GuzzleHttp\Client as HTTPClient;
use Netninja\TeamA\Exceptions\APIException;
use Netninja\TeamA\Exceptions\UserNotFound;
use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use Zend\Mail\Message;
use Zend\Mail\Transport\Sendmail;

/**
 * Class CommonAPI
 * @package Netninja\TeamA
 */
class CommonAPI
{
    /**
     * @var resource (Pgsql connection)
     */
    protected $conn;

    /**
     * @var EasyDB
     */
    protected $db;

    /**
     * @var HTTPClient
     */
    protected $guzzle;

    /**
     * @var Sendmail
     */
    protected $mailTransport;

    /**
     * CommonAPI constructor.
     */
    public function __construct()
    {
        $this->guzzle = new HTTPClient();
        $this->mailTransport = new Sendmail();

        $conn = \json_decode(\file_get_contents(TEAMA_ROOT . '/database.json'), true);
        $port = !empty($conn['port']) ? $conn['port'] : 5432;
        $this->db = Factory::create(
            'pgsql:host=' . $conn['host'] . ';dbname=' . $conn['database'] . ';port=' . $port,
            $conn['username'],
            $conn['password']
        );
    }

    /**
     * Search a slack channel for a user, by name.
     *
     * @param string $name
     * @param string $channel
     * @return array
     * @throws \InvalidArgumentException
     * @throws APIException
     * @throws UserNotFound
     */
    protected function searchSlackChannel($name = '', $channel = 'team_a_is_the_best')
    {
        if (!\is_string($name)) {
            throw new \InvalidArgumentException('Name must be a string');
        }
        $response = (string) $this->slackGetRequest(
            'channels.info',
            [
                'channel' => $channel
            ]
        );

        $channel = \json_decode($response, true);
        if (!$channel['ok']) {
            throw new APIException($channel['error']);
        }
        foreach ($channel['channel']['members'] as $member) {
            if ($this->userMatch($name, $member)) {
                return $member['profile'];
            }
        }
        foreach ($channel['members'] as $member) {
            if ($this->userMatch($name, $member, true)) {
                return $member['profile'];
            }
        }
        throw new UserNotFound('User not found in remote API');
    }

    /**
     * Search all users, by name.
     *
     * @param string $name
     * @return array
     * @throws APIException
     * @throws UserNotFound
     */
    protected function searchAllUsers($name = '')
    {
        if (!\is_string($name)) {
            throw new \InvalidArgumentException('Name must be a string');
        }
        $response = (string) $this->slackGetRequest('users.list');
        $users = \json_decode($response, true);
        if (!$users['ok']) {
            throw new APIException($users['error']);
        }
        foreach ($users['members'] as $member) {
            if ($this->userMatch($name, $member)) {
                return $member['profile'];
            }
        }
        foreach ($users['members'] as $member) {
            if ($this->userMatch($name, $member, true)) {
                return $member['profile'];
            }
        }
        throw new UserNotFound('User not found in remote API');
    }

    /**
     * Does a member object match this name?
     *
     * @param string $searchName
     * @param array $memberObject
     * @param bool $firstOnyl
     * @return bool
     */
    protected function userMatch($searchName, array $memberObject = [], $firstOnly = false)
    {
        $searchName =\strtolower($searchName);
        if (\strtolower($memberObject['name']) === $searchName) {
            return true;
        }
        if (\strtolower($memberObject['profile']['real_name']) === $searchName) {
            return true;
        }
        if (\strpos($searchName, ' ') !== false) {
            list($first, $last) = \explode(' ', \trim($searchName));
            if (
                \strtolower($memberObject['profile']['first_name']) === $first
                &&
                \strtolower($memberObject['profile']['last_name']) === $last
            ) {
                return true;
            }

            // Optional "first name only" match:
            if (
                $firstOnly
                    &&
                \strtolower($memberObject['profile']['first_name']) === $first
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Perform an HTTP request to Slack.
     *
     * @param string $endpoint
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function slackGetRequest($endpoint = '', array $args = [])
    {
        $args['token'] = $this->getSlackAPIToken();
        return $this->guzzle->request(
            'GET',
            'https://slack.com/api/' . $endpoint . '?' . \http_build_query($args)
        );
    }

    /**
     * @return string
     */
    protected function getRandomPlaxitudeCategory()
    {
        static $categories = null;
        if (!$categories) {
            $results = $this->db->run("SELECT DISTINCT category FROM quotes ORDER BY category ASC");
            foreach ($results as $res) {
                $categories[] = $res['category'];
            }
        }
        $r = \random_int(0, \count($categories) - 1);
        return $categories[$r];
    }

    /**
     * @param string $category
     * @return string
     */
    protected function getRandomPlaxitude($category)
    {
        if ($category) {
            $result = $this->db->run(
                "SELECT * FROM quotes WHERE category = $1 ORDER BY RANDOM() ",
                $category
            );
        } else {
            $result = $this->db->run("SELECT * FROM quotes ORDER BY RANDOM() ");
        }

        return $result['text'];
    }

    /**
     * Search all users, by name.
     *
     * @return string[]
     * @throws APIException
     */
    protected function getAllUsers()
    {
        $response = (string) $this->slackGetRequest('users.list');
        $users = \json_decode($response, true);
        if (!$users['ok']) {
            throw new APIException($users['error']);
        }
        $return = [];
        foreach ($users['members'] as $member) {
            $return []= $member['name'];
        }
        return $return;
    }

    /**
     * Load the Slack API token from the hidden configuration file.
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getSlackAPIToken()
    {
        static $token = null;
        if ($token === null) {
            if (!\is_readable(TEAMA_ROOT . '/.slack-token')) {
                throw new \InvalidArgumentException(
                    'Unable to read file: ' . TEAMA_ROOT . '/.slack-token'
                );
            }
            $token = \file_get_contents(TEAMA_ROOT . '/.slack-token');
            if ($token === false) {
                $token = null;
                throw new \InvalidArgumentException(
                    'Unable to read file: ' . TEAMA_ROOT . '/.slack-token'
                );
            }
            // Strip whitespace
            $token = \trim($token);
        }
        return $token;
    }

    /**
     * @param array $data
     */
    protected function jsonResponse(array $data)
    {
        \header('Content-Type: application/json; charset=UTF-8');
        echo \json_encode($data, JSON_PRETTY_PRINT);
        exit(0);
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

    /**
     * @param string $recipient
     * @param string $plaxitude
     * @return array
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
            return [
                'recipient' => $recipient,
                'method' => 'sms',
                'plaxitude' => $plaxitude
            ];
        } elseif (!empty($user['email'])) {
            $this->sendEmail($user['email'], $plaxitude);
            return [
                'recipient' => $recipient,
                'method' => 'email',
                'plaxitude' => $plaxitude
            ];
        }
        return [
            'recipient' => $recipient,
            'method' => null,
            'error' => 'No email address or phone number found'
        ];
    }

    /**
     * @param string $phoneNumber
     * @param string $message
     */
    protected function sendSMS($phoneNumber, $message)
    {
        $from = '13128001570';
        $callback_url = 'http://plaxitude.networkninja.com/callback.php';

        $url =  $callback_url . '?' . \http_build_query([
            'from' => $from ,
            'to' => $phoneNumber,
            'body' => $message,
            'callback_url' => $callback_url
        ]);

        $response = (string) $this->guzzle->request('GET', $url);
        $resp = json_decode($response);

        $this->db->insert(
            'status',
            [
                'message_id' => $resp->sid,
                'recipient' => $phoneNumber
            ]
        );
    }
}
