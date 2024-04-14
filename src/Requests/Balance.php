<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaBalance;

class Balance extends DarajaClient
{
    /**
     * Safaricom APIs Balance endpoint.
     *
     * @var string
     */
    protected string $endPoint = 'mpesa/accountbalance/v1/query';

    /**
     * Safaricom APIs B2C command id.
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
     * Safaricom APIs B2C encrypted initiator short code password.
     *
     * @var string
     */
    protected string $securityCredential;

    /**
     * Safaricom APIs B2C initiator short code.
     *
     * @var string
     */
    protected string $partyA;

    /**
     * Safaricom APIs B2C queue timeout URI.
     *
     * @var string
     */
    protected string $queueTimeOutURL;

    /**
     * Where the Safaricom B2C API will post the result of the transaction.
     *
     * @var string
     */
    protected string $resultURL;

    /**
     * Necessary initializations for B2C transactions from the config file while
     * also initialize parent constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->initiatorName = config('daraja.initiator.name');
        $this->securityCredential = DarajaHelper::setSecurityCredential(config('daraja.initiator.password'));
        $this->partyA = config('daraja.shortcode');
        $this->queueTimeOutURL = config('daraja.timeout_url');
        $this->resultURL = config('daraja.result_url');
        $this->commandId = 'AccountBalance';
    }

    /**
     * Send transaction details to Safaricom B2C API.
     *
     * @return array
     */
    public function check(): array
    {
        $parameters = [
            'Initiator'          => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'CommandID'          => $this->commandId,
            'PartyA'             => $this->partyA,
            'IdentifierType'     => '4',
            'Remarks'            => 'Account balance',
            'QueueTimeOutURL'    => $this->queueTimeOutURL,
            'ResultURL'          => $this->resultURL,
        ];

        /** @var MpesaBalance $balance */
        $balance = MpesaBalance::create([
            'json_request' => json_encode($parameters),
        ]);

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);
            $balance->update(
                [
                    'json_response' => json_encode($response),
                ]
            );
        } catch (DarajaRequestException $e) {
            return [
                'status'  => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Balance request sent out successfully.',
        ];
    }
}
