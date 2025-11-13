<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Data\ClientCredential;
use Edlugz\Daraja\Enums\MpesaTransactionChargeType;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaBalance;
use EdLugz\Daraja\Models\MpesaTransaction;
use EdLugz\Daraja\Services\MpesaTransactionChargeService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
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
     * Necessary initializations for B2B transactions from the config file while
     * also initialize parent constructor.
     *
     * @param ClientCredential $clientCredential
     * @param string|null $resultURL
     * @throws DarajaRequestException
     */
    public function __construct(ClientCredential $clientCredential, ?string $resultURL = null)
    {
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
     * @param string|null $resultUrl
     * @param bool $appendMpesaUuidToUrl
     * @param array $customFieldsKeyValue
     * @return MpesaTransaction |  null
     * @throws FileNotFoundException
     */
    public function remit(
        int     $amount,
        string  $accountReference,
        ?string $resultUrl = null,
        bool    $appendMpesaUuidToUrl = true,
        array   $customFieldsKeyValue = []
    ): MpesaTransaction|null {

        $balance = MpesaBalance::whereShortCode($this->clientCredential->shortcode)
            ->orderBy('created_at', 'desc')
            ->first();

        $working = (int) ($balance->working_account ?? 0);

        $charge = MpesaTransactionChargeService::getCharge($amount, MpesaTransactionChargeType::BUSINESS);

        $total = $amount + $charge;

        if ($working < $total) {
            Log::error('Insufficient balance to process this request', [
                'short_code' => $this->clientCredential->shortcode,
                'balance'    => $working,
                'required_amount' => $total,
            ]);
            return null;
        }

        $originatorConversationID = (string) Str::uuid7();
        $resultUrl = $resultUrl ?? DarajaHelper::getPaybillResultUrl();
        if($appendMpesaUuidToUrl) {
            $resultUrl = $resultUrl . '/'. $originatorConversationID;
        }


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
            'uuid'              => $originatorConversationID,
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->clientCredential->shortcode,
            'transaction_type'  => 'TaxRemittance',
            'account_number'    => '572572',
            'bill_reference'    => $accountReference,
            'amount'            => $amount,
            'transaction_charge' => $charge,
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
                'conversation_id'               => $response->ConversationID,
                'originator_conversation_id'    => $response->OriginatorConversationID,
                'payment_reference'             => $response->OriginatorConversationID,
            ];
        }

        $transaction->update($data);

        return $transaction;
    }
}