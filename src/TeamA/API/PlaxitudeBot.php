<?php
namespace Netninja\TeamA\API;

use Netninja\TeamA\CommonAPI;

/**
 * Class PlaxitudeBot
 * @package Netninja\TeamA\API
 */
class PlaxitudeBot extends CommonAPI
{
    /**
     * @route /
     */
    public function index()
    {
        $this->jsonResponse([
            [
                'method' =>
                    'GET',
                'uri' =>
                    '/',
                'description' =>
                    'API Summary',
                'parameters' => []
            ],
            [
                'method' =>
                    'GET',
                'uri' =>
                    '/send',
                'description' =>
                    'Send a random plaxitude',
                'parameters' => [
                    [
                        'name' =>
                            'name',
                        'type' =>
                            'string',
                        'required' =>
                            true,
                        'description' =>
                            'The name of the person to send a plaxitude. Magic value ("everybody") will send one to everybody.'
                    ],
                    [
                        'name' =>
                            'category',
                        'type' =>
                            'string',
                        'required' =>
                            true,
                        'description' =>
                            'One of the following: SPORTS, EARLY YEARS, JOKES, KIDS'
                    ]
                ]
            ]
        ]);
    }

    /**
     * @route /send
     */
    public function send()
    {
        try {

        } catch (\Exception $ex) {
            $this->jsonResponse([
                'ok' => false,
                'error' => $ex->getMessage()
            ]);
        }
    }
}
