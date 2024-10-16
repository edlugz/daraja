<?php

namespace EdLugz\Daraja\Helpers;

use EdLugz\Daraja\Data\ClientCredential;
use EdLugz\Daraja\Models\ApiCredential;
use EdLugz\Daraja\Models\MpesaBalance;
use EdLugz\Daraja\Models\MpesaTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DarajaHelper
{
    public static function apiCredentials(ApiCredential $apiCredential) : ClientCredential
    {
        return new ClientCredential(
            consumerKey: $apiCredential->consumer_key,
            consumerSecret: $apiCredential->consumer_secret,
            shortcode: $apiCredential->short_code,
            initiator: $apiCredential->initiator_name,
            password: $apiCredential->initiator_password);
    }
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
     *
     * @return MpesaBalance
     */
    public static function balance(Request $request): MpesaBalance
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

        $balance = MpesaBalance::create([
            'short_code'        => env('SHORTCODE'),
            'utility_account'   => $accountBalances[1],
            'working_account'   => $accountBalances[0],
            'uncleared_balance' => $accountBalances[2],
            'json_result'       => json_encode($request->all()),
        ]);

        return $balance;
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
        if ($resultDesc == 'The service request is processed successfully.') {
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
                    'json_result'                     => json_encode($request->all()),
                ]
            );
        }

        return $transaction;
    }
}
