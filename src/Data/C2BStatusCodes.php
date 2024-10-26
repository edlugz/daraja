<?php

namespace EdLugz\Daraja\Data;

class C2BStatusCodes
{
    // Success Codes
    public const SUCCESS = 0;

    // Error Codes
    public const INSUFFICIENT_BALANCE = 1;
    public const DAILY_LIMIT_EXCEEDED = 4;
    public const DUPLICATE_ORIGINATOR_CONVERSATION_ID = 15;
    public const SYSTEM_INTERNAL_ERROR = 17;
    public const INITIATOR_NOT_ALLOWED = 21;
    public const SYSTEM_BUSY = 26;
    public const TRANSACTION_IN_PROGRESS = 1001;
    public const TRANSACTION_EXPIRED = 1019;
    public const PUSH_REQUEST_ERROR = 1025;
    public const REQUEST_CANCELLED_BY_USER = 1031; // Duplicate code could be handled elsewhere
    public const SMSC_ACK_TIMEOUT = 1036;
    public const DS_TIMEOUT_USER_UNREACHABLE = 1037;
    public const INVALID_PROMPT_MESSAGE_PREFIX = 1101;
    public const INVALID_AUTHENTICATION_MESSAGE = 1102;
    public const INVALID_INITIATOR_INFO = 2001;
    public const AUTHENTICATION_FAILED = 2008;
    public const CHANNEL_SESSION_EXPIRED = 2026;
    public const REQUEST_NOT_PERMITTED = 2028;
    public const SECURITY_CREDENTIAL_LOCKED = 8006;
    public const INTERNAL_ERROR = 9999;
    public const STORAGE_OBJECT_SAVE_FAILURE = 100000100;

    // Messages
    //TODO - format to user friendly messages
    private static $messages = [
        self::SUCCESS => "The service request is processed successfully.",
        self::INSUFFICIENT_BALANCE => "The balance is insufficient for the transaction.",
        self::DAILY_LIMIT_EXCEEDED => "Declined due to limit rule: would exceed the daily transfer limit.",
        self::DUPLICATE_ORIGINATOR_CONVERSATION_ID => "[Controller - ]Duplicate OriginatorConversationID.",
        self::SYSTEM_INTERNAL_ERROR => "System internal error.",
        self::INITIATOR_NOT_ALLOWED => "The initiator is not allowed to initiate this request.",
        self::SYSTEM_BUSY => "System busy. The service request is rejected.",
        self::TRANSACTION_IN_PROGRESS => "Unable to lock subscriber, a transaction is already in process for the current subscriber.",
        self::TRANSACTION_EXPIRED => "Transaction has expired.",
        self::PUSH_REQUEST_ERROR => "Error Occurred while sending push request.",
        self::REQUEST_CANCELLED_BY_USER => "Request cancelled by user.",
        self::SMSC_ACK_TIMEOUT => "SMSC ACK timeout.",
        self::DS_TIMEOUT_USER_UNREACHABLE => "DS timeout user cannot be reached.",
        self::INVALID_PROMPT_MESSAGE_PREFIX => "Invalid Input parameter 'Prompt message prefix', length should be less than 94 characters.",
        self::INVALID_AUTHENTICATION_MESSAGE => "Invalid Input parameters 'Authentication message' and 'Prompt message prefix', length of ('Authentication message' + 'Prompt message prefix') should be less than 93 characters.",
        self::INVALID_INITIATOR_INFO => "The initiator information is invalid.",
        self::AUTHENTICATION_FAILED => "Authentication Failed.",
        self::CHANNEL_SESSION_EXPIRED => "The channel session ID has expired.",
        self::REQUEST_NOT_PERMITTED => "The request is not permitted according to product assignment.",
        self::SECURITY_CREDENTIAL_LOCKED => "The security credential is locked.",
        self::INTERNAL_ERROR => "Internal error occurred while executing the transaction.",
        self::STORAGE_OBJECT_SAVE_FAILURE => "[Controller - ]Saving object to storage fail, value type is class com.huawei.sag.sfcb2c.common.infobean.B2cSrcInfoBean."
    ];

    /**
     * Get the message corresponding to a status code.
     *
     * @param int $code
     * @return string
     */
    public static function getMessage(int $code): string
    {
        return self::$messages[$code] ?? "Unknown error code.";
    }
}