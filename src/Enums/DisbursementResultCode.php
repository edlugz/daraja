<?php

namespace EdLugz\Daraja\Enums;
enum DisbursementResultCode
{
    case SUCCESS;
    case INSUFFICIENT_BALANCE;
    case SYSTEM_ERROR;
    case EXTERNAL_VALIDATION_FAILED;
    case PRODUCT_ASSIGNMENT_ERROR;
    case UNRESOLVED_REASON;
    case RECEIPT_NOT_FOUND;
    case CREDIT_PARTY_UNSUPPORTED;
    case ORGANIZATION_NOT_CHILD;
    case ACCOUNT_NUMBER_INVALID;
    case OPERATOR_NOT_EXIST;
    case LIMIT_RULE_MINIMUM;
    case LIMIT_RULE_MAXIMUM;
    case RECEIVER_INVALID;
    case LIMIT_RULE_BALANCE;
    case DEBIT_PARTY_INVALID;
    case CREDIT_PARTY_INVALID;
    case INITIATOR_NOT_ALLOWED;
    case ACCOUNT_REFERENCE_INVALID;
    case INITIATOR_INVALID;
    case ACCOUNT_RULE_DECLINED;
    case INVALID_AMOUNT_FORMAT;
    case MSISDN_INVALID;
    case SECURITY_CREDENTIAL_LOCKED;
    case ID_TYPE_NOT_FOUND;
    case ID_NUMBER_MISMATCH;

    public function getCode(): string|int
    {
        return match ($this) {
            self::SUCCESS => 0,
            self::INSUFFICIENT_BALANCE => 1,
            self::SYSTEM_ERROR => 17,
            self::EXTERNAL_VALIDATION_FAILED => 2007,
            self::PRODUCT_ASSIGNMENT_ERROR => 2028,
            self::UNRESOLVED_REASON => 2029,
            self::RECEIPT_NOT_FOUND => 2033,
            self::CREDIT_PARTY_UNSUPPORTED => 2040,
            self::ORGANIZATION_NOT_CHILD => 2043,
            self::ACCOUNT_NUMBER_INVALID => 2202,
            self::OPERATOR_NOT_EXIST => 'SFC_IC0003',
            self::LIMIT_RULE_MINIMUM => 2,
            self::LIMIT_RULE_MAXIMUM => 3,
            self::RECEIVER_INVALID => 7,
            self::LIMIT_RULE_BALANCE => 8,
            self::DEBIT_PARTY_INVALID => 11,
            self::CREDIT_PARTY_INVALID => 14,
            self::INITIATOR_NOT_ALLOWED => 21,
            self::ACCOUNT_REFERENCE_INVALID => 1005,
            self::INITIATOR_INVALID => 2001,
            self::ACCOUNT_RULE_DECLINED => 2006,
            self::INVALID_AMOUNT_FORMAT => 2020,
            self::MSISDN_INVALID => 2051,
            self::SECURITY_CREDENTIAL_LOCKED => 8006,
            self::ID_TYPE_NOT_FOUND => 'R002',
            self::ID_NUMBER_MISMATCH => 'R003',
        };
    }

    public function getCustomerMessage(): string
    {
        return match ($this) {
            self::SUCCESS => "The transaction was successful.",
            self::INSUFFICIENT_BALANCE => "You have insufficient balance for this transaction.",
            self::SYSTEM_ERROR => "A system error occurred. Please try again later.",
            self::EXTERNAL_VALIDATION_FAILED => "Transaction validation failed. Please check details.",
            self::PRODUCT_ASSIGNMENT_ERROR => "This transaction is not permitted for your account type.",
            self::UNRESOLVED_REASON => "An unknown error occurred. Please contact support.",
            self::CREDIT_PARTY_UNSUPPORTED => "The recipient account type is not supported.",
            self::ORGANIZATION_NOT_CHILD => "Your organization is not linked to the initiator.",
            self::ACCOUNT_NUMBER_INVALID => "Invalid account number. Please check and retry.",
            self::OPERATOR_NOT_EXIST => "The specified mobile/till/paybill number does not exist.",
            self::LIMIT_RULE_MINIMUM => "The amount is below the minimum transaction limit.",
            self::LIMIT_RULE_MAXIMUM => "The amount exceeds the maximum transaction limit.",
            self::RECEIVER_INVALID => "Invalid recipient details. Please check and try again.",
            self::LIMIT_RULE_BALANCE => "Transaction would exceed the maximum account holding balance.",
            self::DEBIT_PARTY_INVALID => "The sender account is in an invalid state.",
            self::CREDIT_PARTY_INVALID => "The recipient account is in an invalid state.",
            self::INITIATOR_NOT_ALLOWED => "You are not authorized to perform this transaction.",
            self::ACCOUNT_REFERENCE_INVALID => "Invalid account reference provided.",
            self::INITIATOR_INVALID => "Your authentication credentials are incorrect.",
            self::ACCOUNT_RULE_DECLINED => "Your account status does not allow this transaction.",
            self::INVALID_AMOUNT_FORMAT => "Invalid amount format. Please enter a valid number.",
            self::MSISDN_INVALID => "Invalid phone number format.",
            self::SECURITY_CREDENTIAL_LOCKED => "Your authentication credentials are locked. Contact support.",
            self::ID_TYPE_NOT_FOUND => "ID type not found for the customer.",
            self::ID_NUMBER_MISMATCH => "ID number does not match our records.",
        };
    }

    public function shouldRetry(): bool
    {
        return match ($this) {
            self::SYSTEM_ERROR,
            self::UNRESOLVED_REASON,
            self::RECEIPT_NOT_FOUND => true, // Temporary issues, retry is possible
            default => false, // Permanent errors, no retry recommended
        };
    }
}
