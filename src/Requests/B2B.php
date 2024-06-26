<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaBalance;
use EdLugz\Daraja\Models\MpesaTransaction;
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
     * Safaricom APIs initiator short code username.
     *
     * @var string
     */
    protected string $initiatorName;

    /**
     * Safaricom APIs B2B encrypted initiator short code password.
     *
     * @var string
     */
    protected string $securityCredential;

    /**
     * Safaricom APIs B2B initiator short code.
     *
     * @var string
     */
    protected string $partyA;

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

        $this->initiatorName = config('daraja.initiator_name');
        $this->securityCredential = DarajaHelper::setSecurityCredential(config('daraja.initiator_password'));
        $this->partyA = config('daraja.shortcode');
        $this->queueTimeOutURL = config('daraja.timeout_url');
        $this->tillResultURL = config('daraja.till_result_url');
        $this->paybillResultURL = config('daraja.paybill_result_url');
        $this->tillCommandId = 'BusinessBuyGoods';
        $this->paybillCommandId = 'BusinessPayBill';
    }

    /**
     * Send transaction details to Safaricom B2B API.
     *
     * @param string $recipient
     * @param string $amount
     * @param string $requester
     * @param array  $customFieldKeyValue
     *
     * @return MpesaTransaction
     */
    protected function till(
        string $recipient,
        string $requester,
        string $amount,
        array $customFieldsKeyValue
    ): MpesaTransaction {
        //check balance before sending out transaction

        $balance = MpesaBalance::orderBy('id', 'desc')->first(['utility_account']);

        if ($balance->working_account > $amount) {
            $originatorConversationID = (string) Str::ulid();
        }

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'Initiator'                => $this->initiatorName,
            'SecurityCredential'       => $this->securityCredential,
            'CommandID'                => $this->tillCommandId,
            'SenderIdentifierType'     => 4,
            'RecieverIdentifierType'   => 2,
            'Amount'                   => $amount,
            'PartyA'                   => $this->partyA,
            'PartyB'                   => $recipient,
            'Requester'                => $requester,
            'Remarks'                  => 'till payment',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $this->tillResultURL,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'mobile'         => $recipient,
            'amount'         => $amount,
            'json_request'   => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);
            $transaction->update(
                [
                    'json_response' => json_encode($response),
                ]
            );
        } catch (DarajaRequestException $e) {
        }

        return $transaction;
    }

    /**
     * Send transaction details to Safaricom B2B API fro paybill.
     *
     * @param string $recipient
     * @param string $requester
     * @param string $amount
     * @param string $accountReference
     * @param array  $customFieldKeyValue
     *
     * @return MpesaTransaction
     */
    protected function paybill(
        string $recipient,
        string $requester,
        string $amount,
        string $accountReference,
        array $customFieldsKeyValue
    ): MpesaTransaction {
        //check balance before sending out transaction

        $balance = MpesaBalance::orderBy('id', 'desc')->first(['utility_account']);

        if ($balance->working_account > $amount) {
            $originatorConversationID = (string) Str::ulid();
        }

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'Initiator'                => $this->initiatorName,
            'SecurityCredential'       => $this->securityCredential,
            'CommandID'                => $this->tillCommandId,
            'SenderIdentifierType'     => 4,
            'RecieverIdentifierType'   => 4,
            'Amount'                   => $amount,
            'AccountReference'         => $accountReference,
            'PartyA'                   => $this->partyA,
            'PartyB'                   => $recipient,
            'Requester'                => $requester,
            'Remarks'                  => 'paybill payment',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $this->paybillResultURL,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'mobile'         => $recipient,
            'amount'         => $amount,
            'json_request'   => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);
            $transaction->update(
                [
                    'json_response' => json_encode($response),
                ]
            );
        } catch (DarajaRequestException $e) {
        }

        return $transaction;
    }
}
