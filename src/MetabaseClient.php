<?php

namespace lujunsan\Metabase;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Phpfastcache\Helper\Psr16Adapter;

class MetabaseClient {

    private $url;
    private $token;

    public function __construct($url, $username, $password)
    {
        $this->url = $url;
        $this->token = $this->getSessionToken($username, $password);
    }

    private function getSessionToken($username, $password) {
        $defaultDriver = 'Files';
        $psr16Adapter = new Psr16Adapter($defaultDriver);
        $cleanUsername = str_replace('@', '_at_', $username);
        if ($psr16Adapter->has('metabase_token_' . $cleanUsername)) {
            return $psr16Adapter->get('metabase_token_' . $cleanUsername);
        }
        $sessionToken = $this->generateSessionToken($username, $password);
        $psr16Adapter->set('metabase_token_' . $cleanUsername, $sessionToken, 7*24*60*60);
        return $sessionToken;
    }

    private function generateSessionToken($username, $password) {
        $guzzleClient = new Client([
            'base_uri' => $this->url
        ]);
        $headers = [
            'Content-Type' => 'application/json',
        ];
        $data = '{"username": "' . $username . '", "password": "' . $password . '"}';
        $guzzleRequest = new Request('POST', $this->url . '/api/session', $headers, $data);
        $request = $guzzleClient->send($guzzleRequest);
        $response = json_decode($request->getBody(), true);
        return $response['id'];
    }

    private function call($method, $endpoint, $parameters = NULL) {
        $guzzleClient = new Client([
            'base_uri' => $this->url
        ]);
        $headers = [
            'Content-Type' => 'application/json',
            'X-Metabase-Session' => $this->token,
        ];
        $guzzleRequest = new Request($method, $this->url . $endpoint . $parameters, $headers);
        $request = $guzzleClient->send($guzzleRequest);
        return json_decode($request->getBody(), true);
    }

    public function getQuestion($questionId, $exportFormat = 'json', $parameters = NULL) {
        $endpoint = '/api/card/' . $questionId . '/query/' . $exportFormat . $parameters;
        return $this->call('POST', $endpoint);
    }

}