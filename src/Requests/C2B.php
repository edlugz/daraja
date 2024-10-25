<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaFunding;
use Illuminate\Support\Facades\Log;

/**
 *
 */
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
     * DTO for api credentials
     *
     * @var ClientCredential
     */
    public ClientCredential $clientCredential;

    /**
     * Necessary initializations for C2B transactions from the config file.
     *
     * @param ClientCredential $clientCredential
     *
     * @throws DarajaRequestException
     */
    public function __construct(ClientCredential $clientCredential)
    {
        $this->clientCredential = $clientCredential;

        parent::__construct($clientCredential);

        $this->timestamp = date('YmdHis');
        $this->initiatorName = $this->clientCredential->initiator;
        $this->password = DarajaHelper::setPassword($this->clientCredential->shortcode, $this->clientCredential->passkey, $this->timestamp);
        $this->partyA = $this->clientCredential->shortcode;
        $this->queueTimeOutURL = DarajaHelper::getTimeoutUrl();
        $this->resultURL = DarajaHelper::getStkResultUrl();
        $this->commandId = 'CustomerPayBillOnline';
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
    public function send(
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
            'mobile_no'      => $recipient,
            'amount'         => $amount,
            'bill_reference' => $accountReference,
            'json_request'   => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            Log::info('Daraja C2B Response', (array) $response);

            $transaction->update(
                [
                    'json_response' => json_encode($response),
                ]
            );
        } catch (DarajaRequestException $e) {
            $response = [
                'ResponseCode'        => $e->getCode(),
                'ResponseDescription' => $e->getMessage(),
            ];

            $response = (object) $response;
        }

        $data = [
            'response_code'          => $response->ResponseCode,
            'response_description'   => $response->ResponseDescription,
        ];

        if ($response->ResponseCode == '0') {
            $data = array_merge($data, [
                'merchant_request_id'    => $response->MerchantRequestID,
                'checkout_request_id'    => $response->CheckoutRequestID,
                'response_code'          => $response->ResponseCode,
                'response_description'   => $response->ResponseDescription,
            ]);
        }

        $transaction->update($data);

        return $transaction;
    }
}
