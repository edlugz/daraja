<?php

namespace EdLugz\Daraja\Enums;

enum MpesaStatusCode: string
{
    case SUCCESS = '0';

    case INSUFFICIENT_BALANCE = '1';

    case DAILY_LIMIT_EXCEEDED = '4';
    case DUPLICATE_ORIGINATOR_CONVERSATION_ID = '15';
    case SYSTEM_INTERNAL_ERROR = '17';
    case INITIATOR_NOT_ALLOWED = '21';
    case SYSTEM_BUSY = '26';
    case SFC_IC0003 = 'SFC_IC0003';
    case EXTERNAL_VALIDATION_FAILED_FOR_C2B = '2007';
    case  ORGANIZATION_NOT_CHILD_OF_INITIATOR= '2043';
    case UNREGISTERED_NUMBER = '2040';
    case RECEIPT_NOT_FOUND_BY_ORIGINATOR_CONVERSATION_ID = '2033';

    case UNRESOLVED_REASON_TYPE = '2029';

    case TRANSACTION_IN_PROGRESS = '1001';
    case TRANSACTION_EXPIRED = '1019';
    case PUSH_REQUEST_ERROR = '1025';
    case REQUEST_CANCELLED_BY_USER = '1031';
    case SMSC_ACK_TIMEOUT = '1036';
    case DS_TIMEOUT_USER_UNREACHABLE = '1037';
    case INVALID_PROMPT_MESSAGE_PREFIX = '1101';
    case INVALID_AUTHENTICATION_MESSAGE = '1102';
    case INVALID_INITIATOR_INFO = '2001';
    case AUTHENTICATION_FAILED = '2008';
    case CHANNEL_SESSION_EXPIRED = '2026';
    case REQUEST_NOT_PERMITTED = '2028';

    case ACCOUNT_NUMBER_DOES_NOT_EXISTS = '2202';
    case SECURITY_CREDENTIAL_LOCKED = '8006';
    case INTERNAL_ERROR = '9999';
    case STORAGE_OBJECT_SAVE_FAILURE = '100000100';

    /**
     * Get the message corresponding to a status code.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return match ($this) {
            self::SUCCESS => "The service request is processed successfully.",
            self::INSUFFICIENT_BALANCE => "The balance is insufficient for the transaction.",
            self::DAILY_LIMIT_EXCEEDED => "Declined due to limit rule: would exceed the daily transfer limit.",
            self::DUPLICATE_ORIGINATOR_CONVERSATION_ID => "Duplicate OriginatorConversationID.",
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
            self::STORAGE_OBJECT_SAVE_FAILURE => "Saving object to storage fail, value type is class com.huawei.sag.sfcb2c.common.infobean.B2cSrcInfoBean.",
            self::SFC_IC0003 => 'The operator does not exist.',
            self::ORGANIZATION_NOT_CHILD_OF_INITIATOR => 'This organization is not a child organization of the initiator.',
            self::UNREGISTERED_NUMBER => 'Credit Party customer type (Unregistered or Registered Customer) can\'t be supported by the service .',
            self::RECEIPT_NOT_FOUND_BY_ORIGINATOR_CONVERSATION_ID => 'The transaction receipt number cannot be found by the specified OriginatorConversationID or ConversationID.',
            self::EXTERNAL_VALIDATION_FAILED_FOR_C2B => 'External validation failed for the C2B transaction.',
            self::UNRESOLVED_REASON_TYPE => 'Failed due to an unresolved reason type.',
            self::ACCOUNT_NUMBER_DOES_NOT_EXISTS => 'The Account Number you provided is not exist. Please check your input information.',
        };
    }

    public function getCustomerMessage() :string
    {
        return match ($this) {
            self::SUCCESS => '',
            self::INSUFFICIENT_BALANCE => "The balance is insufficient for the transaction.",
            self::DAILY_LIMIT_EXCEEDED => "Declined due to limit rule: would exceed the daily transfer limit.",
            self::DUPLICATE_ORIGINATOR_CONVERSATION_ID => '',
            self::SYSTEM_INTERNAL_ERROR => '',
            self::INITIATOR_NOT_ALLOWED => '',
            self::SYSTEM_BUSY => '',
            self::TRANSACTION_IN_PROGRESS => '',
            self::TRANSACTION_EXPIRED => '',
            self::PUSH_REQUEST_ERROR => '',
            self::REQUEST_CANCELLED_BY_USER => '',
            self::SMSC_ACK_TIMEOUT => '',
            self::DS_TIMEOUT_USER_UNREACHABLE => '',
            self::INVALID_PROMPT_MESSAGE_PREFIX => '',
            self::INVALID_AUTHENTICATION_MESSAGE => '',
            self::INVALID_INITIATOR_INFO => '',
            self::AUTHENTICATION_FAILED => '',
            self::CHANNEL_SESSION_EXPIRED => '',
            self::REQUEST_NOT_PERMITTED => 'The paybill does not accept b2b payments',
            self::SECURITY_CREDENTIAL_LOCKED => '',
            self::INTERNAL_ERROR => '',
            self::STORAGE_OBJECT_SAVE_FAILURE => '',
            self::SFC_IC0003 => 'Till Number does not exist.',
            self::ORGANIZATION_NOT_CHILD_OF_INITIATOR => '',
            self::UNREGISTERED_NUMBER => 'Unregistered Number',
            self::RECEIPT_NOT_FOUND_BY_ORIGINATOR_CONVERSATION_ID => 'Receipt not found',
            self::EXTERNAL_VALIDATION_FAILED_FOR_C2B => 'Invalid Account Number',
        };
    }

    public function shouldRetry() : bool
    {
        return match ($this) {
          self::SYSTEM_BUSY => true,
          default => false,
        };
    }


}
