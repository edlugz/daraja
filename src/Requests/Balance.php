<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use Illuminate\Support\Facades\Log;

/**
 *
 */
class Balance extends DarajaClient
{
    /**
     * Safaricom APIs Balance endpoint.
     *
     * @var string
     */
    protected string $endPoint = 'mpesa/accountbalance/v1/query';

    /**
     * Safaricom APIs Balance command id.
     *
     * @var string
     */
    protected string $commandId;

    /**
     * Safaricom APIs Balance queue timeout URI.
     *
     * @var string
     */
    protected string $queueTimeOutURL;

    /**
     * Where the Safaricom Account Balance API will post the result of the transaction.
     *
     * @var string
     */
    public string $resultURL;

    /**
     *  DTO for api credentials
     *
     * @var ClientCredential
     */
    public ClientCredential $apiCredential;

    /**
     * Necessary initializations for Balance transactions from the config file while
     * also initialize parent constructor.
     *
     * @throws DarajaRequestException
     */
    public function __construct(ClientCredential $apiCredential, string $resultURL)
    {
        $this->apiCredential = $apiCredential;

        parent::__construct($apiCredential);

        $this->queueTimeOutURL = env('DARAJA_TIMEOUT_URL');
        $this->commandId = 'AccountBalance';
        $this->resultURL = $resultURL;
    }

    /**
     * Send transaction details to Safaricom Balance API.
     *
     * @return void
     */
    public function check(): void
    {
        $parameters = [
            'Initiator'          => $this->apiCredential->initiator,
            'SecurityCredential' => DarajaHelper::setSecurityCredential($this->apiCredential->password),
            'CommandID'          => $this->commandId,
            'PartyA'             => $this->apiCredential->shortcode,
            'IdentifierType'     => '4',
            'Remarks'            => 'Account balance',
            'QueueTimeOutURL'    => $this->queueTimeOutURL,
            'ResultURL'          => $this->resultURL,
        ];

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            Log::info('Daraja Balance Response: ', (array) $response);
        } catch (DarajaRequestException $e) {
<<<<<<< Updated upstream
=======
            Log::error($e);
>>>>>>> Stashed changes
        }
    }
}
