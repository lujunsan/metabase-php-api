<?php

namespace lujunsan\Metabase;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Phpfastcache\Helper\Psr16Adapter;

class MetabaseClient {

    /** @var string Metabase API base URL */
    private string $url;

    /** @var string Metabase API authentication token */
    private string $token;

    /**
     * MetabaseClient constructor.
     *
     * @param string $url
     * @param string $username
     * @param string $password
     */
    public function __construct(string $url, string $username, string $password)
    {
        $this->url = $url;
        $this->token = $this->getSessionToken($username, $password);
    }

    /**
     * Obtain session token from cache or generate a new one otherwise.
     *
     * @param string $username
     * @param string $password
     * @return string
     */
    private function getSessionToken(string $username, string $password) : string {
        $defaultDriver = 'Files';
        $psr16Adapter = new Psr16Adapter($defaultDriver);
        // Replace '@' with '_at_' for compliance with phpFastCache safety requisites
        $cleanUsername = str_replace('@', '_at_', $username);
        if ($psr16Adapter->has('metabase_token_' . $cleanUsername)) {
            return $psr16Adapter->get('metabase_token_' . $cleanUsername);
        }
        $sessionToken = $this->generateSessionToken($username, $password);
        $psr16Adapter->set('metabase_token_' . $cleanUsername, $sessionToken, 7*24*60*60);
        return $sessionToken;
    }

    /**
     * Generate a new session token in case it's not available through cache
     *
     * @param string $username
     * @param string $password
     * @return string
     */
    private function generateSessionToken(string $username, string $password) : string {
        $guzzleClient = new Client([
            'base_uri' => $this->url
        ]);
        $headers = [
            'Content-Type' => 'application/json',
        ];
        $data = '{"username": "' . $username . '", "password": "' . $password . '"}';
        $guzzleRequest = new Request('POST', $this->url . '/api/session', $headers, $data);
        $request = $guzzleClient->send($guzzleRequest);
        $response = json_decode($request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        return $response['id'];
    }

    /**
     * Make a Guzzle call to the specified Metabase API endpoint and retrieve the result
     *
     * @param string $method
     * @param string $endpoint
     * @param string|null $parameters
     * @param string $format
     * @return mixed|\Psr\Http\Message\StreamInterface
     */
    private function call(string $method, string $endpoint, string $parameters = NULL, string $format = 'json') {
        $guzzleClient = new Client([
            'base_uri' => $this->url
        ]);
        $headers = [
            'Content-Type' => 'application/json',
            'X-Metabase-Session' => $this->token,
        ];
        $guzzleRequest = new Request($method, $this->url . $endpoint . $parameters, $headers);
        $request = $guzzleClient->send($guzzleRequest);
        if ($format === 'json') {
            return json_decode($request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        }
        return $request->getBody();
    }

    /**
     * Returns the result of running the query associated with the specified question in an specific format
     * Accepts query parameters to filter the data on the Metabase endpoint
     *
     * @param string $questionId
     * @param string $exportFormat
     * @param string|null $parameters
     * @return mixed
     */
    public function getQuestion(string $questionId, string $exportFormat = 'json', string $parameters = NULL) {
        $endpoint = '/api/card/' . $questionId . '/query/' . $exportFormat . $parameters;
        return $this->call('POST', $endpoint);
    }

}