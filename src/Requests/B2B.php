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
     */
    public function __construct()
    {
        parent::__construct();

        $this->queueTimeOutURL = config('daraja.timeout_url');
        $this->tillResultURL = config('daraja.till_result_url');
        $this->paybillResultURL = config('daraja.paybill_result_url');
        $this->tillCommandId = 'BusinessBuyGoods';
        $this->paybillCommandId = 'BusinessPayBill';
    }

    /**
     * Send transaction details to Safaricom B2B API.
     *
     * @param string $shortcode
     * @param string $recipient
     * @param string $amount
     * @param string $requester
     * @param array  $customFieldKeyValue
     *
     * @return MpesaTransaction
     */
    protected function till(
        string $shortcode,
        string $recipient,
        string $requester,
        string $amount,
        array $customFieldsKeyValue
    ): MpesaTransaction {
        //check shortcode for credentials
        $api = ApiCredential::where('short_code', $shortcode)->first();

        //check balance before sending out transaction
        $originatorConversationID = (string) Str::ulid();

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'Initiator'                => $api->initiator_name,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential(Crypter::decrypt($api->initiator_password)),
            'CommandID'                => $this->tillCommandId,
            'SenderIdentifierType'     => 4,
            'RecieverIdentifierType'   => 2,
            'Amount'                   => $amount,
            'PartyA'                   => $shortcode,
            'PartyB'                   => $recipient,
            'Requester'                => $requester,
            'Remarks'                  => 'till payment',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $this->tillResultURL,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_reference' => $originatorConversationID,
            'short_code'        => $shortcode,
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
     * Send transaction details to Safaricom B2B API fro paybill.
     *
     * @param string $shortcode
     * @param string $recipient
     * @param string $requester
     * @param string $amount
     * @param string $accountReference
     * @param array  $customFieldKeyValue
     *
     * @return MpesaTransaction
     */
    protected function paybill(
        string $shortcode,
        string $recipient,
        string $requester,
        string $amount,
        string $accountReference,
        array $customFieldsKeyValue
    ): MpesaTransaction {
        //check shortcode for credentials
        $api = ApiCredential::where('short_code', $shortcode)->first();

        //check balance before sending out transaction
        $originatorConversationID = (string) Str::ulid();

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'Initiator'                => $api->initiator_name,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential(Crypter::decrypt($api->initiator_password)),
            'CommandID'                => $this->tillCommandId,
            'SenderIdentifierType'     => 4,
            'RecieverIdentifierType'   => 4,
            'Amount'                   => $amount,
            'AccountReference'         => $accountReference,
            'PartyA'                   => $shortcode,
            'PartyB'                   => $recipient,
            'Requester'                => $requester,
            'Remarks'                  => 'paybill payment',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $this->paybillResultURL,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_reference' => $originatorConversationID,
            'short_code'        => $shortcode,
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
