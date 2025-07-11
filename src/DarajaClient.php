<?php

namespace EdLugz\Daraja;

use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Logging\Log;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log as LaravelLog;
use Illuminate\Support\Str;

class DarajaClient
{
    /**
     * Guzzle client initialization.
     *
     * @var Client
     */
    protected Client $client;

    /**
     * Daraja APIs application client id.
     *
     * @var string
     */
    public string $consumerKey;

    /**
     * Daraja APIs application client secret.
     *
     * @var string
     */
    public string $consumerSecret;
    /**
     * Daraja APIs short code.
     *
     * @var string
     */
    public string $shortcode;

    /**
     * Access token generated by Daraja APIs.
     *
     * @var string
     */
    protected string $accessToken;

    const MODE_LIVE = 'live';
    const MODE_UAT = 'uat';

    /**
     * Base URL end points for the Daraja APIs.
     *
     * @var array
     */
    protected array $base_url = [
        self::MODE_UAT  => 'https://sandbox.safaricom.co.ke/',
        self::MODE_LIVE => 'https://api.safaricom.co.ke',
    ];

    /**
     * Make the initializations required to make calls to the Daraja APIs
     * and throw the necessary exception if there are any missing-required
     * configurations.
     *
     * @param ClientCredential $clientCredential
     *
     * @throws DarajaRequestException
     */
    public function __construct(public ClientCredential $clientCredential)
    {
        try {
            $mode = self::MODE_LIVE;

            $options = [
                'base_uri' => $this->base_url[$mode],
                'verify'   => $mode !== 'uat',
            ];

            $options = Log::enable($options);

            $this->client = new Client($options);
            $this->consumerKey = $this->clientCredential->consumerKey;
            $this->consumerSecret = $this->clientCredential->consumerSecret;
            $this->shortcode = $this->clientCredential->shortcode;
            $this->getAccessToken($this->clientCredential->shortcode);

        } catch(Exception $e) {
            throw new DarajaRequestException('Daraja APIs: '.$e->getMessage(), $e->getCode());
        }
    }

    /**
     * Get access token from Daraja APIs.
     *
     * @param string $shortcode
     *
     * @throws DarajaRequestException
     *
     * @return void
     */
    protected function getAccessToken(string $shortcode): void
    {
        //check if access token exists and not expired
        if (!Cache::get('daraja_'. $shortcode)) {
            // Set the auth option and fetch new token
            $options = [
                'auth' => [
                    $this->consumerKey,
                    $this->consumerSecret,
                ],
            ];

            $accessTokenDetails = $this->call('oauth/v1/generate?grant_type=client_credentials', $options, 'GET');

            //add to Cache
            Cache::add('daraja_'. $shortcode, $accessTokenDetails->access_token, now()->addMinutes(58));
        }

        $this->accessToken = Cache::get('daraja_'. $shortcode);
    }

    /**
     * Make API calls to Daraja API.
     *
     * @param string $url
     * @param array  $options
     * @param string $method
     *
     * @throws DarajaRequestException
     *
     * @return mixed
     */
    protected function call(string $url, array $options = [], string $method = 'POST'): mixed
    {
        if (isset($this->accessToken)) {
            $options['headers'] = ['Authorization' => 'Bearer '.$this->accessToken];
        }

        try {
            $response = $this->client->request($method, $url, $options);

            $stream = $response->getBody();
            $stream->rewind();
            $content = $stream->getContents();

            if (empty($content)) {
                throw new DarajaRequestException('Daraja APIs: Empty response from server', 204);
            }

            return json_decode($content);
        } catch (ServerException $e) {
            $responseBody = $e->getResponse()->getBody()->getContents();
            $response = json_decode($responseBody);

            $message = 'Daraja APIs (ServerException): ' . $e->getMessage();

            if (is_object($response) && isset($response->Envelope->Body->Fault->faultstring)) {
                $message = 'Daraja APIs (SOAP Fault): ' . $response->Envelope->Body->Fault->faultstring;
            }

            elseif (is_object($response) && isset($response->fault->faultstring)) {
                $message = 'Daraja APIs (REST Fault): ' . $response->fault->faultstring;

                if (isset($response->fault->detail->errorcode)) {
                    $message .= ' [' . $response->fault->detail->errorcode . ']';
                }
            }

            elseif (
                Str::contains($e->getRequest()->getUri()->getPath(), '/sfcverify/v1/query/info') &&
                is_object($response) &&
                property_exists($response, 'ResponseMessage')
            ) {
                $message = 'Daraja APIs (ServerException): ' . $response->ResponseMessage;
            }

            if (!isset($response->fault) && !isset($response->Envelope) && !property_exists($response ?? new \stdClass, 'ResponseMessage')) {
                LaravelLog::warning('Unexpected Daraja API error response structure', [
                    'uri' => (string) $e->getRequest()->getUri(),
                    'raw_body' => $responseBody,
                    'decoded_response' => $response,
                    'exception_message' => $e->getMessage(),
                ]);
            }

            throw new DarajaRequestException($message, $e->getCode());

        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents());
            throw new DarajaRequestException(
                message: 'Daraja APIs (ClientException): '. property_exists($response, 'errorMessage') ? $response->errorMessage : $e->getMessage(),
                code: $e->getCode()
            );
        } catch (GuzzleException $e) {
            throw new DarajaRequestException(
                message: 'Daraja APIs (GuzzleException): '.$e->getMessage(),
                code: $e->getCode()
            );
        }
    }
}
