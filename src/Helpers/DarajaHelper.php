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
     * Process balance results.
     *
     * @param Request $request
     *
     * @return MpesaBalance
     */
    public static function balance(Request $request): MpesaBalance
    {
        $balance = MpesaBalance::where('transaction_id', $request->input('transactionId'))->first();

        $balance->update(
            [
                'json_result' => json_encode($request->all()),
            ]
        );

        if ($request->input('status') == '000000') {
            $transactionReceipt = $request->input('receiptNumber');

            $registeredName = 'N/A';

            if ($request->input('resultParameters')) {
                $params = $request->input('resultParameters');
                $keyValueParams = [];
                foreach ($params as $param) {
                    $keyValueParams[$param['id']] = $param['value'];
                }

                $transactionReceipt = $keyValueParams['transactionRef'];
                $registeredName = $keyValueParams['accountName'];
            }

            $data = [
                'request_status'      => $request->input('status'),
                'request_message'     => $request->input('message'),
                'receipt_number'      => $request->input('receiptNumber'),
                'transaction_receipt' => $transactionReceipt,
                'registered_name'     => $registeredName,
                'timestamp'           => $request->input('timestamp'),
            ];
        } else {
            $data = [
                'request_status'  => $request->input('status'),
                'request_message' => $request->input('message'),
                'timestamp'       => $request->input('timestamp'),
            ];
        }

        $balance->update($data);

        return $balance;
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
