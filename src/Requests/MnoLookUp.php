<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use Illuminate\Support\Facades\Log;

class MnoLookUp extends DarajaClient
{
    /**
     * Safaricom APIs query org info endpoint.
     *
     * @var string
     */
    protected string $endPoint = 'sfcverify/v1/query/info';

    /**
     * @param ClientCredential $clientCredential
     * @throws DarajaRequestException
     */
    public function __construct(ClientCredential $clientCredential)
    {
        parent::__construct($clientCredential);
    }

    /**
     * @param string $query
     * @return object
     */
    public function till(string $query): object
    {
        $parameters = ['IdentifierType' => 2, 'Identifier' => trim($query)];

        try {
            $result = $this->call($this->endPoint, ['json' => $parameters]);

            $code    = (string)($result->ResponseCode ?? '');
            $ok      = $code === '4000';
            $message = $ok
                ? (string)($result->OrganizationName ?? 'OK')
                : (string)($result->ResponseMessage ?? 'Lookup failed');

            return (object)[ 'success' => $ok, 'message' => $message ];
        } catch (DarajaRequestException $e) {
            Log::error('MNO lookup (till) failed', ['q' => $query, 'exception' => $e->getMessage()]);
            return (object)[ 'success' => false, 'message' => 'Unable to get till details' ];
        }
    }

    /**
     * @param string $query
     * @return object
     */
    public function paybill(string $query): object
    {
        $parameters = ['IdentifierType' => 4, 'Identifier' => trim($query)];

        try {
            $result  = $this->call($this->endPoint, ['json' => $parameters]);
            $code    = (string)($result->ResponseCode ?? '');
            $ok      = $code === '4000';
            $message = $ok
                ? (string)($result->OrganizationName ?? 'OK')
                : (string)($result->ResponseMessage ?? 'Lookup failed');

            return (object)[ 'success' => $ok, 'message' => $message ];
        } catch (DarajaRequestException $e) {
            Log::error('MNO lookup (paybill) failed', ['q' => $query, 'exception' => $e->getMessage()]);
            return (object)[ 'success' => false, 'message' => 'Unable to get paybill business number details' ];
        }
    }

}