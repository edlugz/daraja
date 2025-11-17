<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Data\ClientCredential;
use Edlugz\Daraja\Enums\MpesaTransactionChargeType;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Exceptions\MpesaChargeException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaBalance;
use EdLugz\Daraja\Models\MpesaTransaction;
use EdLugz\Daraja\Services\MpesaTransactionChargeService;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 *
 */
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
    /**
     * @var string
     */
    protected string $paybillCommandId;
    /**
     * @var string
     */
    protected string $pochiCommandId;

    /**
     * Safaricom APIs queue timeout URI.
     *
     * @var string
     */
    protected string $queueTimeOutURL;

    /**
     * Necessary initializations for B2B transactions from the config file while
     * also initialize parent constructor.
     *
     * @throws DarajaRequestException
     */
    public function __construct(ClientCredential $clientCredential)
    {
        parent::__construct($clientCredential);

        $this->queueTimeOutURL = DarajaHelper::getTimeoutUrl();
        $this->tillCommandId = 'BusinessBuyGoods';
        $this->paybillCommandId = 'BusinessPayBill';
        $this->pochiCommandId = 'BusinessPayToPochi';
    }

    /**
     * Send transaction details to Safaricom B2B API.
     *
     *
     * @param string $recipient
     * @param string $requester
     * @param int $amount
     * @param string|null $resultUrl
     * @param bool $appendMpesaUuidToUrl
     * @param array $customFieldsKeyValue
     * @return MpesaTransaction|null
     * @throws MpesaChargeException|FileNotFoundException
     */
    public function till(
        string  $recipient,
        string  $requester,
        int     $amount,
        ?string $resultUrl = null,
        bool    $appendMpesaUuidToUrl = true,
        array   $customFieldsKeyValue = []
    ): MpesaTransaction|null {

        $balance = MpesaBalance::where('short_code', $this->clientCredential->shortcode)
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
        $resultUrl = $resultUrl ?? DarajaHelper::getTillResultUrl();
        if($appendMpesaUuidToUrl) {
            $resultUrl = $resultUrl . '/'. $originatorConversationID;
        }

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'Initiator'                => $this->clientCredential->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential($this->clientCredential->password),
            'CommandID'                => $this->tillCommandId,
            'SenderIdentifierType'     => 4,
            'RecieverIdentifierType'   => 2,
            'Amount'                   => $amount,
            'PartyA'                   => $this->clientCredential->shortcode,
            'PartyB'                   => $recipient,
            'Requester'                => $requester,
            'Remarks'                  => 'till payment',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $resultUrl,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'uuid'              => $originatorConversationID,
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->clientCredential->shortcode,
            'transaction_type'  => 'BuyGoods',
            'account_number'    => $recipient,
            'requester_mobile'  => $requester,
            'amount'            => $amount,
            'transaction_charge' => $charge,
            'json_request'      => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            $transaction->update([
                'json_response'              => json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'originator_conversation_id' => $response->OriginatorConversationID ?? $transaction->originator_conversation_id,
                'payment_reference'          => $response->OriginatorConversationID ?? $transaction->payment_reference,
            ]);
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
                'payment_reference'             => $response->OriginatorConversationID ?? null,
            ];
        }

        $transaction->update($data);

        return $transaction;
    }

    /**
     * Send transaction details to Safaricom B2B API for paybill.
     *
     * @param string $recipient
     * @param string $requester
     * @param int $amount
     * @param string $accountReference
     * @param string|null $resultUrl
     * @param bool $appendMpesaUuidToUrl
     * @param array $customFieldsKeyValue
     * @return MpesaTransaction |  null
     * @throws MpesaChargeException |FileNotFoundException
     */
    public function paybill(
        string $recipient,
        string $requester,
        int $amount,
        string $accountReference,
        ?string $resultUrl = null,
        bool $appendMpesaUuidToUrl = true,
        array $customFieldsKeyValue = []
    ): MpesaTransaction|null {

        $balance = MpesaBalance::where('short_code', $this->clientCredential->shortcode)
            ->orderBy('created_at', 'desc')
            ->first();

        $working = (int) ($balance->working_account ?? 0);

        $charge = MpesaTransactionChargeService::getCharge($amount, MpesaTransactionChargeType::BUSINESS);

        $total = $amount + $charge;

        if ($working < $total) {
            Log::error('Insufficient balance...', [
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
            'CommandID'                => $this->paybillCommandId,
            'SenderIdentifierType'     => 4,
            'RecieverIdentifierType'   => 4,
            'Amount'                   => $amount,
            'AccountReference'         => $accountReference,
            'PartyA'                   => $this->clientCredential->shortcode,
            'PartyB'                   => $recipient,
            'Requester'                => $requester,
            'Remarks'                  => 'paybill payment',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $resultUrl,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'uuid'              => $originatorConversationID,
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->clientCredential->shortcode,
            'transaction_type'  => 'PayBill',
            'account_number'    => $recipient,
            'requester_mobile'  => $requester,
            'bill_reference'    => $accountReference,
            'amount'            => $amount,
            'transaction_charge' => $charge,
            'json_request'      => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            $transaction->update([
                'json_response'              => json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'originator_conversation_id' => $response->OriginatorConversationID ?? $transaction->originator_conversation_id,
                'payment_reference'          => $response->OriginatorConversationID ?? $transaction->payment_reference,
            ]);
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
                'payment_reference'             => $response->OriginatorConversationID ?? null,
            ];
        }

        $transaction->update($data);

        return $transaction;
    }

    /**
     * Send transaction details to Safaricom B2B API for pochi.
     *
     * @param string $recipient
     * @param int $amount
     * @param string|null $resultUrl
     * @param bool $appendMpesaUuidToUrl
     * @param array $customFieldsKeyValue
     * @return MpesaTransaction |  null
     * @throws FileNotFoundException|MpesaChargeException
     */
    public function pochi(
        string $recipient,
        int $amount,
        ?string $resultUrl = null,
        bool    $appendMpesaUuidToUrl = true,
        array $customFieldsKeyValue = []
    ): MpesaTransaction|null {

        $balance = MpesaBalance::where('short_code', $this->clientCredential->shortcode)
            ->orderBy('created_at', 'desc')
            ->first();

        $working = (int) ($balance->working_account ?? 0);

        $charge = MpesaTransactionChargeService::getCharge($amount, MpesaTransactionChargeType::BUSINESS);

        $total = $amount + $charge;

        if ($working < $total) {
            Log::error('Insufficient balance...', [
                'short_code' => $this->clientCredential->shortcode,
                'balance'    => $working,
                'required_amount' => $total,
            ]);
            return null;
        }

        $originatorConversationID = (string) Str::uuid7();

        $resultUrl = $resultUrl ?? DarajaHelper::getMobileResultUrl();
        if($appendMpesaUuidToUrl) {
            $resultUrl = $resultUrl . '/'. $originatorConversationID;
        }


        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'InitiatorName'                => $this->clientCredential->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential($this->clientCredential->password),
            'CommandID'                => $this->pochiCommandId,
            'Amount'                   => $amount,
            'PartyA'                   => $this->clientCredential->shortcode,
            'PartyB'                   => $recipient,
            'Remarks'                  => 'pochi payment',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $resultUrl,
            'Occassion'                => 'pochi payment'
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'uuid'              => $originatorConversationID,
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->clientCredential->shortcode,
            'transaction_type'  => 'Pochi',
            'account_number'    => $recipient,
            'amount'            => $amount,
            'transaction_charge' => $charge,
            'json_request'      => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            $transaction->update([
                'json_response'              => json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'originator_conversation_id' => $response->OriginatorConversationID ?? $transaction->originator_conversation_id,
                'payment_reference'          => $response->OriginatorConversationID ?? $transaction->payment_reference,
            ]);
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
                'payment_reference'             => $response->OriginatorConversationID ?? null,
            ];
        }

        $transaction->update($data);

        return $transaction;
    }
}
