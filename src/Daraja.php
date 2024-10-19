<?php

namespace EdLugz\Daraja;

use EdLugz\Daraja\Requests\B2B;
use EdLugz\Daraja\Requests\B2C;
use EdLugz\Daraja\Requests\Balance;
use EdLugz\Daraja\Requests\C2B;
use EdLugz\Daraja\Requests\Reversal;
use EdLugz\Daraja\Requests\Transaction;

class Daraja
{
    /**
     * Initiate a business to business transaction.
     *
     * @return B2B
     */
    public function b2b(): B2B
    {
        return new B2B();
    }

    /**
     * Initiate a business to customer transaction.
     *
     * @return B2C
     */
    public function b2c(): B2C
    {
        return new B2C();
    }

    /**
     * Initiate a balance enquiry.
     *
     * @return Balance
     */
    public function balance(): Balance
    {
        return new Balance();
    }

    /**
     * Initialize a customer to business transaction.
     *
     * @return C2B
     */
    public function c2b(): C2B
    {
        return new C2B();
    }

    /**
     * Initiate a transaction reversal.
     *
     * @return Reversal
     */
    public function reversal(): Reversal
    {
        return new Reversal();
    }

    /**
     * Initiate a transaction status check.
     *
     * @return Transaction
     */
    public function transaction(): Transaction
    {
        return new Transaction();
    }
}
