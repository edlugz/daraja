<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\ApiCredential;
use EdLugz\Daraja\Models\MpesaTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
    protected string $paybillResultURL;

    /**
     * Necessary initializations for B2B transactions from the config file while
     * also initialize parent constructor.
     * @throws DarajaRequestException
     */
    public function __construct(ApiCredential $apiCredential)
    {
        parent::__construct($apiCredential);

        $this->queueTimeOutURL = config('daraja.timeout_url');
        $this->tillResultURL = config('daraja.till_result_url');
        $this->paybillResultURL = config('daraja.paybill_result_url');
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
     * @param array $customFieldsKeyValue
     * @return MpesaTransaction
     */
    protected function till(
        string $recipient,
        string $requester,
        string $amount,
        array $customFieldsKeyValue
    ): MpesaTransaction {

        //check balance before sending out transaction
        $originatorConversationID = (string) Str::uuid();

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'Initiator'                => DarajaHelper::apiCredentials($this->apiCredential)->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential(DarajaHelper::apiCredentials($this->apiCredential)->password),
            'CommandID'                => $this->tillCommandId,
            'SenderIdentifierType'     => 4,
            'RecieverIdentifierType'   => 2,
            'Amount'                   => $amount,
            'PartyA'                   => DarajaHelper::apiCredentials($this->apiCredential)->shortcode,
            'PartyB'                   => $recipient,
            'Requester'                => $requester,
            'Remarks'                  => 'till payment',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $this->tillResultURL,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_reference' => $originatorConversationID,
            'short_code'        => DarajaHelper::apiCredentials($this->apiCredential)->shortcode,
            'transaction_type'  => 'BuyGoods',
            'account_number'    => $recipient,
            'requester_mobile'  => $requester,
            'amount'            => $amount,
            'json_request'      => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            $array = (array) $response;

            Log::info($array);

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

        if (array_key_exists('errorCode', $array)) {
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

        if (array_key_exists('ResponseCode', $array)) {
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
     * @param ApiCredential $apiCredential
     * @param string $recipient
     * @param string $requester
     * @param string $amount
     * @param string $accountReference
     * @param array $customFieldsKeyValue
     * @return MpesaTransaction
     */
    protected function paybill(
        ApiCredential $apiCredential,
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
            'Initiator'                => DarajaHelper::apiCredentials($apiCredential)->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential(DarajaHelper::apiCredentials($apiCredential)->password),
            'CommandID'                => $this->tillCommandId,
            'SenderIdentifierType'     => 4,
            'RecieverIdentifierType'   => 4,
            'Amount'                   => $amount,
            'AccountReference'         => $accountReference,
            'PartyA'                   => DarajaHelper::apiCredentials($apiCredential)->shortcode,
            'PartyB'                   => $recipient,
            'Requester'                => $requester,
            'Remarks'                  => 'paybill payment',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $this->paybillResultURL,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_reference' => $originatorConversationID,
            'short_code'        => DarajaHelper::apiCredentials($apiCredential)->shortcode,
            'transaction_type'  => 'PayBill',
            'account_number'    => $recipient,
            'requester_mobile'  => $requester,
            'bill_reference'    => $accountReference,
            'amount'            => $amount,
            'json_request'      => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            $array = (array) $response;

            Log::info($array);

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

        if (array_key_exists('errorCode', $array)) {
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

        if (array_key_exists('ResponseCode', $array)) {
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
