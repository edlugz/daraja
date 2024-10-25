<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
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

    public ClientCredential $clientCredential;

    /**
     * Necessary initializations for B2C transactions from the config file.
     *
     * @param ClientCredential $clientCredential
     *
     * @throws DarajaRequestException
     */
    public function __construct(ClientCredential $clientCredential)
    {
        $this->clientCredential = $clientCredential;

        parent::__construct($clientCredential);

        $this->queueTimeOutURL = DarajaHelper::getTimeoutUrl();
        $this->resultURL = DarajaHelper::getMobileResultUrl();
        $this->commandId = 'SalaryPayment';
    }

    /**
     * Send transaction details to Safaricom B2C API.
     *
     * @param string $recipient
     * @param string $amount
     * @param array  $customFieldsKeyValue
     *
     * @return MpesaTransaction
     */
    public function pay(
        string $recipient,
        string $amount,
        array $customFieldsKeyValue = []
    ): MpesaTransaction {
        //check balance before sending out transaction
        $originatorConversationID = (string) Str::uuid();

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'InitiatorName'            => $this->clientCredential->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential($this->clientCredential->password),
            'CommandID'                => $this->commandId,
            'Amount'                   => $amount,
            'PartyA'                   => $this->clientCredential->shortcode,
            'PartyB'                   => $recipient,
            'Remarks'                  => 'send to mobile',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $this->resultURL,
            'Occasion'                 => 'send to mobile',
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->clientCredential->shortcode,
            'transaction_type'  => 'SendMoney',
            'account_number'    => $recipient,
            'amount'            => $amount,
            'json_request'      => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            Log::info('Daraja B2C Mobile Response', (array) $response);

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
