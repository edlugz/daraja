<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\ApiCredential;

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
     * Necessary initializations for Balance transactions from the config file while
     * also initialize parent constructor.
     * @throws DarajaRequestException
     */
    public function __construct(ApiCredential $apiCredential)
    {
        parent::__construct($apiCredential);

        $this->queueTimeOutURL = config('daraja.timeout_url');
        $this->commandId = 'AccountBalance';
        $this->resultURL = $resultURL ?? config('daraja.balance_result_url');
    }

    /**
     * Send transaction details to Safaricom Balance API.
     *
     * @param ApiCredential $apiCredential
     * @return void
     */
    public function check(): void {

        $parameters = [
            'Initiator'          => DarajaHelper::apiCredentials($this->apiCredential)->initiator,
            'SecurityCredential' => DarajaHelper::setSecurityCredential(DarajaHelper::apiCredentials($this->apiCredential)->password),
            'CommandID'          => $this->commandId,
            'PartyA'             => DarajaHelper::apiCredentials($this->apiCredential)->shortcode,
            'IdentifierType'     => '4',
            'Remarks'            => 'Account balance',
            'QueueTimeOutURL'    => $this->queueTimeOutURL,
            'ResultURL'          => $this->resultURL,
        ];

        try {

            $this->call($this->endPoint, ['json' => $parameters]);

        } catch (DarajaRequestException $e) {

        }
    }
}
