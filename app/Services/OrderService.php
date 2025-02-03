<?php

namespace App\Services;

use App\Mail\PurchaseLink;
use App\Models\Associate;
use App\Models\Cart;
use App\Models\JMoney;
use App\Models\JMoneySetting;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudyMaterialOrderLog;
use App\Models\TempCampaignPoint;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\Address;
use App\Models\CseetStudentDoc;
use App\Models\HolidayOffers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\EmailLog;

class OrderService
{
    public function store($attributes = [])
    {
        if (isset($attributes['coupon']) && isset($attributes['coupon_amount'])) {
            if ($attributes['coupon_type'] == 1) {
                $discount_amount = $attributes['coupon_amount'] / count($attributes['content_delivery']);
            } elseif ($attributes['coupon_type'] == 2) {
                $couponpackageid = explode(',', $attributes['coupon_package_id']);
                $discount_amount = $attributes['coupon_amount'] / count($couponpackageid);
            } else {
                $discount_amount = 0;
            }
      }
        if (isset($attributes['is_study_material_order'])) {

            $order = new Order();
            $order->user_id = auth('api')->id();
            $order->cgst = $attributes['cgst'];
            $order->cgst_amount = $attributes['cgst_amount'];
            $order->sgst = $attributes['sgst'];
            $order->sgst_amount = $attributes['sgst_amount'];
            $order->igst = $attributes['igst'];
            $order->igst_amount = $attributes['igst_amount'];
            $order->transaction_id = rand(0, 1000000000000);
            $order->transaction_response_status = 'Payment Initiated';
            $order->unique_key = rand(0, 100000000000);
            $order->payment_status = Order::PAYMENT_STATUS_INITIATED;
            $order->payment_mode = 1;
            $order->holiday_offer_id=$attributes['holiday_offer_id']??null;
            $package = Package::find($attributes['package_id']);
            if (isset($attributes['coupon']) && isset($attributes['coupon_amount'])) {
                $coupon = Coupon::search($attributes['coupon'])->first();


                if (isset($attributes['rakshadiscounthidden']) && $attributes['rakshadiscounthidden']>0) {
                    $totalAmount = $package->study_material_price;
                    // $totalAmount = $this->calculaterakshabandhandiscount($totalAmount);
                    $totalAmount = $totalAmount - $attributes['rakshadiscounthidden'] - $attributes['coupon_amount'];
                } else {
                    $totalAmount = $package->study_material_price - $attributes['coupon_amount'];
                }
                if ($totalAmount < 0) {
                    $totalAmount = 0;
                }
                $order->net_amount = $totalAmount;
                $order->coupon_id = $coupon->id;
                $order->coupon_code = $attributes['coupon'];
                $order->coupon_amount = $attributes['coupon_amount'];
            } else {
                if (isset($attributes['rakshadiscounthidden'])&&$attributes['rakshadiscounthidden']>0) {
                    $studymaterialraksha = $package->study_material_price ?? 0;
                    $totalAmount = $studymaterialraksha - $attributes['rakshadiscounthidden'];
                    // $totalAmount = $this->calculaterakshabandhandiscount($studymaterialraksha);
                    $order->net_amount =  $totalAmount ?? 0;
                } else {
                    $order->net_amount = $package->study_material_price ?? 0;
                }
            }
            $order->address_id = $attributes['address_id'];
            $order->holiday_offer_id = $attributes['holiday_offer_id'] ?? null;
            $order->holiday_offer_amount = $attributes['rakshadiscounthidden'] ?? null;
            $order->holiday_cashback_point=$attributes['rakshajcoin'] ?? null;
            $address = Address::find($attributes['address_id']);
            $order->name = $address->name ?? null;
            $order->country_code = $address->country_code ?? null;
            $order->phone = $address->phone ?? null;
            $order->alternate_phone = $address->alternate_phone ?? null;
            $order->city = $address->city ?? null;
            $order->state = $address->state ?? null;
            $order->pin = $address->pin ?? null;
            $order->address = $address->address ?? null;
            $order->payment_initiated_at = Carbon::now();
            $order->status = 1;

            $order->save();

            $orderItem = new OrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->package_id = $package->id;
            $orderItem->package_duration = $package->duration;
            $orderItem->user_id = $order->user_id;
            $orderItem->price = $package->study_material_price ?? null;
            $orderItem->price_type = 1;
            $orderItem->is_prebook = false;
            $orderItem->delivery_mode = 1;
            $orderItem->payment_status = 0;
            $orderItem->is_completed = 0;
            $orderItem->item_type = OrderItem::ITEM_TYPE_STUDY_MATERIAL;
            $orderItem->item_id = $package->id;
            if ($package->expiry_type == '1') {
                $orderItem->expire_at = Carbon::now()->addMonths($package->expiry_month);
            } else if ($package->expiry_type == '2') {
                $orderItem->expire_at = $package->expire_at;
            } else {
                $orderItem->expire_at = Carbon::now()->addMonths(Package::VALIDITY_IN_MONTHS);
            }
            $orderItem->save();

            return $order->load('orderItems');


        

        }

       



        DB::beginTransaction();
        $totalAmount = null;

        $pendrive_price = Setting::where('key', 'pendrive_price')->first();

        //calculate total selling price of the package
        foreach ($attributes['content_delivery'] as $id => $mode) {
            /** @var Package $package */
            $package = Package::find($id);

            if ($package) {
                $totalAmount += $package->selling_price;              
            }
        }

        //check if pendrive price exist
        if (in_array("pendrive", $attributes['content_delivery'])) {
            $is_pendrive = 1;
        } else {
            $is_pendrive = 0;
        }

        //if pendrive exist add it to total amount
        if ($is_pendrive) {
            $totalAmount = $totalAmount + $pendrive_price->value;
        }

        //If coupon exist ,reduce coupon amount from total amount
        $couponAmount = null;
        if (isset($attributes['coupon']) && isset($attributes['coupon_amount'])) {
            $coupon = Coupon::search($attributes['coupon'])->first();

           
                $totalAmount = $totalAmount - $attributes['coupon_amount'];
            
        }

        //if rakshabandhan
        if (isset($attributes['rakshadiscounthidden']) && !empty($attributes['rakshadiscounthidden'])) {
            if ($attributes['rakshadiscounthidden']>0){
               
                $totalAmount = $totalAmount - $attributes['rakshadiscounthidden'];
            } 
        }

        //If reward is applied , reduce reward amount from total amount
        //        if(isset($attributes['reward_point_id'])){
        //            if (array_key_exists('reward_point_id', $attributes)) {
        //                $reward = JMoney::find( $attributes['reward_point_id']);
        //
        //                if ($reward) {
        //                    $totalAmount = $totalAmount - $reward->points;
        //                    $reward->is_used =JMoney::USED ;
        //                    $reward->save();
        //                }
        //
        //            }
        //        }

        $rewardID = null;
        $rewardAmount = null;

        if (isset($attributes['reward_amount'])) {
            $jMoney = JMoney::find($attributes['reward_id']);

            // if ($jMoney) {
            //     if ($jMoney->points > $attributes['reward_amount']) {
            //         $jMoney->points = ($jMoney->points - $attributes['reward_amount']);
            //         $jMoney->save();
            //     }

            //     if ($jMoney->points == $attributes['reward_amount']) {
            //         $jMoney->points = 0;
            //         $jMoney->is_used = true;
            //         $jMoney->save();
            //     }
            // }


           
                $totalAmount = ($totalAmount - $attributes['reward_amount']);
            
            $rewardID = $attributes['reward_id'] ?? null;
            $rewardAmount = $attributes['reward_amount'];
        }

        if (isset($attributes['spin_wheel_reward_id'])) {

           
                $totalAmount = $totalAmount - $attributes['spin_wheel_reward_amount'];
            
        }


        //if student orders through an associate link, update associate commission
        $associateId = null;
        $commission = null;
        $branchManagerId = null;

        if (array_key_exists('student_id', $attributes)) {
            $userId = $attributes['student_id'];


            if (auth('api')->user()->role == 7) {
                $associateId = auth('api')->id();

                $associate = Associate::where('user_id', auth('api')->id())->first();
                $associateCommission = Setting::where('key', 'associate_commission')->first();
                $commission = $associate->commission ?? $associateCommission->value ?? 0;
                $commission = ($commission / 100) * $totalAmount;
            }

            if (auth('api')->user()->role == 11) {
                $branchManagerId = auth('api')->id();
            }

            if (array_key_exists('address_id', $attributes)) {
                $address = Address::find($attributes['address_id']);
            }
        } else {
            if (array_key_exists('address_id', $attributes)) {
                $address = Address::find($attributes['address_id']);
            }
            $userId = auth('api')->id();

            $student = Student::query()->where('user_id', $userId)->first();

            if ($student->associate_id) {
                $associate = Associate::where('user_id', $student->associate_id)->first();
                $associateCommission = Setting::where('key', 'associate_commission')->first();
                $commission = $associate->commission ?? $associateCommission->value ?? 0;
                $commission = ($commission / 100) * $totalAmount;

                $associateId = $student->associate_id;
            }
        }

        $paymentURL = null;
        $status = 1;

        $order = new Order();
        $order->user_id = $userId;
        $order->associate_id = $associateId;
        $order->commission = $commission;
        $order->branch_manager_id = $branchManagerId;
        $order->transaction_id = rand(0, 1000000000000);
        $order->transaction_response = null;
        $order->holiday_offer_id=$attributes['holiday_offer_id']??null;

        $order->transaction_response_status = 'Payment initiated';
        $order->payment_status = Order::PAYMENT_STATUS_INITIATED;

        if (isset($attributes['spin_wheel_reward_type']) && ($attributes['spin_wheel_reward_type'] == '5' || $attributes['spin_wheel_reward_type'] == '6' || ($attributes['spin_wheel_reward_type'] == '1' && $attributes['total_amount'] == '0'))) {
            $order->transaction_response_status = 'Success';
            $order->payment_status = Order::PAYMENT_STATUS_SUCCESS;
        }

        $order->unique_key = rand(0, 100000000000);
        $order->payment_status = Order::PAYMENT_STATUS_INITIATED;
        $order->payment_mode = 1;
        $order->cgst =  $attributes['cgst'];
        $order->cgst_amount =  $attributes['cgst_amount'];
        $order->sgst =  $attributes['sgst'];
        $order->sgst_amount =  $attributes['sgst_amount'];
        $order->igst =  $attributes['igst'];
        $order->igst_amount =  $attributes['igst_amount'];
        if(@$attributes['cseet_pkg_id']){
            $order->is_cseet=1;
            $order->status=0;

        }



        if (isset($attributes['coupon']) && isset($attributes['coupon_amount'])) {
            $order->coupon_id = $coupon->id;
            $order->coupon_code = $attributes['coupon'];
            $order->coupon_amount = $attributes['coupon_amount'];
        }
        //        if(isset($attributes['reward_point_id'])) {
        //            if ($reward) {
        //                $order->reward_amount = $reward->points;
        //            }
        //        }

        if (isset($attributes['spin_wheel_reward_id'])) {
            $order->spin_wheel_reward_id = $attributes['spin_wheel_reward_id'];
            $order->reward_amount = $attributes['spin_wheel_reward_amount'];

            if (isset($attributes['spin_wheel_reward_text'])) {
                $order->spin_wheel_reward_text = $attributes['spin_wheel_reward_text'];
            }
        }

        if ($mode != 'online') {
            $order->pendrive_price = $pendrive_price->value;
        }

        $studyMaterialsPrice = 0;

        $checkedStudyMaterials = [];

        if (isset($attributes['checked_study_materials'])) {
            $checkedStudyMaterials = json_decode($attributes['checked_study_materials']);
        }

        foreach ($checkedStudyMaterials as $checkedStudyMaterial) {
            $package = Package::find($checkedStudyMaterial);

            if ($package) {
                $studyMaterialsPrice += $package->study_material_price;
            }
        }

        $order->reward_id = $rewardID;
        $order->reward_amount = $rewardAmount;

        $totalAmount += $studyMaterialsPrice;
        //need to check
        // if (date("Y-m-d") == "2022-08-11") {
        //     // $totalAmount = $this->calculaterakshabandhandiscount($totalAmount);
        //     $totalAmount =  $totalAmount - $attributes['rakshadiscounthidden'];
        // }
        if ($totalAmount < 0) {
            $totalAmount = 0;
        }

        if ( $rewardAmount && ($totalAmount == 0)) {
            $order->transaction_response_status = 'Success';
            $order->payment_status = Order::PAYMENT_STATUS_SUCCESS;
        }

        if ($totalAmount == 0) {
            $order->transaction_response_status = 'Success';
            $order->payment_status = Order::PAYMENT_STATUS_SUCCESS;

            //If it is first purchase, update the jmoneys table
            $orderExist = Order::where('user_id', $userId)->where('payment_status', 1)->first();
            if (!$orderExist) {
                if(JMoneySetting::first()->first_purchase_point>0){
                $jMoney = new JMoney();
                $jMoney->user_id = $userId;
                $jMoney->activity = JMoney::FIRST_PURCHASE;
                $jMoney->points = JMoneySetting::first()->first_purchase_point ?? null;
                $jMoney->expire_after = JMoneySetting::first()->first_purchase_point_expiry ?? null;
                $jMoney->expire_at = Carbon::now()->addDays($jMoney->expire_after);
                $jMoney->save();
                }
            }
        }
        $order->holiday_offer_id = $attributes['holiday_offer_id'] ?? null;
        $order->holiday_offer_amount = $attributes['rakshadiscounthidden'] ?? null;
        $order->holiday_cashback_point=$attributes['rakshajcoin'] ?? null;
        $order->net_amount = $totalAmount;
        // if($attributes['holiday_offer_id']){

        //     $Jkoin=new Jmoney();
        //     $Jkoin->user_id = $userId;
        //     $Jkoin->activity =5;
        //     $holiday_offer = HolidayOffers::where('id',$attributes['holiday_offer_id'])->first();
        //     if(($holiday_offer->discount_amount/100)*$totalAmount > $holiday_offer->max_cashback){
        //         $Jkoin->points=$holiday_offer->max_cashback;
        //     }
        //     else{
        //         $Jkoin->points=($holiday_offer->discount_amount/100)*$totalAmount;
        //     }
        //     $Jkoin->expire_after =10;
        //     $Jkoin->expire_at = Carbon::now()->addDays(10);
        //     $Jkoin->save();
        // }

        $order->address_id = $attributes['address_id'] ?? null;
        $order->name = $address->name ?? null;
        $order->phone = $address->phone ?? null;
        $order->alternate_phone = $address->alternate_phone ?? null;
        $order->city = $address->city ?? null;
        $order->state = $address->state ?? null;
        $order->pin = $address->pin ?? null;
        $order->address = $address->address ?? null;
        $order->payment_initiated_at = Carbon::now();
        $order->status = $status;
        $order->payment_url = $paymentURL;
        $order->save();

        if(@$attributes['cseet_pkg_id']){

        $cseet_package=New CseetStudentDoc();
        $cseet_package->order_id=$order->id;
        $cseet_package->user_id=$order->user_id;
        $cseet_package->package_id=$attributes['cseet_pkg_id'];
        $cseet_package->filename=$attributes['proof_name'];
        $cseet_package->is_verified=0;
        $cseet_package->save();
        }

        if (isset($attributes['spin_wheel_reward_type']) && ($attributes['spin_wheel_reward_type'] == '5' || $attributes['spin_wheel_reward_type'] == '6' || ($attributes['spin_wheel_reward_type'] == '1' && $attributes['total_amount'] == '0'))) {
            $tempCampaignPoint = TempCampaignPoint::find($order->spin_wheel_reward_id);
            $tempCampaignPoint->is_used = 1;
            $tempCampaignPoint->order_id = $order->id;
            $tempCampaignPoint->save();

            Cart::where('user_id', $order->user_id)->delete();
        }


        foreach ($attributes['content_delivery'] as $id => $mode) {
            /** @var Package $package */
            $package = Package::find($id);

            if ($package) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->package_id = $package->id;
                $orderItem->user_id = $order->user_id;
                $orderItem->package_duration = $package->duration;

                if ($mode == 'online') {
                    if ($package->special_price) {
                        $orderItem->price = $price = $package->special_price;
                        $orderItem->price_type = 3;
                    } else if ($package->discounted_price) {
                        $orderItem->price = $price = $package->discounted_price;
                        $orderItem->price_type = 2;
                    } else {
                        $orderItem->price = $price = $package->price;
                        $orderItem->price_type = 1;
                    }

                    $orderItem->delivery_mode = 1;
                    if ($attributes['coupon_type'] == 1) {
                        $orderItem->discount_amount = round($discount_amount, 2);
                        $orderItem->package_discount_amount = round($price - $discount_amount, 2);
                    } elseif ($attributes['coupon_type'] == 2) {
                        if (in_array($package->id, $couponpackageid)) {
                            $orderItem->discount_amount = round($discount_amount, 2);
                            $orderItem->package_discount_amount = round($price - $discount_amount, 2);
                        }
                    }
                } else {

                    if ($package->special_price) {
                        $orderItem->price = $price = $package->special_price;
                        $orderItem->price_type = 3;
                    } else if ($package->discounted_price) {
                        $orderItem->price = $price = $package->discounted_price;
                        $orderItem->price_type = 2;
                    } else {
                        $orderItem->price = $price = $package->price;
                        $orderItem->price_type = 1;
                    }
                    if ($attributes['coupon_type'] == 1) {
                        $discount_package_price = $price - $discount_amount;
                    } elseif ($attributes['coupon_type'] == 2) {
                        if (in_array($package->id, $couponpackageid)) {
                            $discount_package_price = $price - $discount_amount;
                        }
                    }

                    if ($mode == 'pendrive') {
                        $orderItem->delivery_mode = 2;
                        $orderItem->price_type = 4;
                    }
                    if ($mode == 'g-drive') {
                        $orderItem->delivery_mode = 3;
                    }
                }

                if ($package->is_prebook && !$package->is_prebook_package_launched) {
                    $orderItem->is_prebook = 1;
                    $orderItem->booking_amount = $package->booking_amount;
                    $orderItem->balance_amount = $package->prebook_price - $package->booking_amount;
                }

                if (isset($attributes['spin_wheel_reward_type']) && ($attributes['spin_wheel_reward_type'] == '5' || $attributes['spin_wheel_reward_type'] == '6' || ($attributes['spin_wheel_reward_type'] == '1' && $attributes['total_amount'] == '0'))) {
                    $orderItem->payment_status = OrderItem::PAYMENT_STATUS_FULLY_PAID;
                }

                if ($rewardAmount && ($totalAmount == 0)) {
                    $orderItem->payment_status = OrderItem::PAYMENT_STATUS_FULLY_PAID;
                }

                if ($totalAmount == 0) {
                    $orderItem->payment_status = OrderItem::PAYMENT_STATUS_FULLY_PAID;
                }

                $orderItem->item_type = OrderItem::ITEM_TYPE_PACKAGE;
                $orderItem->item_id = $package->id;
                if ($package->expiry_type == '1') {
                    $orderItem->expire_at = Carbon::now()->addMonths($package->expiry_month);
                } else if ($package->expiry_type == '2') {
                    $orderItem->expire_at = $package->expire_at;
                } else {
                    $orderItem->expire_at = Carbon::now()->addMonths(Package::VALIDITY_IN_MONTHS);
                }
                $orderItem->save();

                if ($order->payment_status == 1) {
                    $cart = Cart::where('package_id', $package->id)->where('user_id', auth('api')->id())->first();
                    if ($cart) {
                        $cart->delete();
                    }
                }
            }
        }

        if (array_key_exists('associate_payment_type', $attributes)) {
            if ($attributes['associate_payment_type'] == 'link') {
                $update_order = Order::find($order->id);

                if ($update_order) {
                    //                $update_order->payment_status = Order::PAYMENT_STATUS_INITIATED;
                    //                $update_order->status = Order::STATUS_PENDING;
                    $update_order->payment_url = env('WEB_URL') . '/purchase-now/' . $order->id;
                    $update_order->payment_url_expired_at = Carbon::now()->addDays(7);
                    //                $update_order->commission = null;
                    $update_order->save();

                    $user_details = Student::where('user_id', '=', $order->user_id)->first();

                    $order_details = OrderItem::where('order_id', $order->id)->pluck('package_id');
                    $packages = Package::whereIn('id', $order_details)->get();

                    $user_details['order_id'] = $order->id;
                    $user_details['payment_url'] = $update_order->payment_url;
                    $user_details['net_amount'] = $order->net_amount;
                    $user_details['packages'] = $packages;

                    try {
                        Mail::send(new PurchaseLink($user_details));
                        $email_log = new EmailLog();
                        $email_log->email_to = $user_details->email;
                        $email_log->email_from = env('MAIL_FROM_ADDRESS');
                        $email_log->content = "JKSHAH ONLINE - PURCHASE LINK";
                        $email_log->save();

                    } catch (\Exception $exception) {
                        info($exception->getMessage(), ['exception' => $exception]);
                    }
                }
            }
        }

        foreach ($checkedStudyMaterials as $checkedStudyMaterial) {
            $package = Package::find($checkedStudyMaterial);

            $orderItem = new OrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->package_id = $checkedStudyMaterial;
            $orderItem->user_id = $order->user_id;
            $orderItem->price = $package->study_material_price ?? null;
            $orderItem->package_duration = $package->duration;
            $orderItem->price_type = 1;
            $orderItem->is_prebook = false;
            $orderItem->booking_amount = null;
            $orderItem->balance_amount = null;
            $orderItem->delivery_mode = 1;
            $orderItem->payment_status = 0;
            $orderItem->is_completed = 0;
            $orderItem->item_type = OrderItem::ITEM_TYPE_STUDY_MATERIAL;
            $orderItem->item_id = $checkedStudyMaterial;
            if ($package->expiry_type == '1') {
                $orderItem->expire_at = Carbon::now()->addMonths($package->expiry_month);
            } else if ($package->expiry_type == '2') {
                $orderItem->expire_at = $package->expire_at;
            } else {
                $orderItem->expire_at = Carbon::now()->addMonths(Package::VALIDITY_IN_MONTHS);
            }
            $orderItem->save();
        }

        DB::commit();

        return $order->load('orderItems');
    }

    // function calculaterakshabandhandiscount($total)
    // {
    //     //rakshabandhan starts

    //     $totalrakshabandanprice = round(($total * 2.5) / 100);
    //     // if ($totalrakshabandanprice >= 250) {
    //     //     $rakshajcoin = 250;
    //     // } else {
    //     //     $rakshajcoin = $totalrakshabandanprice;
    //     // }
    //     return  $total = $total - $totalrakshabandanprice;
    //     //ends
    // }
}
