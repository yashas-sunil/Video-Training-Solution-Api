<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Address;

class PaymentService
{
    /**
     * @param array $attributes
     * @return mixed
     */
    public function create($attributes = [])
    {
        $payment = new Payment;
        $payment->user_id = $this->getUserID($attributes['order_id']);

        if ($attributes['order_status'] == "Success" ) {
            $payment->receipt_no = $this->getReceiptNumber();
        }

        $payment->order_id = rtrim($attributes['order_id'], '-F');
        $payment->cc_avenue_order_id = $attributes['order_id'];

        $taxes = $this->getTaxes($attributes['amount'], $payment->user_id, $payment->order_id);

        $payment->cgst = $taxes['cgst'];
        $payment->cgst_amount = $taxes['cgst_amount'];
        $payment->sgst = $taxes['sgst'];
        $payment->sgst_amount = $taxes['sgst_amount'];
        $payment->igst = $taxes['igst'];
        $payment->igst_amount = $taxes['igst_amount'];
        $payment->transaction_id = $attributes['tracking_id'];
        $payment->transaction_response = json_encode($attributes);
        $payment->transaction_response_status = $attributes['order_status'];
        $payment->payment_status = $this->getPaymentStatus($attributes['order_status']);
        $payment->reward_amount = $this->getRewardAmount($attributes['order_id']);
        $payment->net_amount = $attributes['amount'];
        $payment->payment_updated_method = Payment::UPDATE_METHOD_CCAVENUE;
        $payment->save();

        return $payment;
    }

    public function easebuzzcreate($attributes = [])
    {
        $payment = new Payment;
        $payment->user_id = $this->getUserID($attributes['udf1']);

        if ( $attributes['status'] == "success") {
            $payment->receipt_no = $this->getReceiptNumber();
        }
        $cseet=$this->cseet($attributes['udf1']);

        $payment->order_id = $attributes['udf1'];
        $payment->ease_buzz_order_id = $attributes['udf1'];

        $taxes = $this->getTaxes($attributes['amount'], $payment->user_id, $payment->order_id);

        $payment->cgst = $taxes['cgst'];
        $payment->cgst_amount = $taxes['cgst_amount'];
        $payment->sgst = $taxes['sgst'];
        $payment->sgst_amount = $taxes['sgst_amount'];
        $payment->igst = $taxes['igst'];
        $payment->igst_amount = $taxes['igst_amount'];
        $payment->transaction_id = $attributes['txnid'];
        $payment->transaction_response = json_encode($attributes);
        $payment->transaction_response_status = $attributes['status'];

     
        $payment->payment_status = $this->getEaseBuzzPaymentStatus($attributes['status']);
        if($cseet==1 && $attributes['status']=='success'){
           
            $payment->payment_status=3;
        }
        $payment->reward_amount = $this->getRewardAmount($attributes['udf1']);
        $payment->net_amount = $attributes['amount'];
        $payment->payment_updated_method = Payment::UPDATE_METHOD_EASEBUZZ;
        $payment->save();

        return $payment;
    }

    public function easebuzzupdate($attributes = [])
    {
        $payment =Payment::where('order_id',$attributes['udf1'])->first();
        if ( $attributes['status'] == "success") {
            $payment->receipt_no = $this->getReceiptNumber();
        }
        $payment->transaction_id = $attributes['txnid'];
        $payment->transaction_response = json_encode($attributes);
        $payment->transaction_response_status = $attributes['status'];
        $payment->payment_status = $this->getEaseBuzzPaymentStatus($attributes['status']);
        $payment->net_amount = $attributes['amount'];
        $payment->payment_updated_method = Payment::UPDATE_METHOD_EASEBUZZ;
        $payment->save();
        return $payment;
    }

    public function apiCreate($attributes = [])
    {

        $payment = Payment::where('order_id', $attributes['order_no'])->first();

        if (!empty($payment->order_id)) {
        } else {
            $payment = new Payment;
        }


        $payment->user_id = $this->getUserID($attributes['order_no']);

        if ($attributes['order_status'] == "Shipped") {
            $payment->receipt_no = $this->getReceiptNumber();
        }

        $payment->order_id = rtrim($attributes['order_no'], '-F');
        $payment->cc_avenue_order_id = $attributes['order_no'];

        $taxes = $this->getTaxes($attributes['order_amt'], $payment->user_id, $payment->order_id);

        $payment->cgst = $taxes['cgst'];
        $payment->cgst_amount = $taxes['cgst_amount'];
        $payment->sgst = $taxes['sgst'];
        $payment->sgst_amount = $taxes['sgst_amount'];
        $payment->igst = $taxes['igst'];
        $payment->igst_amount = $taxes['igst_amount'];
        $payment->transaction_id = $attributes['reference_no'];
        $payment->transaction_response = json_encode($attributes);
        $payment->transaction_response_status = $attributes['order_status'];
        $payment->payment_status = $this->getPaymentStatus($attributes['order_status']);
        $payment->reward_amount = $this->getRewardAmount($attributes['order_no']);
        $payment->net_amount = $attributes['order_amt'];
        $payment->payment_updated_method = Payment::UPDATE_METHOD_CCAVENUE;
        $payment->save();

        return $payment;
    }
    public function apiCancel($attributes = [])
    {

        $payment = Payment::where('order_id', $attributes['id'])->first();

        if (!empty($payment->order_id)) {


            $payment->transaction_id = $attributes['transaction_id'];
            $payment->transaction_response = json_encode($attributes);
            $payment->transaction_response_status = $attributes['order_status'];
            $payment->payment_status = $this->getPaymentStatus($attributes['order_status']);

            $payment->payment_updated_method = Payment::UPDATE_METHOD_CCAVENUE;
            $payment->save();

            return $payment;
        }
    }



    public function getUserID($orderID = null)
    {
        if ($orderID) {
            $order = Order::find($orderID);

            if ($order) {
                return $order->user_id;
            }
        }

        return null;
    }
    public function cseet($orderID = null)
    {
        if ($orderID) {
            $order = Order::find($orderID);

            if ($order->is_cseet==1) {
                return $order->is_cseet;
            }
        }

        return null;
    }

    public function getReceiptNumber()
    {
        $lastReceiptNumber = Payment::where('payment_status', Payment::PAYMENT_STATUS_SUCCESS)->latest()->first()->receipt_no ?? null;

        if (!$lastReceiptNumber) {
            return 1;
        }

        return $lastReceiptNumber + 1;
    }

   
    public function getPaymentStatus($orderStatus = '')
    {
        if ($orderStatus == 'Success') {
            return Payment::PAYMENT_STATUS_SUCCESS;
        }

        return Payment::PAYMENT_STATUS_FAILURE;
    }

    public function getEaseBuzzPaymentStatus($orderStatus = '')
    {
        if ($orderStatus == 'success') {
            return Payment::PAYMENT_STATUS_SUCCESS;
        }

        return Payment::PAYMENT_STATUS_FAILURE;
    }

    public function getTaxes($amount = null, $userID = null, $orderID = null)
    {
        if (!$amount) {
            return ['cgst' => 0, 'cgst_amount' => 0, 'sgst' => 0, 'sgst_amount' => 0, 'igst' => 0, 'igst_amount' => 0];
        }

        $CGST = Setting::where('key', 'cgst')->first()->value ?? null;
        $SGST = Setting::where('key', 'sgst')->first()->value ?? null;
        $IGST = Setting::where('key', 'igst')->first()->value ?? null;

        $amountExceptCGST_SGST = ($amount * 100) / (100 + $CGST + $SGST);
        $amountExceptIGST = ($amount * 100) / (100 + $IGST);

        $CGSTAmount = (($amountExceptCGST_SGST * $CGST) / 100);
        $SGSTAmount = (($amountExceptCGST_SGST * $SGST) / 100);
        $IGSTAmount = (($amountExceptIGST * $IGST) / 100);

        $addressID = Order::find($orderID)->address_id ?? null;
        $state = null;

        if ($addressID) {
            $state = Address::find($addressID)->state ?? null;
        }

        if ($state == Payment::STATE_MAHARASHTRA) {
            $IGST = 0;
            $IGSTAmount = 0;
        } else {
            $CGST = 0;
            $SGST = 0;
            $CGSTAmount = 0;
            $SGSTAmount = 0;
        }

        return ['cgst' => $CGST, 'cgst_amount' => $CGSTAmount, 'sgst' => $SGST, 'sgst_amount' => $SGSTAmount, 'igst' => $IGST, 'igst_amount' => $IGSTAmount];
    }

    public function getRewardAmount($orderID = null)
    {
        if ($orderID) {
            $order = Order::find($orderID);

            if ($order) {
                return $order->reward_amount;
            }
        }

        return null;
    }
    public function easebuzzcreatecopy($attributes = [])
    {
        $payment = new Payment;
        $payment->user_id = $this->getUserID($attributes['udf1']);

        if ( $attributes['status'] == "success") {
            $payment->receipt_no = $this->getReceiptNumber();
        }

        $payment->order_id = $attributes['udf1'];
        $payment->ease_buzz_order_id = $attributes['udf1'];

        $taxes = $this->getTaxes($attributes['amount'], $payment->user_id, $payment->order_id);

        $payment->cgst = $taxes['cgst'];
        $payment->cgst_amount = $taxes['cgst_amount'];
        $payment->sgst = $taxes['sgst'];
        $payment->sgst_amount = $taxes['sgst_amount'];
        $payment->igst = $taxes['igst'];
        $payment->igst_amount = $taxes['igst_amount'];
        $payment->transaction_id = $attributes['txnid'];
        $payment->transaction_response = json_encode($attributes);
        $payment->transaction_response_status = $attributes['status'];
        $payment->payment_status = $this->getEaseBuzzPaymentStatus($attributes['status']);
        $payment->reward_amount = $this->getRewardAmount($attributes['udf1']);
        $payment->net_amount = $attributes['amount'];
        $payment->payment_updated_method = Payment::UPDATE_METHOD_EASEBUZZ;
        $payment->save();

        return $payment;
    }
}
