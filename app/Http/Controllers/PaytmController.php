<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use paytm\paytmchecksum\PaytmChecksum;

class PaytmController extends Controller
{
    public function paymentNow(Request $request)
    {


        /* initialize an array */
        $paytmParams = array();

        /* add parameters in Array */

        $paytmParams["MID"] = "iELVJt50414347554560";
        $paytmParams["ORDER_ID"] = Str::orderedUuid();
        $paytmParams['CUST_ID'] = "CUST_001";
        $paytmParams['WEBSITE'] = 'WEBSTAGING';
        $paytmParams['CHANNEL_ID'] = 'WEB';
        $paytmParams['INDUSTRY_TYPE_ID'] = 'Retail';
        $paytmParams['TXN_AMOUNT'] = $request->amount;
        $paytmParams['CALLBACK_URL'] = 'http://localhost:8000/api/paytm-callback';
        $paytmParams['EMAIL'] = $request->email;

        /**
         * Generate checksum by parameters we have
         * Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
         */
        $paytmParams['CHECKSUMHASH'] = PaytmChecksum::generateSignature($paytmParams, 'zXhNYVPF4RKIsIIz');



        return response()->json($paytmParams);
    }
    public function paytmCallback(Request $request)
    {
        // return $request->all();
        $isVerifySignature = PaytmChecksum::verifySignature($request->all(), 'zXhNYVPF4RKIsIIz', $request->CHECKSUMHASH);
        if ($isVerifySignature) {

            /* initialize an array */
            $paytmParams = array();

            /* body parameters */
            $paytmParams["body"] = array(

                /* Find your MID in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys */
                "mid" => "iELVJt50414347554560",

                /* Enter your order id which needs to be check status for */
                "orderId" => $request->ORDERID,
            );

            /**
             * Generate checksum by parameters we have in body
             * Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
             */
            $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"]), "zXhNYVPF4RKIsIIz");

            /* head parameters */
            $paytmParams["head"] = array(

                /* put generated checksum value here */
                "signature"    => $checksum
            );

            /* prepare JSON string for request */
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
            return response()->json(
                json_decode($response)
            );
        } else {
            return "Checksum Mismatched";
        }
    }
}
