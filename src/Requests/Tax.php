<?php

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaBalance;
use EdLugz\Daraja\Models\MpesaTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Tax extends DarajaClient
{
    /**
     * Safaricom APIs B2B endpoint.
     *
     * @var string
     */
    protected string $endPoint = 'mpesa/b2b/v1/remittax';

    /**
     * Safaricom APIs B2B command ids.
     *
     * @var string
     */
    protected string $commandId;

    /**
     * tax remittance result URL.
     *
     * @var string
     */
    protected string $resultURL ;

    /**
     * @var string
     */
    protected string $queueTimeOutURL;

    /**
     * DTO for api credentials
     *
     * @var ClientCredential
     */
    public ClientCredential $clientCredential;

    /**
     * Necessary initializations for B2B transactions from the config file while
     * also initialize parent constructor.
     *
     * @param ClientCredential $clientCredential
     * @param string|null $resultURL
     * @throws DarajaRequestException
     */
    public function __construct(ClientCredential $clientCredential, string $resultURL = null)
    {
        $this->clientCredential = $clientCredential;

        parent::__construct($clientCredential);
        $this->resultURL = $resultURL ?? DarajaHelper::getPaybillResultUrl();
        $this->queueTimeOutURL = DarajaHelper::getTimeoutUrl();
        $this->commandId = 'PayTaxToKRA';
    }

    /**
     * Send transaction details to Safaricom B2B API for paybill.
     *
     * @param int $amount
     * @param string $accountReference
     * @param array $customFieldsKeyValue
     * @param string|null $resultUrl
     * @return MpesaTransaction |  null
     * @throws DarajaRequestException
     */
    public function remit(
        int $amount,
        string $accountReference,
        string $resultUrl = null,
        array $customFieldsKeyValue = []
    ): MpesaTransaction|null {

        $balance = MpesaBalance::where('short_code', $this->clientCredential->shortcode)
            ->orderBy('created_at', 'desc')
            ->first();

        if (($balance->working_account ?? 0) < $amount) {
            Log::error('Insufficient balance to process this transaction.', [
                'short_code' => $this->clientCredential->shortcode,
                'balance' => $balance?->amount ?? null,
                'required_amount' => $amount,
            ]);
            return null;
        }

        $resultUrl = $resultUrl ?? DarajaHelper::getPaybillResultUrl();

        $originatorConversationID = (string) Str::uuid();

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'Initiator'                => $this->clientCredential->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential($this->clientCredential->password),
            'CommandID'                => $this->commandId,
            'SenderIdentifierType'     => 4,
            'RecieverIdentifierType'   => 4,
            'Amount'                   => $amount,
            'AccountReference'         => $accountReference,
            'PartyA'                   => $this->clientCredential->shortcode,
            'PartyB'                   => '572572',
            'Remarks'                  => 'paybill payment',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $resultUrl,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->clientCredential->shortcode,
            'transaction_type'  => 'TaxRemittance',
            'account_number'    => '572572',
            'bill_reference'    => $accountReference,
            'amount'            => $amount,
            'json_request'      => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            $transaction->update(
                [
                    'originator_conversation_id' => $response->OriginatorConversationID,
                    'payment_reference' => $response->OriginatorConversationID,
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
                    'payment_reference'             => $response->OriginatorConversationID,
                    'response_code'                 => $response->ResponseCode,
                    'response_description'          => $response->ResponseDescription,
                ]);
            }
        }

        $transaction->update($data);

        return $transaction;
    }
}