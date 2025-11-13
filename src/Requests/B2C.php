<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Enums\IdentificationType;
use Edlugz\Daraja\Enums\MpesaTransactionChargeType;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaBalance;
use EdLugz\Daraja\Models\MpesaTransaction;
use EdLugz\Daraja\Services\MpesaTransactionChargeService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;
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
     * Safaricom APIs B2C With Validation endpoint.
     *
     * @var string
     */
    protected string $validationEndPoint = 'mpesa/b2cvalidate/v2/paymentrequest';

    /**
     * Safaricom APIs B2C command id.
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
     * Necessary initializations for B2C transactions from the config file.
     *
     * @param ClientCredential $clientCredential
     * @param string|null $resultURL
     * @throws DarajaRequestException
     */
    public function __construct(ClientCredential $clientCredential, ?string $resultURL = null)
    {
        parent::__construct($clientCredential);

        $this->queueTimeOutURL = DarajaHelper::getTimeoutUrl();
        $this->resultURL = $resultURL ?? DarajaHelper::getMobileResultUrl();
        $this->commandId = 'SalaryPayment';
    }

    /**
     * Send transaction details to Safaricom B2C API.
     *
     * @param string $recipient
     * @param string $idType
     * @param string $nationalId
     * @param int $amount
     * @param bool $appendMpesaUuidToUrl
     * @param array $customFieldsKeyValue
     * @return MpesaTransaction | null
     * @throws FileNotFoundException
     */
    public function payWithId(
        string  $recipient,
        string  $idType,
        string  $nationalId,
        int     $amount,
        bool    $appendMpesaUuidToUrl = true,
        array $customFieldsKeyValue = []
    ): MpesaTransaction|null {
        if(!$this->clientCredential->use_b2c_validation){
            Log::error('B2C with validation is not active', [
                'short_code' => $this->clientCredential->shortcode
            ]);
            return null;
        }
        $balance = MpesaBalance::where('short_code', $this->clientCredential->shortcode)
            ->orderBy('created_at', 'desc')
            ->first();

        $utility = (int) ($balance->utility_account ?? 0);

        $charge = MpesaTransactionChargeService::getCharge($amount, MpesaTransactionChargeType::MOBILE);

        $total = $amount + $charge;

        if ($utility < $total) {
            Log::error('Insufficient balance to process this transaction.', [
                'short_code' => $this->clientCredential->shortcode,
                'balance'    => $utility,
                'required_amount' => $total,
            ]);
            return null;
        }

        $idType = IdentificationType::fromName($idType);

        $originatorConversationID = (string) Str::uuid7();

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'InitiatorName'            => $this->clientCredential->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential($this->clientCredential->password),
            'CommandID'                => $this->commandId,
            'Amount'                   => $amount,
            'PartyA'                   => $this->clientCredential->shortcode,
            'PartyB'                   => DarajaHelper::formatMobileNumber($recipient),
            'IDType'                   => $idType?->value ?? IdentificationType::NATIONAL_ID->value,
            'IDNumber'                 => $nationalId,
            'Remarks'                  => 'send to mobile',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $this->resultURL . ($appendMpesaUuidToUrl ? '/' . $originatorConversationID : ''),
            'Occasion'                 => 'send to mobile',
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'uuid'              => $originatorConversationID,
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->clientCredential->shortcode,
            'transaction_type'  => 'SendMoney',
            'account_number'    => $recipient,
            'amount'            => $amount,
            'transaction_charge' => $charge,
            'id_type'           => $idType?->value ?? IdentificationType::NATIONAL_ID->value,
            'id_number'         => $nationalId,
            'json_request'      => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->validationEndPoint, ['json' => $parameters]);

            $transaction->update(
                [
                    'json_response' => json_encode($response,   JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
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
            $data = array_merge($data, [
                'conversation_id'               => $response->ConversationID ?? null,
                'originator_conversation_id'    => $response->OriginatorConversationID ?? null,
            ]);
        }

        $transaction->update($data);

        return $transaction;
    }

    /**
     * Send transaction details to Safaricom B2C API.
     *
     * @param string $recipient
     * @param int $amount
     * @param bool $appendMpesaUuidToUrl
     * @param array $customFieldsKeyValue
     *
     * @return MpesaTransaction|null
     * @throws FileNotFoundException
     */
    public function pay(
        string  $recipient,
        int     $amount,
        bool    $appendMpesaUuidToUrl = true,
        array   $customFieldsKeyValue = []
    ): MpesaTransaction|null {

        $balance = MpesaBalance::where('short_code', $this->clientCredential->shortcode)
            ->orderBy('created_at', 'desc')
            ->first();

        $utility = (int) ($balance->utility_account ?? 0);

        $charge = MpesaTransactionChargeService::getCharge($amount, MpesaTransactionChargeType::MOBILE);

        $total = $amount + $charge;

        if ($utility < $total) {
            Log::error('Insufficient balance to process this transaction.', [
                'short_code' => $this->clientCredential->shortcode,
                'balance'    => $utility,
                'required_amount' => $total,
            ]);
            return null;
        }

        $originatorConversationID = (string) Str::uuid7();

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'InitiatorName'            => $this->clientCredential->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential($this->clientCredential->password),
            'CommandID'                => $this->commandId,
            'Amount'                   => $amount,
            'PartyA'                   => $this->clientCredential->shortcode,
            'PartyB'                   => DarajaHelper::formatMobileNumber($recipient),
            'Remarks'                  => 'send to mobile',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $this->resultURL  . ($appendMpesaUuidToUrl ? '/' . $originatorConversationID : ''),
            'Occasion'                 => 'send to mobile',
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'uuid'              => $originatorConversationID,
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->clientCredential->shortcode,
            'transaction_type'  => 'SendMoney',
            'account_number'    => $recipient,
            'amount'            => $amount,
            'transaction_charge' => $charge,
            'json_request'      => json_encode($parameters),
        ], $customFieldsKeyValue));

        try {
            $response = $this->call($this->endPoint, ['json' => $parameters]);

            $transaction->update(
                [
                    'json_response' => json_encode($response,   JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
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
            $data = array_merge($data, [
                'conversation_id'               => $response->ConversationID ?? null,
                'originator_conversation_id'    => $response->OriginatorConversationID ?? null,
                'response_code'                 => $response->ResponseCode ?? null,
                'response_description'          => $response->ResponseDescription ?? null,
            ]);
        }

        $transaction->update($data);

        return $transaction;
    }
}
