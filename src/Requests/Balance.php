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
     * Safaricom APIs initiator short code username.
     *
     * @var string
     */
    protected string $initiatorName;

    /**
     * Safaricom APIs Balance encrypted initiator short code password.
     *
     * @var string
     */
    protected string $securityCredential;

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
    protected string $resultURL;

    /**
     * Necessary initializations for Balance transactions from the config file while
     * also initialize parent constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->queueTimeOutURL = config('daraja.timeout_url');
        $this->commandId = 'AccountBalance';
    }

    /**
     * Send transaction details to Safaricom Balance API.
     *
     * @return array
     */
    public function check(
        string $shortcode
    ): void {
        $api = ApiCredential::where('short_code', $shortcode)->first();

        $parameters = [
            'Initiator'          => $api->initiator_name,
            'SecurityCredential' => DarajaHelper::setSecurityCredential(Crypter::decrypt($api->initiator_password)),
            'CommandID'          => $this->commandId,
            'PartyA'             => $shortcode,
            'IdentifierType'     => '4',
            'Remarks'            => 'Account balance',
            'QueueTimeOutURL'    => $this->queueTimeOutURL,
            'ResultURL'          => api->balance_result_url,
        ];

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);
        } catch (DarajaRequestException $e) {
        }
    }
}
