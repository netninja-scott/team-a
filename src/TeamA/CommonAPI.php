<?php
namespace Netninja\TeamA;

use GuzzleHttp\Client as HTTPClient;
use Netninja\TeamA\Exceptions\APIException;
use Netninja\TeamA\Exceptions\UserNotFound;
use Zend\Mail\Transport\Sendmail;

/**
 * Class CommonAPI
 * @package Netninja\TeamA
 */
class CommonAPI
{
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
}
