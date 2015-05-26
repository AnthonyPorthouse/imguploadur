<?php

namespace Imguploadur;

use GuzzleHttp\Client;
use GuzzleHttp\Post\PostFile;

class Imguploadur
{
    // API
    /** @var string Base API URL */
    protected $base = "https://api.imgur.com/3/";
    /** @var string Imgur Application Client ID */
    protected $clientId = "d5230dcb9d74aad";
    /** @var string Imgur Application Secret */
    protected $clientSecret = '646d6d6357c0314d136890a0b31db170db5e05a3';

    // Authorization
    protected $authorizeUrl = "https://api.imgur.com/oauth2/token";
    /** @var string Auth file path */
    protected $authFile;
    /** @var array StdClass Auth Information */
    protected $auth;

    /** @var Client $client Guzzle Instance */
    protected $client;

    public function __construct()
    {
        $this->authFile = __DIR__.'/../auth.json';
        $this->client = new Client();

        $this->authorize();
    }

    private function authorize()
    {

        // If Auth file doesnt exist, authorize the user to get an access_token, otherwise get an updated access_token
        if (!is_file($this->authFile)) {
            echo "Go to the following URL: https://api.imgur.com/oauth2/authorize?client_id={$this->clientId}&response_type=pin\n";
            echo "Enter Pin: ";
            $pin = trim(fgets(STDIN));

            $request = $this->client->createRequest('POST', $this->authorizeUrl);
            $postBody = $request->getBody();
            $postBody->setField('client_id', $this->clientId)
                     ->setField('client_secret', $this->clientSecret)
                     ->setField('grant_type', 'pin')
                     ->setField('pin', $pin);

            $response = $this->client->send($request);

            file_put_contents(__DIR__.'/../auth.json', $response->getBody());
            $this->auth = json_decode(file_get_contents($this->authFile));
        } else {
            $this->auth = json_decode(file_get_contents($this->authFile));

            $request = $this->client->createRequest('POST', $this->authorizeUrl);
            $postBody = $request->getBody();
            $postBody->setField('refresh_token', $this->auth->refresh_token)
                     ->setField('client_id', $this->clientId)
                     ->setField('client_secret', $this->clientSecret)
                     ->setField('grant_type', 'refresh_token');

            $response = $this->client->send($request);
            file_put_contents(__DIR__.'/../auth.json', $response->getBody());
            $this->auth = json_decode(file_get_contents($this->authFile));
        }

    }

    public function upload($image)
    {
        if (!(file_exists($image) && is_readable($image))) {
            throw new Exception("File does not exist or is not readable");
        }

        $file = fopen($image, 'r');

        try {
            $request = $this->client->createRequest('POST', $this->base . 'image');

            $request->addHeader('Authorization', 'Bearer ' . $this->auth->access_token);

            $postBody = $request->getBody();
            $postBody->setField('type', 'file')
                     ->addFile(new PostFile('image', $file));

            $response = $this->client->send($request);
            $json = $response->json();

            echo "Image is available at: {$json['data']['link']}\n";
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage() . "\n";
        }
    }
}
