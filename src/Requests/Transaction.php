<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\ApiCredential;
use EdLugz\Daraja\Models\MpesaTransaction;
use Illuminate\Support\Facades\Log;

class Transaction extends DarajaClient
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
    protected string $mobileResultURL;
    protected string $tillResultURL;
    protected string $paybillResultURL;

    /**
     * Necessary initializations for C2B transactions from the config file.
     * @throws DarajaRequestException
     */
    public function __construct($consumerKey, $consumerSecret, $shortcode)
    {
        parent::__construct($consumerKey, $consumerSecret, $shortcode);

        $this->queueTimeOutURL = config('daraja.timeout_url');
        $this->mobileResultURL = config('daraja.transaction_query_mobile_result_url');
        $this->tillResultURL = config('daraja.transaction_query_till_result_url');
        $this->paybillResultURL = config('daraja.transaction_query_paybill_result_url');
        $this->commandId = 'TransactionStatusQuery';
    }

    /**
     * Send transaction details to Safaricom C2B API.
     *
     * @param string $shortcode
     * @param string $paymentId
     *
     * @return MpesaTransaction
     */
    public function status(
        ApiCredential $apiCredential,
        string $paymentId
    ): MpesaTransaction {

        $check = MpesaTransaction::where('payment_id', $paymentId)->first();

        if ($check->transaction_type == 'SendMoney') {
            $resultUrl = $this->mobileResultURL;
        } elseif ($check->transaction_type == 'BuyGoods') {
            $resultUrl = $this->tillResultURL;
        } elseif ($check->transaction_type == 'PayBill') {
            $resultUrl = $this->paybillResultURL;
        } else {
            $resultUrl = $this->mobileResultURL;
        }

        $parameters = [
            'Initiator'                => DarajaHelper::apiCredentials($apiCredential)->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential(DarajaHelper::apiCredentials($apiCredential)->password),
            'CommandID'                => $this->commandId,
            'TransactionID'            => '',
            'OriginatorConversationID' => $check->originator_conversation_id,
            'PartyA'                   => DarajaHelper::apiCredentials($apiCredential)->shortcode,
            'IdentifierType'           => '4',
            'ResultURL'                => $resultUrl,
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'Remarks'                  => 'OK',
            'Occasion'                 => 'OK',
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create([
            'payment_id'        => $check->payment_id,
            'payment_reference' => $check->originator_conversation_id,
            'short_code'        => DarajaHelper::apiCredentials($apiCredential)->shortcode,
            'transaction_type'  => 'TransactionStatus',
            'account_number'    => $check->account_number,
            'amount'            => $check->amount,
            'json_request'      => json_encode($parameters),
        ]);

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

        $data = [
            'response_code'          => $response->ResponseCode,
            'response_description'   => $response->ResponseDescription,
        ];

        if (array_key_exists('errorCode', $array)) {
            $response = [
                'ResponseCode'        => $response->errorCode,
                'ResponseDescription' => $response->errorMessage,
            ];

            $response = (object) $response;
        }

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
