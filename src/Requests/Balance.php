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
     */
    public function __construct($consumerKey, $consumerSecret, $shortcode, $resultURL = null)
    {
        parent::__construct($consumerKey, $consumerSecret, $shortcode);

        $this->queueTimeOutURL = config('daraja.timeout_url');
        $this->commandId = 'AccountBalance';
        $this->resultURL = $resultURL ?? config('daraja.balance_result_url');
    }

    /**
     * Send transaction details to Safaricom Balance API.
     *
     * @param string $shortcode
     * @return void
     */
    public function check(
        ApiCredential $apiCredential
    ): void {

        $parameters = [
            'Initiator'          => DarajaHelper::apiCredentials($apiCredential)->initiator,
            'SecurityCredential' => DarajaHelper::setSecurityCredential(DarajaHelper::apiCredentials($apiCredential)->password),
            'CommandID'          => $this->commandId,
            'PartyA'             => DarajaHelper::apiCredentials($apiCredential)->shortcode,
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
