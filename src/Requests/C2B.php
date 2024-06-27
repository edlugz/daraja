<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaBalance;
use EdLugz\Daraja\Models\MpesaFunding;
use Illuminate\Support\Str;

class C2B extends DarajaClient
{
    /**
     * Safaricom APIs C2B endpoint.
     *
     * @var string
     */
    protected string $endPoint = 'mpesa/stkpush/v1/processrequest';

    /**
     * Safaricom APIs C2B command id.
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
     * Safaricom APIs stk password.
     *
     * @var string
     */
    protected string $password;

    /**
     * current timestamp.
     *
     * @var string
     */
    protected string $timestamp;

    /**
     * Safaricom APIs C2B encrypted initiator short code password.
     *
     * @var string
     */
    protected string $securityCredential;

    /**
     * Safaricom APIs C2B initiator short code.
     *
     * @var string
     */
    protected string $partyA;

    /**
     * Safaricom APIs C2B queue timeout URI.
     *
     * @var string
     */
    protected string $queueTimeOutURL;

    /**
     * Where the Safaricom C2B API will post the result of the transaction.
     *
     * @var string
     */
    protected string $resultURL;

    /**
     * Necessary initializations for C2B transactions from the config file.
     */
    public function __construct()
    {
        parent::__construct();

        $this->timestamp = date('YmdHis');
        $this->initiatorName = config('daraja.initiator_name');
        $this->password = DarajaHelper::setPassword(config('daraja.shortcode'), config('daraja.passkey'), $this->timestamp);
        $this->partyA = config('daraja.shortcode');
        $this->queueTimeOutURL = config('daraja.timeout_url');
        $this->resultURL = config('daraja.mobile_result_url');
        $this->commandId = 'SalaryPayment';
    }

    /**
     * Send transaction details to Safaricom C2B API.
     *
     * @param string $recipient
     * @param string $amount
     * @param string $accountReference
     * @param array  $customFieldsKeyValue
     *
     * @return MpesaFunding
     */
    protected function send(
        string $recipient,
        string $amount,
        string $accountReference,
        array $customFieldsKeyValue = []
    ): MpesaFunding {
        
        $parameters = [
            'BusinessShortCode' => $this->partyA,
            'Password'          => $this->password,
            'Timestamp'         => $this->timestamp,
            'TransactionType'   => $this->commandId,
            'Amount'            => $amount,
            'PartyA'            => $recipient,
            'PartyB'            => $this->partyA,
            'PhoneNumber'       => $recipient,
            'CallBackURL'       => $this->resultURL,
            'AccountReference'  => $accountReference,
            'TransactionDesc'   => 'top up',
        ];

        /** @var MpesaFunding $transaction */
        $transaction = MpesaFunding::create(array_merge([
            'mobile'         => $recipient,
            'amount'         => $amount,
            'json_request'   => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);
            $transaction->update(
                [
                    'json_response' => json_encode($response),
                ]
            );
        } catch (DarajaRequestException $e) {
            $response = [
                'status'         => $e->getCode(),
                'responseCode'   => $e->getCode(),
                'message'        => $e->getMessage(),
            ];

            $response = (object) $response;
        }

        return $transaction;
    }
}
