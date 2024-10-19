<?php

namespace EdLugz\Daraja\Data;

readonly class ClientCredential
{
    /**
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param string $shortcode
     * @param string $initiator
     * @param string $password
     */
    public function __construct(
        public string $consumerKey,
        public string $consumerSecret,
        public string $shortcode,
        public string $initiator,
        public string $password,
        public string $passkey,
    )
    {
    }
}