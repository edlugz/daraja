<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use Illuminate\Support\Facades\Log;

class MNOLookUp
{
    /**
     * Safaricom APIs query org info endpoint.
     *
     * @var string
     */
    protected string $endPoint = 'sfcverify/v1/query/info';

    /**
     * @var ClientCredential
     */
    public ClientCredential $clientCredential;

    /**
     * @param ClientCredential $clientCredential
     */
    public function __construct(ClientCredential $clientCredential)
    {
        $this->clientCredential = $clientCredential;

        parent::__construct($clientCredential);
    }

    /**
     * @param string $query
     * @return object
     */
    public function till(string $query): object
    {
        $parameters = [
            "IdentifierType" => "2",
            "Identifier" =>  $query
        ];

        try {
            $result = $this->call($this->endPoint, ['json' => $parameters]);

            if($result->ResponseCode == "4000") {
                $response = [
                    'success' => true,
                    'message' => $result->OrganizationName
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => $result->ResponseMessage
                ];
            }

        } catch (DarajaRequestException $e) {
            Log::error($e);
            $response = [
                'success' => false,
                'message' => 'Unable to get till details'
            ];
        }

        return (object) $response;
    }

    /**
     * @param string $query
     * @return object
     */
    public function paybill(string $query): object
    {
        $parameters = [
            "IdentifierType" => "4",
            "Identifier" =>  $query
        ];

        try {
            $result = $this->call($this->endPoint, ['json' => $parameters]);

            if($result->ResponseCode == "4000") {
                $response = [
                    'success' => true,
                    'message' => $result->OrganizationName
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => $result->ResponseMessage
                ];
            }
        } catch (DarajaRequestException $e) {
            Log::error($e);
            $response = [
                'success' => false,
                'message' => 'Unable to get paybill business number details'
            ];
        }

        return (object) $response;
    }

}