<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Backend\Payments\PaymentsController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Library\SslCommerz\SslCommerzNotification;
use App\Models\OrderGroup;

class SslCommerzPaymentController extends Controller
{
    public function initPayment()
    {
        return view('payments.SslCommerz');
    }
    public function exampleEasyCheckout()
    {
        return view('exampleEasycheckout');
    }
    public function exampleHostedCheckout()
    {
        return view('exampleHosted');
    }

    public function payViaAjax(Request $request)
    {
        $amount = 0;
        if (session('payment_type') == 'order_payment') {
            $orderGroup = OrderGroup::where('order_code', session('order_code'))->first(['grand_total_amount']);
            $amount = round($orderGroup->grand_total_amount * 100);
        }
        if ($amount <= 0) {
            return (new PaymentsController)->payment_failed();
        }
        $post_data = array();
        $post_data['total_amount'] = $amount; 
        $post_data['currency'] = "BDT";
        $post_data['tran_id'] = uniqid(); 
        # CUSTOMER INFORMATION
        $post_data['cus_name'] = auth()->user()->name;
        $post_data['cus_email'] = auth()->user()->email;
        $post_data['cus_add1'] = 'Customer Address';
        $post_data['cus_add2'] = "";
        $post_data['cus_city'] = "";
        $post_data['cus_state'] = "";
        $post_data['cus_postcode'] = "";
        $post_data['cus_country'] = "Bangladesh";
        $post_data['cus_phone'] = auth()->user()->phone;
        $post_data['cus_fax'] = "";
        # SHIPMENT INFORMATION
        $post_data['ship_name'] = "Store Test";
        $post_data['ship_add1'] = "Dhaka";
        $post_data['ship_add2'] = "Dhaka";
        $post_data['ship_city'] = "Dhaka";
        $post_data['ship_state'] = "Dhaka";
        $post_data['ship_postcode'] = "1000";
        $post_data['ship_phone'] = "";
        $post_data['ship_country'] = "Bangladesh";
        $post_data['shipping_method'] = "NO";
        $post_data['product_name'] = "Computer";
        $post_data['product_category'] = "Goods";
        $post_data['product_profile'] = "physical-goods";
        $update_product = DB::table('order_groups')
            ->update([
                'transaction_id' => $post_data['tran_id'],
                'payment_status' => 'unpaid',
            ]);
        $sslc = new SslCommerzNotification();
        $payment_options = $sslc->makePayment($post_data, 'checkout', 'json');
        if (!is_array($payment_options)) {
            print_r($payment_options);
            $payment_options = array();
        }
    }

    public function success(Request $request)
    {
        $tran_id = $request->input('transaction_id');
        $amount = $request->input('grand_total_amount');
        $sslc = new SslCommerzNotification();
        $order_details = DB::table('order_groups') 
            ->select('transaction_id', 'payment_status','grand_total_amount')->first();
        if ($order_details->payment_status == 'unpaid') {
            $validation = $sslc->orderValidate($tran_id, $amount);
            if ($validation) {
                $update_product = DB::table('order_groups')  
                    ->update(['payment_status' => 'paid']);
               
                flash('Transaction is successfully Completed')->success();
            }
        } else if ($order_details->payment_status == 'paid') {
            flash('Transaction already successfully Completed')->success();
        } else {
          
            echo "Invalid Transaction";
        }
        return redirect()->route('customers.dashboard');
    }

    public function fail(Request $request)
    {
        $tran_id = $request->input('transaction_id');
        $order_details = DB::table('order_groups')
            ->where('transaction_id', $tran_id)
            ->select('transaction_id', 'payment_status', 'grand_total_amount')->first();
        if ($order_details->payment_status == 'unpaid') {
            $update_product = DB::table('order_groups')
                ->where('transaction_id', $tran_id)
                ->update(['payment_status' => 'failed']);
            echo "Transaction is Falied";
        } else if ($order_details->payment_status == 'paid') {
            echo "Transaction is already Successful";
        } else {
            echo "Transaction is Invalid";
        }
    }

    public function cancel(Request $request)
    {
        $tran_id = $request->input('transaction_id');
        $order_details = DB::table('order_groups')
            ->where('transaction_id', $tran_id)
            ->select('transaction_id', 'payment_status','grand_total_amount')->first();
        if ($order_details->payment_status == 'unpaid') {
            $update_product = DB::table('order_groups')
                ->where('transaction_id', $tran_id)
                ->update(['payment_status' => 'canceled']);
            echo "Transaction is Cancel";
        } else if ($order_details->payment_status == 'paid') {
            echo "Transaction is already Successful";
        } else {
            echo "Transaction is Invalid";
        }
    }

    public function ipn(Request $request)
    {
        if ($request->input('transaction_id')) 
        {
            $tran_id = $request->input('transaction_id');
            $order_details = DB::table('order_groups')
                ->where('transaction_id', $tran_id)
                ->select('transaction_id', 'payment_status','grand_total_amount')->first();
            if ($order_details->payment_status == 'unpaid') {
                $sslc = new SslCommerzNotification();
                $validation = $sslc->orderValidate($tran_id, $order_details->grand_total_amount);
                if ($validation == TRUE) {
                    $update_product = DB::table('order_groups')
                        ->where('transaction_id', $tran_id)
                        ->update(['payment_status' => 'paid']);
                    echo "Transaction is successfully Completed";
                }
            } else if ($order_details->payment_status == 'paid') {
                echo "Transaction is already successfully Completed";
            } else {
                echo "Invalid Transaction";
            }
        } else {
            echo "Invalid Data";
        }
    }

}
