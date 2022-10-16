<?php

namespace App\Http\Controllers;

use App\Models\Paytm;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use paytm\paytmchecksum\PaytmChecksum;

class PaytmController extends Controller
{
    public function paymentNow(Request $request)
    {

        $paytmParams = array();

        $paytmParams["MID"] = env('PAYTM_MERCHANT_ID');
        $paytmParams["ORDER_ID"] = Str::orderedUuid();
        $paytmParams['CUST_ID'] = "CUST_001";
        $paytmParams['WEBSITE'] = env('PAYTM_MERCHANT_WEBSITE');
        $paytmParams['CHANNEL_ID'] = env('PAYTM_CHANNEL');
        $paytmParams['INDUSTRY_TYPE_ID'] = env('PAYTM_INDUSTRY_TYPE');
        $paytmParams['TXN_AMOUNT'] = $request->amount;
        $paytmParams['CALLBACK_URL'] = 'http://localhost:8000/api/paytm-callback';
        $paytmParams['EMAIL'] = $request->email;


        $paytmParams['CHECKSUMHASH'] = PaytmChecksum::generateSignature($paytmParams, env('PAYTM_MERCHANT_KEY'));

        return response()->json($paytmParams);
    }
    public function paytmCallback(Request $request)
    {
        // return $request->all();
        $isVerifySignature = PaytmChecksum::verifySignature($request->all(), env('PAYTM_MERCHANT_KEY'), $request->CHECKSUMHASH);
        if ($isVerifySignature) {


            $paytmParams = array();

            $paytmParams["body"] = array(
                "mid" => env('PAYTM_MERCHANT_ID'),
                "orderId" => $request->ORDERID,
            );

            $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"]), env('PAYTM_MERCHANT_KEY'));


            $paytmParams["head"] = array(
                "signature"    => $checksum
            );


            $post_data = json_encode($paytmParams);

            /* for Staging */
            $url = "https://securegw-stage.paytm.in/v3/order/status";

            /* for Production */
            // $url = "https://securegw.paytm.in/v3/order/status";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            $response = curl_exec($ch);
            $result = json_decode($response);
            if ($result->body->resultInfo->resultStatus == 'TXN_SUCCESS') {
                Paytm::create([
                    'order_id' => $result->body->orderId,
                    'txn_id' => $result->body->txnId,
                    'txn_amount' => $result->body->txnAmount,
                    'currency' => "INR",
                    'bank_name' => $result->body->bankName,
                    'resp_msg' => $result->body->resultInfo->resultMsg,
                    'status' => $result->body->resultInfo->resultStatus,

                ]);
            }
            $orderId = $result->body->orderId;
            $url = "http://localhost:3000/status/";
            return  redirect()->away($url . $orderId);
        } else {
            $url = "http://localhost:3000/status/404";
            return  redirect()->away($url);
        }
    }

    public function getPaytmStatus($orderId)
    {
        $paytmStatement =  Paytm::where('order_id', $orderId)->first();
        return response()->json([
            'paytmStatement' => $paytmStatement
        ]);
    }
}
