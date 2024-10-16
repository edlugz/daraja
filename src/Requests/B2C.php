<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\ApiCredential;
use EdLugz\Daraja\Models\MpesaTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class B2C extends DarajaClient
{
    /**
     * Safaricom APIs B2C endpoint.
     *
     * @var string
     */
    protected string $endPoint = 'mpesa/b2c/v3/paymentrequest';

    /**
     * Safaricom APIs B2C command id.
     *
     * @var string
     */
    protected string $commandId;

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
     * Necessary initializations for B2C transactions from the config file.
     */
    public function __construct($consumerKey, $consumerSecret, $shortcode)
    {
        parent::__construct($consumerKey, $consumerSecret, $shortcode);

        $this->queueTimeOutURL = config('daraja.timeout_url');
        $this->resultURL = config('daraja.mobile_result_url');
        $this->commandId = 'SalaryPayment';
    }

    /**
     * Send transaction details to Safaricom B2C API.
     *
     * @param string $shortcode
     * @param string $recipient
     * @param string $amount
     * @param array  $customFieldsKeyValue
     *
     * @return MpesaTransaction
     */
    public function pay(
        ApiCredential $apiCredential,
        string $recipient,
        string $amount,
        array $customFieldsKeyValue = []
    ): MpesaTransaction {

        //check balance before sending out transaction
        $originatorConversationID = (string) Str::uuid();

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'InitiatorName'            => DarajaHelper::apiCredentials($apiCredential)->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential(DarajaHelper::apiCredentials($apiCredential)->password),
            'CommandID'                => $this->commandId,
            'Amount'                   => $amount,
            'PartyA'                   => DarajaHelper::apiCredentials($apiCredential)->shortcode,
            'PartyB'                   => $recipient,
            'Remarks'                  => 'send to mobile',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $this->resultURL,
            'Occasion'                 => 'send to mobile',
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_reference' => $originatorConversationID,
            'short_code'        => DarajaHelper::apiCredentials($apiCredential)->shortcode,
            'transaction_type'  => 'SendMoney',
            'account_number'    => $recipient,
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
