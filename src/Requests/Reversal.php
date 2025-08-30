<?php

declare(strict_types=1);

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
     * @param ClientCredential $clientCredential
     *
     * @throws DarajaRequestException
     */
    public function __construct(ClientCredential $clientCredential)
    {
        parent::__construct($clientCredential);

        $this->queueTimeOutURL = DarajaHelper::getTimeoutUrl();
        $this->resultURL = DarajaHelper::getReversalResultUrl();
        $this->commandId = 'TransactionReversal';
    }

    /**
     * Send transaction details to Safaricom Reversal API.
     *
     * @param string $transactionId
     * @param string $amount
     * @param array  $customFieldsKeyValue
     *
     * @return MpesaTransaction
     */
    public function request(
        string $transactionId,
        string $amount,
        array $customFieldsKeyValue = []
    ): MpesaTransaction {
        $originatorConversationID = (string) Str::uuid();

        $parameters = [
            'Initiator'                => $this->clientCredential->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential($this->clientCredential->password),
            'CommandID'                => $this->commandId,
            'TransactionID'            => $transactionId,
            'Amount'                   => $amount,
            'ReceiverParty'            => $this->clientCredential->shortcode,
            'RecieverIdentifierType'   => 11,
            'ResultURL'                => $this->resultURL,
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'Remarks'                  => 'Reversal',
            'Occasion'                 => 'Reversal',
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->clientCredential->shortcode,
            'transaction_type'  => 'Reversal',
            'account_number'    => '0',
            'amount'            => $amount,
            'json_request'      => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            $transaction->update(
                [
                    'json_response' => json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
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
            'response_code'          => $response->ResponseCode ?? null,
            'response_description'   => $response->ResponseDescription ?? null,
        ];

        if (($response->ResponseCode ?? null) === '0' || (string)($response->ResponseCode ?? '') === '0') {
            $data += [
                'conversation_id'               => $response->ConversationID ?? null,
                'originator_conversation_id'    => $response->OriginatorConversationID ?? null,
            ];
        }

        $transaction->update($data);

        return $transaction;
    }
}
