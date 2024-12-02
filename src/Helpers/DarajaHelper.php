<?php

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
        $publicKey = File::get(__DIR__.'/cert/production.cer');

        openssl_public_encrypt($password, $output, $publicKey, OPENSSL_PKCS1_PADDING);

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
        return base64_encode($shortcode.$passkey.$timestamp);
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
            $accountBalances[] = $accountDetails[2];
        }

        return MpesaBalance::create([
            'account_id'        => $clientCredential->accountId,
            'short_code'        => $clientCredential->shortcode,
            'utility_account'   => $accountBalances[1],
            'working_account'   => $accountBalances[0],
            'uncleared_balance' => $accountBalances[2],
            'json_result'       => json_encode($request->all()),
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

        if(!$transaction){
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

        if($resultCode == 0){
            $data = [
                'result_type'                     => $resultType,
                'result_code'                     => $resultCode,
                'result_description'              => $resultDesc,
                'transaction_id'                  => $transactionID,
                'transaction_completed_date_time' => $TransactionCompletedDateTime == '0' ? date('YmdHis') : date('YmdHis', strtotime($TransactionCompletedDateTime)),
                'receiver_party_public_name'      => $ReceiverPartyPublicName == '0' ? '0' : $ReceiverPartyPublicName,
                'utility_account_balance'         => $B2CWorkingAccountAvailableFunds,
                'working_account_balance'         => $B2CUtilityAccountAvailableFunds,
                'json_result'                     => json_encode($request->all()),
            ];
        } else {
            $data = [
                'result_type'                     => $resultType,
                'result_code'                     => $resultCode,
                'result_description'              => $resultDesc,
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

        if(!$transaction) {
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

        if($resultCode == 0){
            $data = [
                'result_type'                     => $resultType,
                'result_code'                     => $resultCode,
                'result_description'              => $resultDesc,
                'transaction_id'                  => $transactionID,
                'transaction_completed_date_time' => $TransCompletedTime == '0' ? date('YmdHis') : date('YmdHis', strtotime($TransCompletedTime)),
                'receiver_party_public_name'      => $ReceiverPartyPublicName == '0' ? '0' : $ReceiverPartyPublicName,
                'working_account_balance'         => $B2CWorkingAccountAvailableFunds,
                'utility_account_balance'         => null,
                'json_result'                     => json_encode($request->all()),
            ];
        } else {
            $data = [
                'result_type'                     => $resultType,
                'result_code'                     => $resultCode,
                'result_description'              => $resultDesc,
            ];
        }

        $transaction->update($data);

        return $transaction;
    }

    /**
     * @param Request $request
     * @return MpesaFunding
     */
    public static function c2b(Request $request) : MpesaFunding
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

        if($resultCode == 0){
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

        if($transaction){

            $resultType = $request['Result']['ResultType'];
            $resultCode = $request['Result']['ResultCode'];
            $resultDesc = $request['Result']['ResultDesc'];

            if($resultCode == 0){
                $resultParameters = $request['Result']['ResultParameters']['ResultParameter'];

                if($resultParameters){
                    foreach ($resultParameters as $parameter) {
                        ${$parameter['Key']} = array_key_exists('Value', $parameter) ? $parameter['Value'] : null;
                    }
                }

                $data = [
                    'result_type'                     => $resultType,
                    'result_code'                     => $resultCode,
                    'result_description'              => $resultDesc,
                    'transaction_id'                  => $ReceiptNo,
                    'transaction_status'              => $TransactionStatus,
                    'transaction_completed_date_time' => !$FinalisedTime || $FinalisedTime == '0'
                        ? date('YmdHis')
                        : date('YmdHis', strtotime($FinalisedTime)),
                    'receiver_party_public_name'      => $CreditPartyName ?: '0',
                    'json_result'                     => json_encode($request->all()),
                ];
            } else {
                $data = [
                    'result_type'                     => $resultType,
                    'result_code'                     => $resultCode,
                    'result_description'              => $resultDesc,
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

        if($transaction){

            $resultType = $data['Result']['ResultType'] ?? null;
            $resultCode = $data['Result']['ResultCode'] ?? null;
            $resultDesc = $data['Result']['ResultDesc'] ?? null;
            $transactionID = $data['Result']['TransactionID'] ?? null;

            if($resultCode == 0){
                $resultParameters = $data['Result']['ResultParameters']['ResultParameter'] ?? [];

                if($resultParameters){
                    foreach ($resultParameters as $parameter) {
                        ${$parameter['Key']} = array_key_exists('Value', $parameter) ? $parameter['Value'] : null;
                    }
                }

                $data = [
                    'result_type'                     => $resultType,
                    'result_code'                     => $resultCode,
                    'result_description'              => $resultDesc,
                    'transaction_id'                  => $transactionID,
                    'transaction_completed_date_time' => !$TransCompletedTime || $TransCompletedTime == '0'
                        ? date('YmdHis')
                        : date('YmdHis', strtotime($TransCompletedTime)),
                    'receiver_party_public_name'      => $CreditPartyPublicName ?: '0',
                    'json_result'                     => json_encode($request->all()),
                ];
            } else {
                $data = [
                    'result_type'                     => $resultType,
                    'result_code'                     => $resultCode,
                    'result_description'              => $resultDesc,
                ];
            }

            $transaction->update($data);

            return $transaction;

        } else {
            return null;
        }
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
            passkey:  $apiCredential->pass_key
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
