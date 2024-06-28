<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaTransaction;
use Illuminate\Support\Str;
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
     * Safaricom APIs initiator short code username.
     *
     * @var string
     */
    protected string $initiatorName;

    /**
     * Safaricom APIs C2B encrypted initiator short code password.
     *
     * @var string
     */
    protected string $securityCredential;

    /**
     * Safaricom APIs C2B initiator short code.
     *
     * @var string
     */
    protected string $partyA;

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
    protected string $mobileResultURL, $tillResultURL, $paybillResultURL;

    /**
     * Necessary initializations for C2B transactions from the config file.
     */
    public function __construct()
    {
        parent::__construct();

        $this->timestamp = date('YmdHis');
        $this->initiatorName = config('daraja.initiator_name');
        $this->securityCredential = DarajaHelper =>  => setSecurityCredential(config('daraja.initiator_password'));
        $this->partyA = config('daraja.shortcode');
        $this->queueTimeOutURL = config('daraja.timeout_url');
        $this->mobileResultURL = config('daraja.transaction_query_mobile_result_url');
        $this->tillResultURL = config('daraja.transaction_query_till_result_url');
        $this->paybillResultURL = config('daraja.transaction_query_paybill_result_url');
        $this->commandId = 'TransactionStatusQuery';
    }

    /**
     * Send transaction details to Safaricom C2B API.
     *
     * @param string $paymentId
     *
     * @return MpesaFunding
     */
    public function status(
        string $paymentId
    ) : MpesaTransaction {

        $check = MpesaTransaction::where('payment_id', $paymentId)->first();

        $parameters = [
            'Initiator' => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'Command ID' =>  $this->commandId,
            'Transaction ID' =>  '',
            'OriginatorConversationID' => $check->originator_conversation_id,
            'PartyA' => $this->partyA,
            'IdentifierType' => '4',
            'ResultURL' => $this->resultURL,
            'QueueTimeOutURL' => $this->queueTimeOutURL,
            'Remarks' => 'OK',
            'Occasion' => 'OK'
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create([
            'payment_id'        => $check->payment_id,
            'payment_reference' => $check->originator_conversation_id,
            'short_code'        => $this->partyA,
            'transaction_type'  => 'TransactionStatus',
            'account_number'    => $check->account_number,
            'amount'            => $check->amount,
            'json_request'      => json_encode($parameters)
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
                'ResponseCode' => $e->getCode(),
                'ResponseDescription' => $e->getMessage(),
            ];

            $response = (object) $response;
        }

        $data = [
            'response_code'          => $response->ResponseCode,
            'response_description'   => $response->ResponseDescription
        ];

        if(array_key_exists('errorCode', $array)){
            $response = [
                'ResponseCode'   => $response->errorCode,
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
                    'response_description'          => $response->ResponseDescription
                ]);
            }
        }

        $transaction->update($data);

        return $transaction;
    }
}
