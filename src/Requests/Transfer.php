<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use EdLugz\Daraja\Models\MpesaTransaction;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
class Transfer extends DarajaClient
{
    /**
     * Safaricom APIs B2B endpoint.
     *
     * @var string
     */
    protected string $endPoint = 'mpesa/b2b/v1/paymentrequest';

    /**
     * Safaricom APIs B2C command ids.
     *
     * @var string
     */
    protected string $workingCommandId;
    protected string $utilityCommandId;

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
        $this->resultURL = $resultURL ?? DarajaHelper::getFundsTransferResultUrl();
        $this->workingCommandId = 'BusinessTransferFromMMFToUtility';
        $this->utilityCommandId = 'OrgRevenueSettlement';
    }

    /**
     * Transfer funds from working(mmf) to utility account via Safaricom B2B API.
     * @param int $amount
     * @param array $customFieldsKeyValue
     * @return MpesaTransaction
     * @throws FileNotFoundException
     */
    public function workingToUtility(
        int $amount,
        array $customFieldsKeyValue = []
    ): MpesaTransaction {

        $originatorConversationID = (string) Str::uuid();

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'Initiator'                => $this->clientCredential->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential($this->clientCredential->password),
            'CommandID'                => $this->workingCommandId,
            "SenderIdentifierType"     => "4",
            "RecieverIdentifierType"   => "4",
            'Amount'                   => $amount,
            'PartyA'                   => $this->clientCredential->shortcode,
            'PartyB'                   => $this->clientCredential->shortcode,
            'Remarks'                  => 'Funds movement',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $this->resultURL,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->clientCredential->shortcode,
            'transaction_type'  => 'FundsTransfer',
            'account_number'    => $this->clientCredential->shortcode,
            'amount'            => $amount,
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

        if (array_key_exists('ResponseCode', (array) $response)) {
            if ($response->ResponseCode == '0') {
                $data = array_merge($data, [
                    'conversation_id'               => $response->ConversationID ?? null,
                    'originator_conversation_id'    => $response->OriginatorConversationID ?? null,
                    'payment_reference'             => $response->OriginatorConversationID ?? null,
                ]);
            }
        }

        $transaction->update($data);

        return $transaction;
    }

    /**
     * STransfer funds from utility to working(mmf) account via  Safaricom B2B API.
     * @param array $customFieldsKeyValue
     * @return MpesaTransaction
     * @throws FileNotFoundException
     */
    public function utilityToWorking(array $customFieldsKeyValue = []): MpesaTransaction {

        $originatorConversationID = (string) Str::uuid();

        $parameters = [
            'OriginatorConversationID' => $originatorConversationID,
            'Initiator'                => $this->clientCredential->initiator,
            'SecurityCredential'       => DarajaHelper::setSecurityCredential($this->clientCredential->password),
            'CommandID'                => $this->utilityCommandId,
            "SenderIdentifierType"     => "4",
            "RecieverIdentifierType"   => "4",
            'PartyA'                   => $this->clientCredential->shortcode,
            'PartyB'                   => $this->clientCredential->shortcode,
            'Remarks'                  => 'Funds movement',
            'QueueTimeOutURL'          => $this->queueTimeOutURL,
            'ResultURL'                => $this->resultURL,
        ];

        /** @var MpesaTransaction $transaction */
        $transaction = MpesaTransaction::create(array_merge([
            'payment_reference' => $originatorConversationID,
            'short_code'        => $this->clientCredential->shortcode,
            'transaction_type'  => 'FundsTransfer',
            'account_number'    => $this->clientCredential->shortcode,
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
            'response_code'          => $response->ResponseCode ??  null,
            'response_description'   => $response->ResponseDescription ?? null,
        ];

        if (($response->ResponseCode ?? null) === '0' || (string)($response->ResponseCode ?? '') === '0') {
            $data += [
                'conversation_id'               => $response->ConversationID ?? null,
                'originator_conversation_id'    => $response->OriginatorConversationID ?? null,
            ];
        }

        $transaction->update($data);

        return $transaction;
    }

}