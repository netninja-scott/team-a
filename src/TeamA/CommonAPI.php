<?php
namespace Netninja\TeamA;

use GuzzleHttp\Client as HTTPClient;
use Netninja\TeamA\Exceptions\APIException;
use Netninja\TeamA\Exceptions\UserNotFound;

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
     * CommonAPI constructor.
     */
    public function __construct()
    {
        $this->guzzle = new HTTPClient();
    }

    /**
     * @param string $name
     * @param string $channel
     * @return array
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
            if (\strtolower($member['name']) === $name) {
                return $member['profile'];
            }
        }
        throw new UserNotFound('User not found in remote API');
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
    protected function getSlackAPIToken()
    {
        static $token = null;
        if ($token === null) {
            $token = \file_get_contents(TEAMA_ROOT . '/.slack-token');
            if ($token === false) {
                $token = null;
                throw new \InvalidArgumentException(
                    'Unable to read file: ' . TEAMA_ROOT . '/.slack-token'
                );
            }
        }
        return $token;
    }
}
