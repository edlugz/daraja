<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaBalance;
use EdLugz\Daraja\Models\MpesaTransaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 *
 */
class B2B extends DarajaClient
{
    /**
     * Safaricom APIs B2B endpoint.
     *
     * @var string
     */
    protected string $endPoint = 'mpesa/b2b/v1/paymentrequest';

    /**
     * Safaricom APIs B2B command ids.
     *
     * @var string
     */
    protected string $tillCommandId;
    /**
     * @var string
     */
    protected string $paybillCommandId;

    /**
     * Safaricom APIs queue timeout URI.
     *
     * @var string
     */
    protected string $queueTimeOutURL;

    /**
     * DTO for api credentials
     *
     * @var ClientCredential
     */
    public ClientCredential $clientCredential;

    /**
     * Necessary initializations for B2B transactions from the config file while
     * also initialize parent constructor.
     *
     * @throws DarajaRequestException
     */
    public function __construct(ClientCredential $clientCredential)
    {
        $this->clientCredential = $clientCredential;

        parent::__construct($clientCredential);

        $this->queueTimeOutURL = DarajaHelper::getTimeoutUrl();
        $this->tillCommandId = 'BusinessBuyGoods';
        $this->paybillCommandId = 'BusinessPayBill';
    }

    /**
     * Send transaction details to Safaricom B2B API.
     *
     *
     * @param string $recipient
     * @param string $requester
     * @param int $amount
     * @param array $customFieldsKeyValue
     * @param string|null $resultUrl
     * @return MpesaTransaction|null
     * @throws DarajaRequestException
     */
    public function till(
        string $recipient,
        string $requester,
        int $amount,
        string $resultUrl = null,
        array $customFieldsKeyValue = []
    ): MpesaTransaction|null {

        $balance = MpesaBalance::where('short_code', $this->clientCredential->shortcode)
            ->orderBy('created_at', 'desc')
            ->first();

        if (($balance->amount ?? 0) < $amount) {
            Log::error('Insufficient balance to process this transaction.', [
                'short_code' => $this->clientCredential->shortcode,
                'balance' => $balance?->amount ?? null,
                'required_amount' => $amount,
            ]);
            return null;
        }

        $resultUrl = $resultUrl ?? DarajaHelper::getStkResultUrl();

        $originatorConversationID = (string) Str::uuid();

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'Initiator'                => $this->clientCredential->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential($this->clientCredential->password),
            'CommandID'                => $this->tillCommandId,
            'SenderIdentifierType'     => 4,
            'RecieverIdentifierType'   => 2,
            'Amount'                   => $amount,
            'PartyA'                   => $this->clientCredential->shortcode,
            'PartyB'                   => $recipient,
            'Requester'                => $requester,
            'Remarks'                  => 'till payment',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $resultUrl,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->clientCredential->shortcode,
            'transaction_type'  => 'BuyGoods',
            'account_number'    => $recipient,
            'requester_mobile'  => $requester,
            'amount'            => $amount,
            'json_request'      => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            $transaction->update(
                [
                    'json_response' => json_encode($response),
                    'originator_conversation_id' => $response->OriginatorConversationID,
                    'payment_reference' => $response->OriginatorConversationID,
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

        if (array_key_exists('errorCode', (array) $response)) {
            $response = [
                'ResponseCode'        => $response->errorCode,
                'ResponseDescription' => $response->errorMessage,
            ];

            $response = (object) $response;
        }

        $data = [
            'response_code'          => $response->ResponseCode,
            'response_description'   => $response->ResponseDescription,
        ];

        if (array_key_exists('ResponseCode', (array) $response)) {
            if ($response->ResponseCode == '0') {
                $data = array_merge($data, [
                    'conversation_id'               => $response->ConversationID,
                    'originator_conversation_id'    => $response->OriginatorConversationID,
                    'payment_reference'             => $response->OriginatorConversationID,
                    'response_code'                 => $response->ResponseCode,
                    'response_description'          => $response->ResponseDescription,
                ]);
            }
        }

        $transaction->update($data);

        return $transaction;
    }

    /**
     * Send transaction details to Safaricom B2B API for paybill.
     *
     * @param string $recipient
     * @param string $requester
     * @param int $amount
     * @param string $accountReference
     * @param array $customFieldsKeyValue
     * @param string|null $resultUrl
     * @return MpesaTransaction |  null
     * @throws DarajaRequestException
     */
    public function paybill(
        string $recipient,
        string $requester,
        int $amount,
        string $accountReference,
        string $resultUrl = null,
        array $customFieldsKeyValue = []
    ): MpesaTransaction|null {

        $balance = MpesaBalance::where('short_code', $this->clientCredential->shortcode)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$balance || $balance->amount < $amount) {
            Log::error('Insufficient balance to process this transaction.', [
                'short_code' => $this->clientCredential->shortcode,
                'balance' => $balance?->amount ?? null,
                'required_amount' => $amount,
            ]);
            return null;
        }

        $resultUrl = $resultUrl ?? DarajaHelper::getPaybillResultUrl();

        $originatorConversationID = (string) Str::uuid();

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'Initiator'                => $this->clientCredential->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential($this->clientCredential->password),
            'CommandID'                => $this->paybillCommandId,
            'SenderIdentifierType'     => 4,
            'RecieverIdentifierType'   => 4,
            'Amount'                   => $amount,
            'AccountReference'         => $accountReference,
            'PartyA'                   => $this->clientCredential->shortcode,
            'PartyB'                   => $recipient,
            'Requester'                => $requester,
            'Remarks'                  => 'paybill payment',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $resultUrl,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->clientCredential->shortcode,
            'transaction_type'  => 'PayBill',
            'account_number'    => $recipient,
            'requester_mobile'  => $requester,
            'bill_reference'    => $accountReference,
            'amount'            => $amount,
            'json_request'      => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            $transaction->update(
                [
                    'originator_conversation_id' => $response->OriginatorConversationID,
                    'payment_reference' => $response->OriginatorConversationID,
                    'json_response' => json_encode($response),
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

        if (array_key_exists('errorCode', (array) $response)) {
            $response = [
                'ResponseCode'        => $response->errorCode,
                'ResponseDescription' => $response->errorMessage,
            ];

            $response = (object) $response;
        }

        $data = [
            'response_code'          => $response->ResponseCode,
            'response_description'   => $response->ResponseDescription,
        ];

        if (array_key_exists('ResponseCode', (array) $response)) {
            if ($response->ResponseCode == '0') {
                $data = array_merge($data, [
                    'conversation_id'               => $response->ConversationID,
                    'originator_conversation_id'    => $response->OriginatorConversationID,
                    'payment_reference'             => $response->OriginatorConversationID,
                    'response_code'                 => $response->ResponseCode,
                    'response_description'          => $response->ResponseDescription,
                ]);
            }
        }

        $transaction->update($data);

        return $transaction;
    }
}
