<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Requests;

use EdLugz\Daraja\DarajaClient;
use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Helpers\DarajaHelper;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Log;

/**
 *
 */
class Balance extends DarajaClient
{
    /**
     * Safaricom APIs Balance endpoint.
     *
     * @var string
     */
    protected string $endPoint = 'mpesa/accountbalance/v1/query';

    /**
     * Safaricom APIs Balance command id.
     *
     * @var string
     */
    protected string $commandId;

    /**
     * Safaricom APIs Balance queue timeout URI.
     *
     * @var string
     */
    protected string $queueTimeOutURL;

    /**
     * Where the Safaricom Account Balance API will post the result of the transaction.
     *
     * @var string
     */
    public string $resultURL;

    /**
     * Necessary initializations for Balance transactions from the config file while
     * also initialize parent constructor.
     *
     * @throws DarajaRequestException
     */
    public function __construct(ClientCredential $clientCredential, ?string $resultURL = null)
    {
        parent::__construct($clientCredential);

        $this->queueTimeOutURL = DarajaHelper::getTimeoutUrl();
        $this->commandId = 'AccountBalance';
        $this->resultURL = $resultURL ?? DarajaHelper::getBalanceResultUrl();
    }

    /**
     * Send transaction details to Safaricom Balance API.
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function check(): void
    {
        $parameters = [
            'Initiator'          => $this->clientCredential->initiator,
            'SecurityCredential' => DarajaHelper::setSecurityCredential($this->clientCredential->password),
            'CommandID'          => $this->commandId,
            'PartyA'             => $this->clientCredential->shortcode,
            'IdentifierType'     => '4',
            'Remarks'            => 'Account balance',
            'QueueTimeOutURL'    => $this->queueTimeOutURL,
            'ResultURL'          => $this->resultURL,
        ];

        try {
            $this->call($this->endPoint, ['json' => $parameters]);
        } catch (DarajaRequestException $e) {
            Log::error($e);
        }
    }
}
