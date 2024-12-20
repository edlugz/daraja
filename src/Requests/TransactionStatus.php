<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaTransaction;
use Illuminate\Support\Facades\Log;

class TransactionStatus extends DarajaClient
{
    /**
     * Safaricom APIs C2B endpoint.
     *
     * @var string
     */
    protected string $endPoint = 'mpesa/transactionstatus/v1/query';

    /**
     * Safaricom APIs C2B command id.
     *
     * @var string
     */
    protected string $commandId;

    /**
     * Safaricom APIs C2B queue timeout URI.
     *
     * @var string
     */
    protected string $queueTimeOutURL;

    /**
     * Where the Safaricom Transaction Status API will post the result of the transaction query.
     *
     * @var string
     */
    protected string $resultUrl;

    public ClientCredential $clientCredential;

    /**
     * Necessary initializations for C2B transactions from the config file.
     *
     * @throws DarajaRequestException
     */
    public function __construct(ClientCredential $clientCredential, string $resultUrl = null)
    {
        $this->clientCredential = $clientCredential;

        parent::__construct($clientCredential);

        $this->queueTimeOutURL = DarajaHelper::getTimeoutUrl();
        $this->resultUrl = $resultUrl ?? DarajaHelper::getTransactionQueryResultUrl();
        $this->commandId = 'TransactionStatusQuery';
    }

    /**
     * Send transaction details to Safaricom C2B API.
     *
     * @param string $paymentId
     * @param array  $customFieldsKeyValue
     *
     * @return MpesaTransaction|null
     */
    public function query(
        string $paymentId,
        array $customFieldsKeyValue = []
    ): ?MpesaTransaction {

        $check = MpesaTransaction::where('payment_id', $paymentId)
            ->whereIn('transaction_type',
                [
                    'SendMoney',
                    'PayBill',
                    'BuyGoods'
                ]
            )
            ->latest()->first();

        if(!$check){
            return null;
        }

        $parameters = [
            'Initiator'                => $this->clientCredential->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential($this->clientCredential->password),
            'CommandID'                => $this->commandId,
            'TransactionID'            => '',
            'OriginalConversationID'   => $check->payment_reference,
            'PartyA'                   => $this->clientCredential->shortcode,
            'IdentifierType'           => '4',
            'ResultURL'                => $this->resultUrl,
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'Remarks'                  => 'OK',
            'Occasion'                 => 'OK',
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_id'        => $check->payment_id,
            'payment_reference' => $check->originator_conversation_id,
            'short_code'        => $this->clientCredential->shortcode,
            'transaction_type'  => 'TransactionStatus',
            'account_number'    => $check->account_number,
            'amount'            => $check->amount,
            'json_request'      => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            $transaction->update(
                [
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

        $data = [
            'response_code'          => $response->ResponseCode,
            'response_description'   => $response->ResponseDescription,
        ];

        if (array_key_exists('errorCode', (array) $response)) {
            $response = [
                'ResponseCode'        => $response->errorCode,
                'ResponseDescription' => $response->errorMessage,
            ];

            $response = (object) $response;
        }

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
