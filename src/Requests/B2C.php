<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaBalance;
use EdLugz\Daraja\Models\MpesaTransaction;
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
     * Safaricom APIs initiator short code username.
     *
     * @var string
     */
    protected string $initiatorName;

    /**
     * Safaricom APIs B2C encrypted initiator short code password.
     *
     * @var string
     */
    protected string $securityCredential;

    /**
     * Safaricom APIs B2C initiator short code.
     *
     * @var string
     */
    protected string $partyA;

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
     */
    public function __construct()
    {
        parent::__construct();

        $this->initiatorName = config('daraja.initiator_name');
        $this->securityCredential = DarajaHelper::setSecurityCredential(config('daraja.initiator_password'));
        $this->partyA = config('daraja.shortcode');
        $this->queueTimeOutURL = config('daraja.timeout_url');
        $this->resultURL = config('daraja.result_url');
        $this->commandId = 'SalaryPayment';
    }

    /**
     * Send transaction details to Safaricom B2C API.
     *
     * @param string $transactionId
     * @param string $recipient
     * @param string $amount
     * @param string $remarks
     * @param string $occasion
     *
     * @return array
     */
    protected function pay(
        string $transactionId,
        string $recipient,
        string $amount,
        string $remarks,
        string $occasion = ''
    ): array {
        //check balance before sending out transaction

        $balance = MpesaBalance::orderBy('id', 'desc')->first(['utility_account']);

        if ($balance->utility_account <= $amount) {
            return [
                'success' => false,
                'message' => 'Insufficient balance.',
            ];
        }

        $originatorConversationID = (string) Str::ulid();

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'InitiatorName'            => $this->initiatorName,
            'SecurityCredential'       => $this->securityCredential,
            'CommandID'                => $this->commandId,
            'Amount'                   => $amount,
            'PartyA'                   => $this->partyA,
            'PartyB'                   => $recipient,
            'Remarks'                  => str_limit($remarks, 100, ''),
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $this->resultURL,
            'Occasion'                 => $occasion,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create([
            'transaction_id' => $transactionId,
            'mobile'         => $recipient,
            'amount'         => $amount,
            'json_request'   => json_encode($parameters),
        ]);

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);
            $transaction->update(
                [
                    'json_response' => json_encode($response),
                ]
            );
        } catch (DarajaRequestException $e) {
            return [
                'status'         => $e->getCode(),
                'message'        => $e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Transaction sent out successfully.',
        ];
    }
}
