<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaTransaction;
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
        $this->queueTimeOutURL = config('daraja.timeout_url');
        $this->resultURL = config('daraja.reversal_result_url');
        $this->commandId = 'TransactionReversal';
    }

    /**
     * Send transaction details to Safaricom Reversal API.
     *
     * @param string $transactionId
     * @param string $recipient
     * @param string $amount
     *
     * @return array
     */
    protected function request(
        string $transactionId,
        string $recipient,
        string $amount
    ): array {

        $parameters = [
            'Initiator'                => $this->initiatorName,
            'SecurityCredential'       => $this->securityCredential,
            'CommandID'                => $this->commandId,
            'TransactionID'            => $transactionId,
            'Amount'                   => $amount,
            'ReceiverParty'            => $recipient,
            'RecieverIdentifierType'   => 11,
            'ResultURL'                => $this->resultURL,
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'Remarks'                  => 'Reversal',
            'Occasion'                 => 'Reversal'
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
