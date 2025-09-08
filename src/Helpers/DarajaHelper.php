<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Helpers;

use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Models\MpesaBalance;
use EdLugz\Daraja\Models\MpesaFunding;
use EdLugz\Daraja\Models\MpesaTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use EdLugz\Daraja\Models\ApiCredential;

/**
 *
 */
class DarajaHelper
{
    /**
     * Encrypt initiator password.
     *
     * @param string $password
     *
     * @return string
     */
    public static function setSecurityCredential(string $password): string
    {
        $certPath = config('daraja.mode') === 'sandbox'
            ? __DIR__ . '/cert/sandbox.cer'
            : __DIR__ . '/cert/production.cer';

        if (!File::exists($certPath)) {
            throw new \RuntimeException("Daraja cert not found at {$certPath}");
        }

        $publicKey = File::get($certPath);

        $key = openssl_pkey_get_public($publicKey);

        if ($key === false) {
            throw new \RuntimeException('Invalid public key contents.');
        }

        $ok = openssl_public_encrypt($password, $output, $key, OPENSSL_PKCS1_PADDING);

        if (!$ok) {
            throw new \RuntimeException('openssl_public_encrypt failed.');
        }

        return base64_encode($output);
    }

    /**
     * Encrypt stk password.
     *
     * @param string $shortcode
     * @param string $passkey
     * @param string $timestamp
     *
     * @return string
     */
    public static function setPassword(string $shortcode, string $passkey, string $timestamp): string
    {
        return base64_encode($shortcode . $passkey . $timestamp);
    }

    /**
     * @param string $number
     * @return string
     */
    public static function formatMobileNumber(string $number): string
    {
        // keep leading '+' for E.164 check
        $raw = str_starts_with($number, '+') ? '+' . preg_replace('/\D/', '', substr($number, 1)) : preg_replace('/\D/', '', $number);

        // Already E.164 Kenya
        if (str_starts_with($raw, '+254')) {
            return substr($raw, 1); // return without '+', e.g. '2547...'
        }

        // Already national 254...
        if (str_starts_with($raw, '254')) {
            return $raw;
        }

        // '07...' or '7...' -> normalize to 2547...
        $raw = ltrim($raw, '0');
        return '254' . $raw;
    }


    /**
     * Process balance results.
     *
     * @param Request $request
     * @param ClientCredential $clientCredential
     * @return MpesaBalance
     */
    public static function balance(Request $request, ClientCredential $clientCredential): MpesaBalance
    {
        $accountBalances = $request['Result']['ResultParameters']['ResultParameter'][1]['Value'];

        $accountsString = $accountBalances;

        // Explode the string into an array of account information
        $accountsArray = explode('&', $accountsString);

        // Define arrays to store account details
        $accountBalances = [];

        // Loop through each account information
        foreach ($accountsArray as $account) {
            // Explode each account information into an array of details
            $accountDetails = explode('|', $account);
            //get balances
            $accountBalances[] = $accountDetails[3];
        }

        return MpesaBalance::create([
            'account_id' => $clientCredential->accountId,
            'short_code' => $clientCredential->shortcode,
            'utility_account' => $accountBalances[1],
            'working_account' => $accountBalances[0],
            'uncleared_balance' => $accountBalances[2],
            'json_result' => json_encode($request->all()),
        ]);

    }

    /**
     * Process b2c results.
     *
     * @param Request $request
     *
     * @return MpesaTransaction|null
     */
    public static function b2c(Request $request): ?MpesaTransaction
    {
        $transaction = MpesaTransaction::where('originator_conversation_id', $request['Result']['OriginatorConversationID'])->first();

        if (!$transaction) {
            return null;
        }

        // Accessing different elements of the array and assigning them to separate variables
        $resultType = $request['Result']['ResultType'];
        $resultCode = $request['Result']['ResultCode'];
        $resultDesc = $request['Result']['ResultDesc'];
        $transactionID = $request['Result']['TransactionID'];

        $TransactionCompletedDateTime = $ReceiverPartyPublicName = '0';

        // Accessing ResultParameters
        if ($resultCode == 0) {
            $resultParameters = $request['Result']['ResultParameters']['ResultParameter'];

            // Loop through ResultParameters and assign them to separate variables
            if ($resultParameters) {
                foreach ($resultParameters as $parameter) {
                    ${$parameter['Key']} = array_key_exists('Value', $parameter) ? $parameter['Value'] : null;
                }
            }
        }

        if ($resultCode == 0) {
            $completed = ($TransactionCompletedDateTime ?? '0') !== '0'
                ? \DateTimeImmutable::createFromFormat('YmdHis', preg_replace('/\D/','', $TransactionCompletedDateTime)) ?: new \DateTimeImmutable()
                : new \DateTimeImmutable();
            $data = [
                'result_type' => $resultType,
                'result_code' => $resultCode,
                'result_description' => $resultDesc,
                'transaction_id' => $transactionID,
                'transaction_completed_date_time' =>  $completed->format('YmdHis'),
                'receiver_party_public_name' => $ReceiverPartyPublicName ?? 0,
                'utility_account_balance' => $B2CWorkingAccountAvailableFunds ?? 0,
                'working_account_balance' => $B2CUtilityAccountAvailableFunds ?? 0,
                'json_result' => json_encode($request->all()),
            ];
        } else {
            $data = [
                'result_type' => $resultType,
                'result_code' => $resultCode,
                'result_description' => $resultDesc,
            ];
        }

        $transaction->update($data);

        return $transaction;
    }

    /**
     * Process b2b - paybill results.
     *
     * @param Request $request
     *
     * @return MpesaTransaction|null
     */
    public static function b2b(Request $request): ?MpesaTransaction
    {
        $transaction = MpesaTransaction::where('originator_conversation_id', $request['Result']['OriginatorConversationID'])->first();

        if (!$transaction) {
            return null;
        }

        // Accessing different elements of the array and assigning them to separate variables
        $resultType = $request['Result']['ResultType'];
        $resultCode = $request['Result']['ResultCode'];
        $resultDesc = $request['Result']['ResultDesc'];
        $transactionID = $request['Result']['TransactionID'];

        $TransCompletedTime = $ReceiverPartyPublicName = '0';

        // Accessing ResultParameters
        if ($resultCode == 0) {
            $resultParameters = $request['Result']['ResultParameters']['ResultParameter'];

            // Loop through ResultParameters and assign them to separate variables
            if ($resultParameters) {
                foreach ($resultParameters as $parameter) {
                    ${$parameter['Key']} = array_key_exists('Value', $parameter) ? $parameter['Value'] : null;
                }
                if ($InitiatorAccountCurrentBalance) {
                    $parsedBalance = self::parseBalanceString($InitiatorAccountCurrentBalance);
                    if ($parsedBalance) {
                        $B2CWorkingAccountAvailableFunds = $parsedBalance['BasicAmount'];
                    }
                }
            }
        }

        if ($resultCode == 0) {

            $raw = (string) ($TransCompletedTime ?? '');
            if ($raw === '' || $raw === '0') {
                $completed = new \DateTimeImmutable();
            } else {
                $digits = preg_replace('/\D+/', '', $raw) ?? '';
                $completed = \DateTimeImmutable::createFromFormat('YmdHis', $digits) ?: new \DateTimeImmutable();
            }

            $data = [
                'result_type' => $resultType,
                'result_code' => $resultCode,
                'result_description' => $resultDesc,
                'transaction_id' => $transactionID,
                'transaction_completed_date_time' => $completed->format('YmdHis'),
                'receiver_party_public_name' => $ReceiverPartyPublicName ?? 0,
                'working_account_balance' => $B2CWorkingAccountAvailableFunds,
                'utility_account_balance' => null,
                'json_result' => json_encode($request->all()),
            ];
        } else {
            $data = [
                'result_type' => $resultType,
                'result_code' => $resultCode,
                'result_description' => $resultDesc,
            ];
        }

        $transaction->update($data);

        return $transaction;
    }

    /**
     * @param Request $request
     * @return MpesaFunding|null
     */
    // change signature
    public static function c2b(Request $request): ?MpesaFunding
    {

        // Decode the JSON payload from the request
        $callback = $request->input('Body.stkCallback');

        $transaction = MpesaFunding::where('merchant_request_id', $callback['MerchantRequestID'])->first();

        // Extract data from the callback
        $resultCode = $callback['ResultCode'] ?? null;
        $resultDesc = $callback['ResultDesc'] ?? null;

        // Extract callback metadata items
        $items = $callback['CallbackMetadata']['Item'] ?? [];
        $data = [];

        foreach ($items as $item) {
            if (isset($item['Name']) && isset($item['Value'])) {
                $data[$item['Name']] = $item['Value'];
            }
        }

        // Extract specific metadata values
        $mpesaReceiptNumber = $data['MpesaReceiptNumber'] ?? null;
        $transactionDate = $data['TransactionDate'] ?? null;

        if ($resultCode == 0) {
            $data = [
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
                'mpesa_receipt_number' => $mpesaReceiptNumber,
                'transaction_date' => date('Y-m-d H:i:s', strtotime($transactionDate)),
                'json_result' => $request->input()
            ];
        } else {
            $data = [
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
            ];
        }

        if ($transaction) {
            $transaction->update($data);
        }

        return $transaction;

    }

    /**
     * Parse mobile transaction status results
     * @param Request $request
     * @return MpesaTransaction|null
     */
    public static function status(Request $request): ?MpesaTransaction
    {
        $transaction = MpesaTransaction::where('originator_conversation_id', $request['Result']['OriginatorConversationID'])->first();

        if ($transaction) {

            $resultType = $request['Result']['ResultType'];
            $resultCode = $request['Result']['ResultCode'];
            $resultDesc = $request['Result']['ResultDesc'];

            if ($resultCode == 0) {
                $resultParameters = $request['Result']['ResultParameters']['ResultParameter'];

                if ($resultParameters) {
                    foreach ($resultParameters as $parameter) {
                        ${$parameter['Key']} = array_key_exists('Value', $parameter) ? $parameter['Value'] : null;
                    }
                }

                $data = [
                    'result_type' => $resultType,
                    'result_code' => $resultCode,
                    'result_description' => $resultDesc,
                    'transaction_id' => $ReceiptNo ?? 0,
                    'transaction_status' => $TransactionStatus ?? null,
                    'transaction_completed_date_time' => !$FinalisedTime || $FinalisedTime == '0'
                        ? date('YmdHis')
                        : date('YmdHis', strtotime($FinalisedTime)),
                    'receiver_party_public_name' =>  $CreditPartyName ?? $ReceiverPartyPublicName ?? '0',
                    'json_result' => json_encode($request->all()),
                ];
            } else {
                $data = [
                    'result_type' => $resultType,
                    'result_code' => $resultCode,
                    'result_description' => $resultDesc,
                ];
            }

            $transaction->update($data);

            return $transaction;

        } else {
            return null;
        }
    }

    /**
     * Parse reversal transaction results
     * @param Request $request
     * @return MpesaTransaction|null
     */
    public static function reversal(Request $request): ?MpesaTransaction
    {
        $transaction = MpesaTransaction::where('originator_conversation_id', $request['Result']['OriginatorConversationID'])->first();

        if ($transaction) {

            $resultType = $request['Result']['ResultType'] ?? null;
            $resultCode = $request['Result']['ResultCode'] ?? null;
            $resultDesc = $request['Result']['ResultDesc'] ?? null;
            $transactionID = $request['Result']['TransactionID'] ?? null;

            if ($resultCode == 0) {
                $resultParameters = $request['Result']['ResultParameters']['ResultParameter'] ?? [];

                if ($resultParameters) {
                    foreach ($resultParameters as $parameter) {
                        ${$parameter['Key']} = array_key_exists('Value', $parameter) ? $parameter['Value'] : null;
                    }
                }

                $data = [
                    'result_type' => $resultType,
                    'result_code' => $resultCode,
                    'result_description' => $resultDesc,
                    'transaction_id' => $transactionID,
                    'transaction_completed_date_time' => !$TransCompletedTime || $TransCompletedTime == '0'
                        ? date('YmdHis')
                        : date('YmdHis', strtotime($TransCompletedTime)),
                    'receiver_party_public_name' => $CreditPartyPublicName ?: '0',
                    'json_result' => json_encode($request->all()),
                ];
            } else {
                $data = [
                    'result_type' => $resultType,
                    'result_code' => $resultCode,
                    'result_description' => $resultDesc,
                ];
            }

            $transaction->update($data);

            return $transaction;

        } else {
            return null;
        }
    }

    /**
     * @param Request $request
     * @return MpesaTransaction|null
     */
    public static function fundsTransfer(Request $request): ?MpesaTransaction
    {
        $transaction = MpesaTransaction::where('originator_conversation_id', $request['Result']['OriginatorConversationID'])->first();

        if (is_null($transaction)) {
            return null;
        }

        $resultType = $request['Result']['ResultType'] ?? null;
        $resultCode = $request['Result']['ResultCode'] ?? null;
        $resultDesc = $request['Result']['ResultDesc'] ?? null;
        $transactionId = $request['Result']['TransactionID'] ?? null;

        if ($resultCode == 0) {
            $resultParameters = $request['Result']['ResultParameters']['ResultParameter'] ?? [];

            if (!empty($resultParameters)) {
                foreach ($resultParameters as $parameter) {
                    ${$parameter['Key']} = $parameter['Value'] ?? null;
                }
            }

            $transCompletedTimeFormatted = !empty($TransCompletedTime) && $TransCompletedTime != '0'
                ? \DateTime::createFromFormat('YmdHis', $TransCompletedTime)->format('Y-m-d H:i:s')
                : now()->format('Y-m-d H:i:s');

            $data = [
                'result_type' => $resultType,
                'result_code' => $resultCode,
                'result_description' => $resultDesc,
                'transaction_id' => $transactionId,
                'transaction_completed_date_time' => $transCompletedTimeFormatted,
                'json_result' => json_encode($request->all()),
            ];
        } else {
            $data = [
                'transaction_id' => $transactionId,
                'result_type' => $resultType,
                'result_code' => $resultCode,
                'result_description' => $resultDesc,
                'json_result' => json_encode($request->all()),
            ];
        }

        $transaction->update($data);

        return $transaction;
    }

    /**
     * @param $balanceString
     * @return array|null
     */
    protected static function parseBalanceString($balanceString): ?array
    {
        $pattern = '/CurrencyCode=(.*?), MinimumAmount=(.*?), BasicAmount=(.*?)}/';
        if (preg_match($pattern, $balanceString, $matches)) {
            return [
                'CurrencyCode' => $matches[1],
                'MinimumAmount' => $matches[2],
                'BasicAmount' => $matches[3]
            ];
        }
        return null;
    }

    /**
     * @param ApiCredential $apiCredential
     * @return ClientCredential
     */
    public static function getCredentials(ApiCredential $apiCredential): ClientCredential
    {
        return new ClientCredential(
            accountId: $apiCredential->account_id,
            consumerKey: $apiCredential->consumer_key,
            consumerSecret: $apiCredential->consumer_secret,
            shortcode: $apiCredential->short_code,
            initiator: $apiCredential->initiator,
            password: $apiCredential->initiator_password,
            passkey: $apiCredential->pass_key,
            use_b2c_validation:  $apiCredential->use_b2c_validation,
        );
    }

    /**
     * @return string
     */
    public static function getDarajaBaseUrl(): string
    {
        return rtrim(config('daraja.base_url'), '/');
    }

    /**
     * @return string
     */
    public static function getBalanceResultUrl(): string
    {
        return self::getDarajaBaseUrl() . '/' . ltrim(config('daraja.balance_result_url'), '/');
    }

    /**
     * @return string
     */
    public static function getStkResultUrl(): string
    {
        return self::getDarajaBaseUrl() . '/' . ltrim(config('daraja.stk_result_url'), '/');
    }

    /**
     * @return string
     */
    public static function getFundsTransferResultUrl(): string
    {
        return self::getDarajaBaseUrl() . '/' . ltrim(config('daraja.funds_transfer_result_url'), '/');
    }

    /**
     * @return string
     */
    public static function getMobileResultUrl(): string
    {
        return self::getDarajaBaseUrl() . '/' . ltrim(config('daraja.mobile_result_url'), '/');
    }

    /**
     * @return string
     */
    public static function getTillResultUrl(): string
    {
        return self::getDarajaBaseUrl() . '/' . ltrim(config('daraja.till_result_url'), '/');
    }

    /**
     * @return string
     */
    public static function getPaybillResultUrl(): string
    {
        return self::getDarajaBaseUrl() . '/' . ltrim(config('daraja.paybill_result_url'), '/');
    }

    /**
     * @return string
     */
    public static function getReversalResultUrl(): string
    {
        return self::getDarajaBaseUrl() . '/' . ltrim(config('daraja.reversal_result_url'), '/');
    }

    /**
     * @return string
     */
    public static function getTransactionQueryResultUrl(): string
    {
        return self::getDarajaBaseUrl() . '/' . ltrim(config('daraja.transaction_query_result_url'), '/');
    }

    /**
     * @return string
     */
    public static function getTimeoutUrl(): string
    {
        return self::getDarajaBaseUrl() . '/' . ltrim(config('daraja.timeout_url'), '/');
    }

    /**
     * @return string
     */
    public static function getReversalQueryResultUrl(): string
    {
        return self::getDarajaBaseUrl() . '/' . ltrim(config('daraja.reversal_query_result_url'), '/');
    }
}
