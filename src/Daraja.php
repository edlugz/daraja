<?php

namespace EdLugz\Daraja;

use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Requests\B2B;
use EdLugz\Daraja\Requests\B2C;
use EdLugz\Daraja\Requests\Balance;
use EdLugz\Daraja\Requests\C2B;
use EdLugz\Daraja\Requests\MNOLookUp;
use EdLugz\Daraja\Requests\Reversal;
use EdLugz\Daraja\Requests\TransactionStatus;
use EdLugz\Daraja\Requests\Transfer;
use EdLugz\Daraja\Requests\Tax;

/**
 *
 */
class Daraja
{
    /**
     * @var ClientCredential
     */
    protected ClientCredential $apiCredential;

    /**
     * @param ClientCredential $apiCredential
     */
    public function __construct(ClientCredential $apiCredential){
        $this->apiCredential = $apiCredential;
    }

    /**
     * Initiate a business to business transaction.
     *
     * @param ClientCredential $apiCredential
     *
     * @throws DarajaRequestException
     *
     * @return B2B
     */
    public function b2b(ClientCredential $apiCredential): B2B
    {
        return new B2B($apiCredential);
    }

    /**
     * Initiate a business to customer transaction.
     *
     * @param ClientCredential $apiCredential
     *
     * @throws DarajaRequestException
     *
     * @return B2C
     */
    public function b2c(ClientCredential $apiCredential): B2C
    {
        return new B2C($apiCredential);
    }

    /**
     * Initiate a balance enquiry.
     *
     * @param ClientCredential $apiCredential
     * @param string $resultUrl
     * @return Balance
     * @throws DarajaRequestException
     *
     * @return Balance
     */

    public function balance(ClientCredential $apiCredential, string $resultUrl) : Balance

    {
        return new Balance($apiCredential, $resultUrl);
    }

    /**
     * Initialize a customer to business transaction.
     *
     * @param ClientCredential $apiCredential
     * @param string $resultUrl
     * @return C2B
     * @throws DarajaRequestException
     */
    public function c2b(ClientCredential $apiCredential, string $resultUrl): C2B
    {
        return new C2B($apiCredential, $resultUrl);
    }

    /**
     * Initiate a transaction reversal.
     *
     * @param ClientCredential $apiCredential
     *
     * @throws DarajaRequestException
     *
     * @return Reversal
     */
    public function reversal(ClientCredential $apiCredential): Reversal
    {
        return new Reversal($apiCredential);
    }

    /**
     * Initiate a transaction status check.
     *
     * @param ClientCredential $apiCredential
     * @param string $resultUrl
     * @return TransactionStatus
     * @throws DarajaRequestException
     */
    public function transactionStatus(ClientCredential $apiCredential, string $resultUrl): TransactionStatus
    {
        return new TransactionStatus($apiCredential, $resultUrl);
    }

    /**
     * Initiate a funds transfer.
     *
     * @param ClientCredential $apiCredential
     * @param string $resultUrl
     * @return Transfer
     * @throws DarajaRequestException
     */
    public function transfer(ClientCredential $apiCredential, string $resultUrl): Transfer
    {
        return new Transfer($apiCredential, $resultUrl);
    }

    /**
     * Initiate a tax transaction.
     *
     * @param ClientCredential $apiCredential
     * @param string $resultUrl
     * @return Tax
     * @throws DarajaRequestException
     */
    public function tax(ClientCredential $apiCredential, string $resultUrl): Tax
    {
        return new Tax($apiCredential, $resultUrl);
    }

    public function mnoLookup(ClientCredential $apiCredential): MnoLookup
    {
        return new MnoLookup($apiCredential);
    }
}
