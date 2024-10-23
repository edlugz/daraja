<?php

namespace EdLugz\Daraja;

use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Exceptions\DarajaRequestException;
use EdLugz\Daraja\Requests\B2B;
use EdLugz\Daraja\Requests\B2C;
use EdLugz\Daraja\Requests\Balance;
use EdLugz\Daraja\Requests\C2B;
use EdLugz\Daraja\Requests\Reversal;
use EdLugz\Daraja\Requests\Transaction;

/**
 *
 */
class Daraja
{
    /**
     * @var ClientCredential
     */
    protected ClientCredential $apiCredential;

<<<<<<< Updated upstream
    public function __construct(ClientCredential $apiCredential)
    {
=======
    /**
     * @param ClientCredential $apiCredential
     */
    public function __construct(ClientCredential $apiCredential){
>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
     *
=======
     * @param string $resultUrl
     * @return Balance
>>>>>>> Stashed changes
     * @throws DarajaRequestException
     *
     * @return Balance
     */
<<<<<<< Updated upstream
    public function balance(ClientCredential $apiCredential): Balance
=======
    public function balance(ClientCredential $apiCredential, string $resultUrl) : Balance
>>>>>>> Stashed changes
    {
        return new Balance($apiCredential, $resultUrl);
    }

    /**
     * Initialize a customer to business transaction.
     *
     * @param ClientCredential $apiCredential
     *
     * @throws DarajaRequestException
     *
     * @return C2B
     */
    public function c2b(ClientCredential $apiCredential): C2B
    {
        return new C2B($apiCredential);
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
     *
     * @throws DarajaRequestException
     *
     * @return Transaction
     */
    public function transaction(ClientCredential $apiCredential): Transaction
    {
        return new Transaction($apiCredential);
    }
}
