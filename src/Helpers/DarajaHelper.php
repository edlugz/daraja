<?php

namespace EdLugz\Daraja\Helpers;

use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Models\MpesaBalance;
use EdLugz\Daraja\Models\MpesaTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

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
     * @param ClientCredential $apiCredential
     * @return MpesaBalance
     */
    public static function balance(Request $request, ClientCredential $apiCredential): MpesaBalance
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
            'short_code'        => $apiCredential->shortcode,
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
     * @return MpesaTransaction
     */
    public static function mobile(Request $request): MpesaTransaction
    {
        $transaction = MpesaTransaction::where('originator_conversation_id', $request['Result']['OriginatorConversationID'])->first();

        // Accessing different elements of the array and assigning them to separate variables
        $resultType = $request['Result']['ResultType'];
        $resultCode = $request['Result']['ResultCode'];
        $resultDesc = $request['Result']['ResultDesc'];
        $originatorConversationID = $request['Result']['OriginatorConversationID'];
        $conversationID = $request['Result']['ConversationID'];
        $transactionID = $request['Result']['TransactionID'];

        $TransactionCompletedDateTime = $ReceiverPartyPublicName = '0';

        // Accessing ResultParameters
        if ($resultCode == 0) {
            $resultParameters = $request['Result']['ResultParameters']['ResultParameter'];

            // Loop through ResultParameters and assign them to separate variables
            if ($resultParameters) {
                foreach ($resultParameters as $parameter) {
                    ${$parameter['Key']} = $parameter['Value'];
                }
            }
        }

        if ($transaction) {
            $transaction->update(
                [
                    'result_type'                     => $resultType,
                    'result_code'                     => $resultCode,
                    'result_description'              => $resultDesc,
                    'transaction_id'                  => $transactionID,
                    'transaction_completed_date_time' => $TransactionCompletedDateTime == '0' ? date('YmdHis') : date('YmdHis', strtotime($TransactionCompletedDateTime)),
                    'receiver_party_public_name'      => $ReceiverPartyPublicName == '0' ? '0' : $ReceiverPartyPublicName,
                    'utility_account_balance'                 => $B2CWorkingAccountAvailableFunds,
                    'working_account_balance'                 => $B2CUtilityAccountAvailableFunds,
                    'json_result'                     => json_encode($request->all()),
                ]
            );
        }

        return $transaction;
    }

    /**
     * Process b2b - paybill results.
     *
     * @param Request $request
     *
     * @return MpesaTransaction
     */
    public static function b2b(Request $request): MpesaTransaction
    {
        $transaction = MpesaTransaction::where('originator_conversation_id', $request['Result']['OriginatorConversationID'])->first();

        // Accessing different elements of the array and assigning them to separate variables
        $resultType = $request['Result']['ResultType'];
        $resultCode = $request['Result']['ResultCode'];
        $resultDesc = $request['Result']['ResultDesc'];
        $originatorConversationID = $request['Result']['OriginatorConversationID'];
        $conversationID = $request['Result']['ConversationID'];
        $transactionID = $request['Result']['TransactionID'];

        $TransactionCompletedDateTime = $ReceiverPartyPublicName = '0';

        // Accessing ResultParameters
        if ($resultCode == 0) {
            $resultParameters = $request['Result']['ResultParameters']['ResultParameter'];

            // Loop through ResultParameters and assign them to separate variables
            if ($resultParameters) {
                foreach ($resultParameters as $parameter) {
                    ${$parameter['Key']} = $parameter['Value'];
                    if ($InitiatorAccountCurrentBalance) {
                        $parsedBalance = parseBalanceString($InitiatorAccountCurrentBalance);
                        if ($parsedBalance) {
                            $B2CWorkingAccountAvailableFunds = $parsedBalance['BasicAmount'];
                        }
                    }
                }
            }
        }

        if ($transaction) {
            $transaction->update(
                [
                    'result_type'                     => $resultType,
                    'result_code'                     => $resultCode,
                    'result_description'              => $resultDesc,
                    'transaction_id'                  => $transactionID,
                    'transaction_completed_date_time' => $TransCompletedTime == '0' ? date('YmdHis') : date('YmdHis', strtotime($TransCompletedTime)),
                    'receiver_party_public_name'      => $ReceiverPartyPublicName == '0' ? '0' : $ReceiverPartyPublicName,
                    'working_account_balance'         => $B2CWorkingAccountAvailableFunds,
                    'utility_account_balance'         => null,
                    'json_result'                     => json_encode($request->all()),
                ]
            );
        }

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
}
