<?php

namespace EdLugz\Daraja;

use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Requests\C2B;
use EdLugz\Daraja\Requests\B2B;
use EdLugz\Daraja\Requests\B2C;
use EdLugz\Daraja\Requests\Balance;
use EdLugz\Daraja\Requests\Reversal;
use EdLugz\Daraja\Requests\Transaction;

class Daraja
{
    protected ClientCredential $apiCredential;

    public function __construct(ClientCredential $apiCredential){
        $this->apiCredential = $apiCredential;
    }

    /**
     * Initiate a business to business transaction.
     *
     * @param ClientCredential $apiCredential
     * @return B2B
     *
     * @throws DarajaRequestException
     */
    public function b2b(ClientCredential $apiCredential) : B2B
    {
        return new B2B($apiCredential);
    }

    /**
     * Initiate a business to customer transaction.
     *
     * @param ClientCredential $apiCredential
     * @return B2C
     * @throws DarajaRequestException
     */
    public function b2c(ClientCredential $apiCredential) : B2C
    {
        return new B2C($apiCredential);
    }

    /**
     * Initiate a balance enquiry.
     *
     * @param ClientCredential $apiCredential
     * @return Balance
     * @throws DarajaRequestException
     */
    public function balance(ClientCredential $apiCredential) : Balance
    {
        return new Balance($apiCredential);
    }

    /**
     * Initialize a customer to business transaction.
     *
     * @param ClientCredential $apiCredential
     * @return C2B
     * @throws DarajaRequestException
     */
    public function c2b(ClientCredential $apiCredential) : C2B
    {
        return new C2B($apiCredential);
    }

    /**
     * Initiate a transaction reversal.
     *
     * @param ClientCredential $apiCredential
     * @return Reversal
     * @throws DarajaRequestException
     */
    public function reversal(ClientCredential $apiCredential) : Reversal
    {
        return new Reversal($apiCredential);
    }

    /**
     * Initiate a transaction status check.
     *
     * @param ClientCredential $apiCredential
     * @return Transaction
     * @throws DarajaRequestException
     */
    public function transaction(ClientCredential $apiCredential) : Transaction
    {
        return new Transaction($apiCredential);
    }

}
