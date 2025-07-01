<?php

namespace EdLugz\Daraja\Data;

readonly class ClientCredential
{
    /**
     * @param string $accountId
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param string $shortcode
     * @param string $initiator
     * @param string $password
     * @param string $passkey
     * @param string $use_b2c_validation
     */
    public function __construct(
        public string $accountId,
        public string $consumerKey,
        public string $consumerSecret,
        public string $shortcode,
        public string $initiator,
        public string $password,
        public string $passkey,
        public string $use_b2c_validation,
    ) {
    }
}
