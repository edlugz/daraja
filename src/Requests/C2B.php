<?php

declare(strict_types=1);

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
     *
     * @param ClientCredential $clientCredential
     * @param string|null $resultURL
     * @throws DarajaRequestException
     */
    public function __construct(ClientCredential $clientCredential, ?string $resultURL = null)
    {
        parent::__construct($clientCredential);

        $this->initiatorName = $this->clientCredential->initiator;
        $this->partyA = $this->clientCredential->shortcode;
        $this->queueTimeOutURL = DarajaHelper::getTimeoutUrl();
        $this->commandId = 'CustomerPayBillOnline';
        $this->resultURL = $resultURL ?? DarajaHelper::getStkResultUrl();
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
        $timestamp = date('YmdHis');
        $password  = DarajaHelper::setPassword(
            $this->clientCredential->shortcode,
            $this->clientCredential->passkey,
            $timestamp
        );

        $parameters = [
            'BusinessShortCode' => $this->partyA,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => $this->commandId,
            'Amount'            => $amount,
            'PartyA'            => DarajaHelper::formatMobileNumber($recipient),
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

            $transaction->update(
                [
                    'json_response' => json_encode($response,   JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ]
            );
        } catch (DarajaRequestException $e) {
            Log::error($e);
            $response = [
                'ResponseCode'        => $e->getCode(),
                'ResponseDescription' => $e->getMessage(),
            ];

            $response = (object) $response;
        }

        $data = [
            'response_code'          => $response->ResponseCode ?? null,
            'response_description'   => $response->ResponseDescription ?? null,
        ];

        if ((string)($response->ResponseCode ?? '') === '0') {
            $data += [
                'merchant_request_id'    => $response->MerchantRequestID ?? null,
                'checkout_request_id'    => $response->CheckoutRequestID ?? null,
            ];
        }

        $transaction->update($data);

        return $transaction;
    }
}
