<?php

namespace EdLugz\Daraja\Helpers;

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

       return MpesaBalance::create([
            'short_code' => env('SHORTCODE'),
            'utility_account' =>  $accountBalances[1],
            'working_account' =>  $accountBalances[0],
            'uncleared_balance' =>  $accountBalances[2],
            'json_result' => json_encode($request->all())
        ]);
    }

    /**
     * Process b2c results.
     *
     * @param Request $request
     *
     * @return MpesaTransaction
     */
    public static function b2c(Request $request): MpesaTransaction
    {
        $transaction = MpesaTransaction::where('transaction_id', $request->input('transactionId'))->first();

        $transaction->update(
            [
                'json_result' => json_encode($request->all()),
            ]
        );

        if ($request->input('status') == '000000') {
            $transactionReceipt = $request->input('receiptNumber');

            if ($request->input('resultParameters')) {
                $params = $request->input('resultParameters');
                $keyValueParams = [];
                foreach ($params as $param) {
                    $keyValueParams[$param['id']] = $param['value'];
                }

                $transactionReceipt = $keyValueParams['transactionRef'];
            }

            $data = [
                'request_status'        => $request->input('status'),
                'request_message'       => $request->input('message'),
                'receipt_number'        => $request->input('receiptNumber'),
                'transaction_reference' => $transactionReceipt,
                'timestamp'             => $request->input('timestamp'),
            ];
        } else {
            $data = [
                'request_status'  => $request->input('status'),
                'request_message' => $request->input('message'),
                'timestamp'       => $request->input('timestamp'),
            ];
        }

        $transaction->update($data);

        return $transaction;
    }
}
