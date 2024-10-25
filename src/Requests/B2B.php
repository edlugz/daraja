<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
     * Where the Safaricom B2B API will post the result of the transaction.
     *
     * @var string
     */
    protected string $tillResultURL;
    /**
     * @var string
     */
    protected string $paybillResultURL;

    /**
     * DTO for api credentials
     *
     * @var ClientCredential
     */
    public ClientCredential $apiCredential;

    /**
     * Necessary initializations for B2B transactions from the config file while
     * also initialize parent constructor.
     *
     * @throws DarajaRequestException
     */
    public function __construct(ClientCredential $apiCredential)
    {
        $this->apiCredential = $apiCredential;

        parent::__construct($apiCredential);

        $this->queueTimeOutURL = DarajaHelper::getTimeoutUrl();
        $this->tillResultURL = DarajaHelper::getTillResultUrl();
        $this->paybillResultURL = DarajaHelper::getPaybillResultUrl();
        $this->tillCommandId = 'BusinessBuyGoods';
        $this->paybillCommandId = 'BusinessPayBill';
    }

    /**
     * Send transaction details to Safaricom B2B API.
     *
     *
     * @param string $recipient
     * @param string $requester
     * @param string $amount
     * @param array  $customFieldsKeyValue
     *
     * @return MpesaTransaction
     */
    public function till(
        string $recipient,
        string $requester,
        string $amount,
        array $customFieldsKeyValue
    ): MpesaTransaction {
        //check balance before sending out transaction
        $originatorConversationID = (string) Str::uuid();

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'Initiator'                => $this->apiCredential->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential($this->apiCredential->password),
            'CommandID'                => $this->tillCommandId,
            'SenderIdentifierType'     => 4,
            'RecieverIdentifierType'   => 2,
            'Amount'                   => $amount,
            'PartyA'                   => $this->apiCredential->shortcode,
            'PartyB'                   => $recipient,
            'Requester'                => $requester,
            'Remarks'                  => 'till payment',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $this->tillResultURL,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->apiCredential->shortcode,
            'transaction_type'  => 'BuyGoods',
            'account_number'    => $recipient,
            'requester_mobile'  => $requester,
            'amount'            => $amount,
            'json_request'      => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            Log::info('B2B Till Response', (array) $response);

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
     * @param string $amount
     * @param string $accountReference
     * @param array  $customFieldsKeyValue
     *
     * @return MpesaTransaction
     */
    public function paybill(
        string $recipient,
        string $requester,
        string $amount,
        string $accountReference,
        array $customFieldsKeyValue
    ): MpesaTransaction {
        //check balance before sending out transaction
        $originatorConversationID = (string) Str::uuid();

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'Initiator'                => $this->apiCredential->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential($this->apiCredential->password),
            'CommandID'                => $this->tillCommandId,
            'SenderIdentifierType'     => 4,
            'RecieverIdentifierType'   => 4,
            'Amount'                   => $amount,
            'AccountReference'         => $accountReference,
            'PartyA'                   => $this->apiCredential->shortcode,
            'PartyB'                   => $recipient,
            'Requester'                => $requester,
            'Remarks'                  => 'paybill payment',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $this->paybillResultURL,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->apiCredential->shortcode,
            'transaction_type'  => 'PayBill',
            'account_number'    => $recipient,
            'requester_mobile'  => $requester,
            'bill_reference'    => $accountReference,
            'amount'            => $amount,
            'json_request'      => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            Log::info('B2B Paybill Response', (array) $response);

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
                    'response_code'                 => $response->ResponseCode,
                    'response_description'          => $response->ResponseDescription,
                ]);
            }
        }

        $transaction->update($data);

        return $transaction;
    }
}
