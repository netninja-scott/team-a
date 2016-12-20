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
     * This is just a dummy Twilio callback.
     *
     * @route /callback
     */
    public function callback()
    {
        $this->jsonResponse([
            'ok' => true
        ]);
    }


    /**
     * @route /send
     */
    public function send()
    {
        try {
            if (empty($_GET['name'])) {
                $this->jsonResponse([
                    'ok' => false,
                    'error' => 'Parameter not passed: name'
                ]);
            }
            if (empty($_GET['category'])) {
                /*
                $this->jsonResponse([
                    'ok' => false,
                    'error' => 'Parameter not passed: category'
                ]);
                */
                $_GET['category'] = $this->getRandomPlaxitudeCategory();
            }
            if (\is_array($_GET['name'])) {
                $this->jsonResponse([
                    'ok' => false,
                    'error' => 'Parameter "name" cannot be an array.'
                ]);
            }
            if (\is_array($_GET['categoy'])) {
                $this->jsonResponse([
                    'ok' => false,
                    'error' => 'Parameter "category" cannot be an array.'
                ]);
            }

            $this->handleSend($_GET['name'], $_GET['category']);
        } catch (\Exception $ex) {
            $this->jsonResponse([
                'ok' => false,
                'error' => $ex->getMessage()
            ]);
        }
    }

    /**
     * @param string $name
     * @param string $category
     */
    protected function handleSend($name = '', $category = '')
    {
        // MAGIC VALUE: SEND EVERYONE SOMETHING
        $results = [];
        if (\strtolower($name) === 'everyone') {
            foreach ($this->getAllUsers() as $user) {
                $results []= $this->sendPlaxitude(
                    $user,
                    $this->getRandomPlaxitude($category)
                );
            }
        } else {
            $results []= $this->sendPlaxitude(
                $name,
                $this->getRandomPlaxitude($category)
            );
        }

        $this->jsonResponse(
            [
                'ok' => true,
                'results' => $results
            ]
        );
    }
}
