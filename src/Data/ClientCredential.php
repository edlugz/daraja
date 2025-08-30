<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Data;

readonly class ClientCredential
{
    /**
     * @param int $accountId
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param string $shortcode
     * @param string $initiator
     * @param string $password
     * @param string $passkey
     * @param bool $use_b2c_validation
     */
    public function __construct(
        public int $accountId,
        public string $consumerKey,
        public string $consumerSecret,
        public string $shortcode,
        public string $initiator,
        public string $password,
        public string $passkey,
        public bool $use_b2c_validation,
    ) {
    }
}
