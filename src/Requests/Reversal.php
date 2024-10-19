<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Reversal extends DarajaClient
{
    /**
     * Safaricom APIs B2C endpoint.
     *
     * @var string
     */
    protected string $endPoint = 'mpesa/reversal/v1/request';

    /**
     * Safaricom APIs Reversal command id.
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
     * Necessary initializations for B2C transactions from the config file while
     * also initialize parent constructor.
     *
     * @throws \EdLugz\Daraja\Exceptions\DarajaRequestException
     */
    public function __construct(ClientCredential $apiCredential)
    {
        parent::__construct($apiCredential);

        $this->queueTimeOutURL = config('daraja.timeout_url');
        $this->resultURL = config('daraja.reversal_result_url');
        $this->commandId = 'TransactionReversal';
    }

    /**
     * Send transaction details to Safaricom Reversal API.
     *
     * @param string $transactionId
     * @param string $amount
     * @param array  $customFieldsKeyValue
     *
     * @return \EdLugz\Daraja\Models\MpesaTransaction
     */
    public function request(
        string $transactionId,
        string $amount,
        array $customFieldsKeyValue = []
    ): MpesaTransaction {
        $originatorConversationID = (string) Str::uuid();

        $parameters = [
            'Initiator'                => $this->apiCredential->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential($this->apiCredential->password),
            'CommandID'                => $this->commandId,
            'TransactionID'            => $transactionId,
            'Amount'                   => $amount,
            'ReceiverParty'            => $this->apiCredential->shortcode,
            'RecieverIdentifierType'   => 11,
            'ResultURL'                => $this->resultURL,
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'Remarks'                  => 'Reversal',
            'Occasion'                 => 'Reversal',
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->apiCredential->shortcode,
            'transaction_type'  => 'Reversal',
            'account_number'    => '0',
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
