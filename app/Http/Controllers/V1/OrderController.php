<?php

namespace App\Http\Controllers\V1;

use App\Mail\OrderPlaced;
use App\Mail\OrderStatusFailed;
use App\Mail\PurchaseMail;
use App\Mail\PurchaseMailAdmin;
use App\Models\Associate;
use App\Models\Cart;
use App\Models\JMoney;
use App\Models\JMoneySetting;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\PaymentOrderItem;
use App\Models\PaymentTransaction;
use App\Models\Professor;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudyMaterialOrderLog;
use App\Models\TempCampaignPoint;
use App\Models\User;
use App\Models\ProfessorPayout;
use App\Models\HolidayOffers;
use App\Models\Coupon;
use App\Notifications\OrderCreated;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\OrderService;
use App\Models\Order;
use App\Models\PaymentTransactionHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Mockery\Exception;
use App\Services\PaymentService;
use App\Services\ProfessorRevenueService;
use App\Models\EmailLog;
use App\Mail\JMoneyMail;
use App\Models\Payment;

class OrderController extends Controller
{
    /** @var OrderService */
    var $orderService;

    /** @var PaymentService $paymentService */
    var $paymentService;

    /** @var ProfessorRevenueService $professorRevenueService */
    var $professorRevenueService;

    /**
     * OrderController constructor.
     * @param OrderService $service
     * @param PaymentService $paymentService
     * @param ProfessorRevenueService $professorRevenueService
     */
    public function __construct(OrderService $service, PaymentService $paymentService, ProfessorRevenueService $professorRevenueService)
    {
        $this->orderService = $service;
        $this->paymentService = $paymentService;
        $this->professorRevenueService = $professorRevenueService;
    }

    public function index()
    {
    }

    public function store(Request $request)
    {


        $store_order = $this->orderService->store($request->input());
        //nth transaction decision logic
        $payment_counter = Setting::where('key', 'payment_counter')->first();
        // $denominator_counter = Setting::where('key', 'denominator_counter')->first();
        // $flag=($payment_counter->value+1) % $denominator_counter->value;
        // if( $flag==0){
        //     $payment_gateway_type='CCAVENUE';
        // }else{
        //      $payment_gateway_type='EASEBUZZ';
        // }

        $payment_gateway_type = 'EASEBUZZ';

        if ($request->input('spin_wheel_reward_type') && ($request->input('spin_wheel_reward_type') == '5' || $request->input('spin_wheel_reward_type') == '6' || ($request->input('spin_wheel_reward_type') == '1' && $request->input('total_amount') == '0'))) {
            $payment = $this->paymentService->create([
                'order_id' => $store_order['id'],
                'order_status' => 'Success',
                'amount' => '0',
                'tracking_id' => null,
            ]);

            $orderItems = OrderItem::where('order_id', $store_order['id'])->get();

            foreach ($orderItems as $orderItem) {
                $paymentOrderItem = new PaymentOrderItem;
                $paymentOrderItem->payment_id = $payment['id'];
                $paymentOrderItem->order_item_id = $orderItem->id;
                $paymentOrderItem->is_balance_payment = false;
                $paymentOrderItem->save();
            }
        }

        if ($store_order['reward_amount'] == ($store_order['net_amount'] == 0)) {
            // $payment = $this->paymentService->create([
            //     'order_id' => $store_order['id'],
            //     'order_status' => 'Success',
            //     'amount' => '0',
            //     'tracking_id' => null,
            // ]);

            // $orderItems = OrderItem::where('order_id', $store_order['id'])->get();

            // foreach ($orderItems as $orderItem) {
            //     $paymentOrderItem = new PaymentOrderItem;
            //     $paymentOrderItem->payment_id = $payment['id'];
            //     $paymentOrderItem->order_item_id = $orderItem->id;
            //     $paymentOrderItem->is_balance_payment = false;
            //     $paymentOrderItem->save();
            // }
            //   $jMoney = JMoney::find($store_order['reward_id']);

            //   if ($jMoney) {
            //       if ($jMoney->points < $store_order['reward_amount']) {
            //           $jMoney->points = 0;
            //           $jMoney->is_used = true;
            //           $jMoney->save();
            //       }

            //       if ($jMoney->points > $store_order['reward_amount']) {
            //           $jMoney->points = ($jMoney->points - $store_order['reward_amount']);
            //           $jMoney->save();
            //       }

            //       if ($jMoney->points == $store_order['reward_amount']) {
            //           $jMoney->points = 0;
            //           $jMoney->is_used = true;
            //           $jMoney->save();
            //       }
            // }
            $jMoney = new JMoney();
            $jMoney->points = $store_order['reward_amount'];
            $jMoney->user_id = $store_order['user_id'];
            $jMoney->activity = JMoney::PURCHASE;
            $jMoney->is_used = true;
            $jMoney->transaction_type = 2;
            $jMoney->order_id = $store_order['id'];
            $jMoney->save();
        }

        if ($store_order['net_amount'] == 0) {
            $payment = $this->paymentService->create([
                'order_id' => $store_order['id'],
                'order_status' => 'Success',
                'amount' => '0',
                'tracking_id' => null,
            ]);

            $orderItems = OrderItem::where('order_id', $store_order['id'])->get();

            foreach ($orderItems as $orderItem) {
                $paymentOrderItem = new PaymentOrderItem;
                $paymentOrderItem->payment_id = $payment['id'];
                $paymentOrderItem->order_item_id = $orderItem->id;
                $paymentOrderItem->is_balance_payment = false;
                $paymentOrderItem->save();
            }
            // Mail to Admin and student
            $order = Order::find($store_order['id']);
            $order_items = OrderItem::where('order_id', $order->id)->pluck('package_id');
            $packages = Package::with('subject', 'course', 'level', 'chapter', 'language', 'packagetype')->whereIn('id', $order_items)->get();
            $order_items_details = OrderItem::select('package_id', 'discount_amount', 'package_discount_amount', 'price')->where('order_id', $order->id)->get()->toArray();
            $study_material = OrderItem::where('order_id', $order->id)->where('item_type', 2)->pluck('package_id');


            $order_details = Student::where('user_id', '=', $order->user_id)->first();
            if (count($study_material) > 0) {

                $study_material_price = Package::select(DB::raw('sum(study_material_price) as total'))->whereIn('id', $study_material)->first();
                $order_details['stdy_material_parice'] = $study_material_price->total;
                $order_details['item_type'] = 2;
            } else {
                $order_details['item_type'] = 1;
            }
            //Add jkoin
            if ($order->holiday_cashback_point > 0) {
                $jMoney = new JMoney();
                $jMoney->user_id = auth('api')->id();
                $jMoney->activity = JMoney::CASHBACK;
                $jMoney->points = $order->holiday_cashback_point;
                $jMoney->expire_after = 365;
                $jMoney->expire_at = Carbon::now()->addDays($jMoney->expire_after);
                $jMoney->order_id = $order->id;
                $jMoney->holiday_offer_id = $order->holiday_offer_id;
                $jMoney->save();

                try {
                    $holiday = HolidayOffers::where('id', '=', $order->holiday_offer_id)->first();
                    $attributes['email'] = $order_details['email'];
                    $attributes['j_amount'] = $order->holiday_cashback_point;
                    $attributes['holiday_offername'] = $holiday->name;
                    $attributes['holiday_jkoin'] = 1;
                    Mail::send(new JMoneyMail($attributes));

                    $email_log = new EmailLog();
                    $email_log->email_to = $attributes['email'];
                    $email_log->email_from = env('MAIL_FROM_ADDRESS');
                    $email_log->content = "Your J-Koins Gift card is here";
                    $email_log->save();
                } catch (\Exception $exception) {
                    info($exception->getMessage(), ['exception' => $exception]);
                }
            }

            //endjkoin
            $order_details['order_id'] = $order->id;
            $order_details['net_amount'] = $order['net_amount'];
            $order_details['packages'] = $packages;
            $order_details['coupon_amount'] = $order['coupon_amount'];
            $order_details['coupon_code'] = $order['coupon_code'];
            if (@$store_order['coupon_id']) {
                $couponcode = Coupon::where('id', $store_order['coupon_id'])->first();
                $order_details['coupon_code'] = $couponcode->name;
            }
            $order_details['pendrive_price'] = $order['pendrive_price'];
            $order_details['reward_amount'] = $order['reward_amount'];
            if ($order['cgst']) {
                $order_details['cgst'] = $order['cgst'];
                $order_details['cgst_amount'] = $order['cgst_amount'];
            }
            if ($order['sgst']) {
                $order_details['sgst'] = $order['sgst'];
                $order_details['sgst_amount'] = $order['sgst_amount'];
            }
            if ($order['igst']) {
                $order_details['igst'] = $order['igst'];
                $order_details['igst_amount'] = $order['igst_amount'];
            }
            try {
                $admin_mail = Setting::where('key', 'admin_email')->first();
                //$admin_mail = Setting::where('key', 'email_bcc')->first();
                $bcc = $special_bcc = '';
                $bcc_ids = $special_bcc_ids = $email_bcc = [];
                $bcc_setting = Setting::where('key', 'email_bcc')->first();
                $bcc = $bcc_setting->value;
                if (!empty($bcc_setting->value)) {
                    $bcc_ids = explode(",", $bcc);
                }
                $special_bcc_settings = Setting::where('key', 'special_bcc')->first();
                $special_bcc = $special_bcc_settings->value;
                if (!empty($special_bcc) && !empty($bcc_ids)) {
                    $special_bcc_ids = explode(",", $special_bcc);
                    $email_bcc = array_merge($bcc_ids, $special_bcc_ids);
                } else {
                    $email_bcc = $bcc_ids;
                }
                $order_details['admin_email'] = $admin_mail->value;
                $order_details['email_bcc'] = $email_bcc;
                $order_details['email_bcc_user'] = $bcc_ids;
                $order_details['address'] = $order['address'];
                $order_details['phone'] = $order['phone'];
                $order_details['location'] = $order['city'];
                $order_details['order_items_details'] = $order_items_details;

                Mail::send(new PurchaseMailAdmin($order_details));

                $email_log = new EmailLog();
                $email_log->email_to = $admin_mail->value;
                $email_log->email_from = env('MAIL_FROM_ADDRESS');
                $email_log->content = "Confirmation about  course purchase - #" . $order_details['order_id'];
                $email_log->save();

                Mail::send(new PurchaseMail($order_details));

                $email_log = new EmailLog();
                $email_log->email_to = $order_details['email'];
                $email_log->email_from = env('MAIL_FROM_ADDRESS');
                $email_log->content = "Congrats! Here’s the confirmation about your course purchase";
                $email_log->save();
            } catch (\Exception $exception) {
                info($exception->getMessage());
            }
        }

        $user = User::find(auth('api')->id());

        return $this->jsonResponse('Order', ['store_order' => $store_order, 'user' => $user, 'payment_gateway_type' => $payment_gateway_type, 'payment_counter' => $payment_counter->value]);
    }

    public function fetchStudentOrders(Request $request)
    {
        $order_details = Order::find($request->order_id);
        $user = Student::where('user_id', '=', $order_details->user_id)->first();

        return $this->jsonResponse('Order Details', ['order_details' => $order_details, 'user' => $user]);
    }

    public function update(Request $request)
    {
        DB::beginTransaction();
        $response_parameters = $request->all();

        //        $paymentTransaction = PaymentTransaction::create([
        //            'transaction_id' => $response_parameters['transaction_id'],
        //            'response' => $response_parameters['transaction_response']
        //        ]);

        //        return $paymentTransaction;

        $update_order = Order::find($response_parameters['id']);
        $update_order->transaction_id = $response_parameters['transaction_id'];
        $update_order->transaction_response = $response_parameters['transaction_response'];
        $update_order->transaction_response_status = $response_parameters['order_status'];

        $user = User::find($update_order->user_id);

        if ($response_parameters['order_status'] == "Success") {

            $update_order->payment_status = 1;
            $associate = Associate::where('user_id', $update_order->associate_id)->first();
            if ($associate) {
                $associateCommission = Setting::where('key', 'associate_commission')->first();
                $commission = $associate->commission ?? $associateCommission->value ?? 0;
                $commission = ($commission / 100) * $update_order->net_amount;
                $update_order->commission = $commission;
            } else {
                $order = Order::find($response_parameters['id']);
                $order_items = OrderItem::where('order_id', $order->id)->pluck('package_id');
                $packages = Package::with('subject', 'course', 'level', 'chapter', 'language', 'packagetype')->whereIn('id', $order_items)->get();
                $order_items_details = OrderItem::select('package_id', 'discount_amount', 'package_discount_amount', 'price')->where('order_id', $order->id)->get()->toArray();
                $study_material = OrderItem::where('order_id', $order->id)->where('item_type', 2)->pluck('package_id');


                $order_details = Student::where('user_id', '=', $order->user_id)->first();
                if (count($study_material) > 0) {

                    $study_material_price = Package::select(DB::raw('sum(study_material_price) as total'))->whereIn('id', $study_material)->first();
                    $order_details['stdy_material_parice'] = $study_material_price->total;
                    $order_details['item_type'] = 2;
                } else {
                    $order_details['item_type'] = 1;
                }

                $order_details['order_id'] = $order->id;
                $order_details['net_amount'] = $order['net_amount'];
                $order_details['packages'] = $packages;
                $order_details['coupon_amount'] = $order['coupon_amount'];
                $order_details['coupon_code'] = $order['coupon_code'];
                if (@$update_order->coupon_id) {
                    $couponcode = Coupon::where('id', $update_order->coupon_id)->first();
                    $order_details['coupon_code'] = $couponcode->name;
                }
                if (@$update_order->holiday_offer_id) {
                    $holidayoffer = HolidayOffers::where('id', $update_order->holiday_offer_id)->first();
                    $order_details['holiday_offername'] = $holidayoffer->name;
                }
                $order_details['holiday_offer_amount'] = $order['holiday_offer_amount'];
                $order_details['pendrive_price'] = $order['pendrive_price'];
                $order_details['reward_amount'] = $order['reward_amount'];
                if ($order['cgst']) {
                    $order_details['cgst'] = $order['cgst'];
                    $order_details['cgst_amount'] = $order['cgst_amount'];
                }
                if ($order['sgst']) {
                    $order_details['sgst'] = $order['sgst'];
                    $order_details['sgst_amount'] = $order['sgst_amount'];
                }
                if ($order['igst']) {
                    $order_details['igst'] = $order['igst'];
                    $order_details['igst_amount'] = $order['igst_amount'];
                }
                try {

                    $notification = new OrderCreated($user);
                    Notification::route('sms', $user->phone)->notify($notification);
                    $admin_mail = Setting::where('key', 'admin_email')->first();
                    $bcc = $special_bcc = '';
                    $bcc_ids = $special_bcc_ids = $email_bcc = [];
                    $bcc_setting = Setting::where('key', 'email_bcc')->first();
                    $bcc = $bcc_setting->value;
                    if (!empty($bcc_setting->value)) {
                        $bcc_ids = explode(",", $bcc);
                    }
                    $special_bcc_settings = Setting::where('key', 'special_bcc')->first();
                    $special_bcc = $special_bcc_settings->value;
                    if (!empty($special_bcc) && !empty($bcc_ids)) {
                        $special_bcc_ids = explode(",", $special_bcc);
                        $email_bcc = array_merge($bcc_ids, $special_bcc_ids);
                    } else {
                        $email_bcc = $bcc_ids;
                    }
                    $order_details['admin_email'] = $admin_mail->value;
                    $order_details['address'] = $order['address'];
                    $order_details['phone'] = $order['phone'];
                    $order_details['location'] = $order['city'];
                    $order_details['order_items_details'] = $order_items_details;
                    $order_details['email_bcc'] = $email_bcc;
                    $order_details['email_bcc_user'] = $bcc_ids;
                    Mail::send(new PurchaseMailAdmin($order_details));

                    $email_log = new EmailLog();
                    $email_log->email_to = $admin_mail->value;
                    $email_log->email_from = env('MAIL_FROM_ADDRESS');
                    $email_log->content = "Confirmation about  course purchase - #" . $order_details['order_id'];
                    $email_log->save();

                    Mail::send(new PurchaseMail($order_details));

                    $email_log = new EmailLog();
                    $email_log->email_to = $order_details['email'];
                    $email_log->email_from = env('MAIL_FROM_ADDRESS');
                    $email_log->content = "Congrats! Here’s the confirmation about your course purchase";
                    $email_log->save();
                } catch (\Exception $exception) {
                    info($exception->getMessage());
                }
            }
            if (@$update_order->coupon_id) {

                $couponcode = Coupon::where('id', $update_order->coupon_id)->first();


                $update_order->coupon_code = $couponcode->name;
            }

            $order_items = OrderItem::where('order_id', $response_parameters['id'])->get('package_id');

            $package_price = $response_parameters['amount'];
            foreach ($order_items as $order_item) {
                $package = Package::find($order_item->package_id);
                $professors  = $package['professors'];
                foreach ($professors as $professor) {
                    if ($package['professor_revenue']) {
                        $professor_revenue = $package['professor_revenue'];
                    } elseif ($professor['professor_revenue']) {
                        $professor_revenue = $professor['professor_revenue'];
                    } else {
                        $global_settings = Setting::where('key', 'professor_revenue')->first();
                        $professor_revenue = $global_settings->value;
                    }
                    $professor_revenue_percentage = $professor_revenue / 100;
                    $total_professors = count($professors);

                    $professor_payout = ProfessorPayout::updateOrCreate([
                        'professor_id' => $professor['id'],
                        'order_id' => $response_parameters['id'],
                        'package_id' => $package->id,
                        'amount' => ($professor_revenue_percentage / $total_professors) * $package_price,
                        'percentage' => $professor_revenue,
                    ]);
                    $professor_payout->save();
                }
            }

            $orderItems = OrderItem::where('order_id', $response_parameters['id'])->get();

            foreach ($orderItems as $orderItem) {
                if ($orderItem->is_prebook && !$package->is_prebook_package_launched) {
                    $orderItem->payment_status = OrderItem::PAYMENT_STATUS_PARTIALLY_PAID;
                } else {
                    $orderItem->payment_status = OrderItem::PAYMENT_STATUS_FULLY_PAID;
                }

                if ($orderItem->item_type == OrderItem::ITEM_TYPE_STUDY_MATERIAL) {
                    $orderItem->order_status = OrderItem::STATUS_ORDER_PLACED;

                    $studyMaterialOrderLog = new StudyMaterialOrderLog();
                    $studyMaterialOrderLog->order_item_id = $orderItem->id;
                    $studyMaterialOrderLog->status = StudyMaterialOrderLog::STATUS_ORDER_PLACED;
                    $studyMaterialOrderLog->save();

                    $package = Package::find($orderItem->package_id);
                    $user = User::with('address')->find($orderItem->user_id);

                    try {
                        Mail::send(new OrderPlaced([
                            'package_id' => $package->id,
                            'package_name' => $package->name,
                            'order_id' => $orderItem->order_id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'address' => optional($user->address)->address ?? '',
                            'area' => optional($user->address)->area ?? '',
                            'landmark' => optional($user->address)->landmark ?? '',
                            'city' => optional($user->address)->city ?? '',
                            'state' => optional($user->address)->state ?? '',
                            'pin' => optional($user->address)->pin ?? '',
                        ]));
                        $email_log = new EmailLog();
                        $email_log->email_to = env('DISPATCHER_MAIL');
                        $email_log->email_from = env('MAIL_FROM_ADDRESS');
                        $email_log->content = "JKSHAH ONLINE - ORDER PLACED";
                        $email_log->save();
                    } catch (\Exception $exception) {
                        info($exception->getMessage());
                    }
                }

                $orderItem->save();
            }

            if ($update_order->spin_wheel_reward_id) {
                $tempCampaignPoint = TempCampaignPoint::find($update_order->spin_wheel_reward_id);
                $tempCampaignPoint->is_used = 1;
                $tempCampaignPoint->order_id = $update_order->id;
                $tempCampaignPoint->save();
            }

            if ($update_order->reward_amount) {
                // $jMoney = JMoney::find($update_order->reward_id);

                // if ($jMoney) {
                //     if ($jMoney->points < $update_order->reward_amount) {
                //         $jMoney->points = 0;
                //         $jMoney->is_used = true;
                //         $jMoney->save();
                //     }

                //     if ($jMoney->points > $update_order->reward_amount) {
                //         $jMoney->points = ($jMoney->points - $update_order->reward_amount);
                //         $jMoney->save();
                //     }

                //     if ($jMoney->points == $update_order->reward_amount) {
                //         $jMoney->points = 0;
                //         $jMoney->is_used = true;
                //         $jMoney->save();
                //     }
                // }
                $jMoney = new JMoney();
                $jMoney->points = $update_order->reward_amount;
                $jMoney->user_id = $update_order->user_id;
                $jMoney->activity = JMoney::PURCHASE;
                $jMoney->is_used = true;
                $jMoney->transaction_type = 2;
                $jMoney->order_id = $update_order->id;
                $jMoney->save();
            }

            if ($update_order->holiday_cashback_point > 0) {
                $jMoney = new JMoney();
                $jMoney->user_id = auth('api')->id();
                $jMoney->activity = JMoney::CASHBACK;
                $jMoney->points = $update_order->holiday_cashback_point;
                $jMoney->expire_after = 365;
                $jMoney->expire_at = Carbon::now()->addDays($jMoney->expire_after);
                $jMoney->order_id = $update_order->id;
                $jMoney->holiday_offer_id = $update_order->holiday_offer_id;
                $jMoney->save();

                try {
                    $holiday = HolidayOffers::where('id', '=', $update_order->holiday_offer_id)->first();
                    $order2 = Order::find($response_parameters['id']);
                    $order_details2 = Student::where('user_id', '=', $order2->user_id)->first();
                    $attributes['email'] = $order_details2['email'];
                    $attributes['j_amount'] = $update_order->holiday_cashback_point;
                    $attributes['holiday_offername'] = $holiday->name;
                    $attributes['holiday_jkoin'] = 1;
                    Mail::send(new JMoneyMail($attributes));

                    $email_log = new EmailLog();
                    $email_log->email_to = $attributes['email'];
                    $email_log->email_from = env('MAIL_FROM_ADDRESS');
                    $email_log->content = "Your J-Koins Gift card is here";
                    $email_log->save();
                } catch (\Exception $exception) {
                    info($exception->getMessage(), ['exception' => $exception]);
                }
            }

            //If it is first purchase, update the jmoneys table
            $orderExist = Order::where('user_id', $update_order->user_id)->where('payment_status', 1)->first();
            if (!$orderExist) {
                if (JMoneySetting::first()->first_purchase_point > 0) {
                    $jMoney = new JMoney();
                    $jMoney->user_id = $update_order->user_id;
                    $jMoney->activity = JMoney::FIRST_PURCHASE;
                    $jMoney->order_id = $update_order->id;
                    $jMoney->points = JMoneySetting::first()->first_purchase_point ?? null;
                    $jMoney->expire_after = JMoneySetting::first()->first_purchase_point_expiry ?? null;
                    $jMoney->expire_at = Carbon::now()->addDays($jMoney->expire_after);
                    $jMoney->save();
                }
            }



            Cart::where('user_id', $update_order->user_id)->delete();
            Cart::where('user_id', $update_order->associate_id)->delete();
            Cart::where('user_id', $update_order->branch_manager_id)->delete();
        } elseif ($response_parameters['order_status'] == "Failure") {
            $update_order->payment_status = Order::PAYMENT_STATUS_FAILED;
            $update_order->commission = null;
        } elseif ($response_parameters['order_status'] == "Aborted") {
            $update_order->payment_status = Order::PAYMENT_STATUS_ABORTED;
            $update_order->commission = null;
        } elseif ($response_parameters['order_status'] == "Invalid") {
            $update_order->payment_status = Order::PAYMENT_STATUS_INVALID;
            $update_order->commission = null;
        } else {
            $update_order->payment_status = Order::PAYMENT_STATUS_INITIATED;
            $update_order->commission = null;
        }


        //        $update_order->updated_by = $user->id;
        $update_order->updated_method = Order::UPDATE_METHOD_CCAVENUE;
        $update_order->updated_ip_address = request()->ip();

        $update_order->update();

        DB::commit();

        $transactionResponse = $request->input('transaction_response');
        $transactionResponse = (json_decode($transactionResponse, true));
        $payment = $this->paymentService->create($transactionResponse);
        $orderItems = OrderItem::where('order_id', $response_parameters['id'])->get();

        foreach ($orderItems as $orderItem) {
            $paymentOrderItem = new PaymentOrderItem;
            $paymentOrderItem->payment_id = $payment['id'];
            $paymentOrderItem->order_item_id = $orderItem->id;
            $paymentOrderItem->is_balance_payment = false;
            $paymentOrderItem->save();

            if ($orderItem->payment_status == OrderItem::PAYMENT_STATUS_PARTIALLY_PAID || $orderItem->payment_status == OrderItem::PAYMENT_STATUS_FULLY_PAID) {
                try {
                    $netAmount = null;

                    if (!$orderItem->is_prebook) {
                        $netAmount = $orderItem->price;
                    }

                    if ($orderItem->is_prebook && $orderItem->payment_status == OrderItem::PAYMENT_STATUS_PARTIALLY_PAID) {
                        $netAmount = $orderItem->booking_amount;
                    }

                    if ($orderItem->is_prebook && $orderItem->payment_status == OrderItem::PAYMENT_STATUS_FULLY_PAID) {
                        $netAmount = $orderItem->balance_amount;
                    }

                    $this->professorRevenueService->store([
                        'package_id' => $orderItem->package_id,
                        'net_amount' => $netAmount,
                        'invoice_id' => $payment->receipt_no,
                        'invoice_date' => $payment->created_at
                    ]);
                } catch (Exception $exception) {
                    info('PROFESSOR REVENUE SERVICE EXCEPTION: ' . $exception->getMessage());
                }
            }
        }

        // $holiday_offer_id= Order::where('id',$response_parameters['id'])->first()->holiday_offer_id;
        $holiday_offer_id = Order::where('id', $response_parameters['id'])->first()->holiday_offer_amount;
        return $this->jsonResponse('Order Status', ['order_status' => $response_parameters['order_status'], 'order' => $update_order, 'holiday_offer_id' => $holiday_offer_id]);
    }

    public function easeBuzzUpdate(Request $request)
    {
        try {


            DB::beginTransaction();
            $response_parameters = $request->all();
            $update_order = Order::find($response_parameters['id']);
            if (!empty($update_order->id)) {


                $update_order->transaction_id = $response_parameters['transaction_id'];
                $update_order->transaction_response = $response_parameters['transaction_response'];
                $update_order->transaction_response_status = $response_parameters['order_status'];


                if ($update_order->payment_status == 1) {
                    info('order status success found ');
                    $holiday_offer_id = Order::where('id', $response_parameters['id'])->first()->holiday_offer_amount;
                    return $this->jsonResponse('Order Status', ['order_status' => $response_parameters['order_status'], 'order' => $update_order, 'holiday_offer_id' => $holiday_offer_id]);
                } else {

                    $user = User::find($update_order->user_id);

                    if ($response_parameters['order_status'] == 'success') {

                        $update_order->payment_status = 1;
                        $associate = Associate::where('user_id', $update_order->associate_id)->first();
                        if ($associate) {
                            $associateCommission = Setting::where('key', 'associate_commission')->first();
                            $commission = $associate->commission ?? $associateCommission->value ?? 0;
                            $commission = ($commission / 100) * $update_order->net_amount;
                            $update_order->commission = $commission;
                        } else {
                            $order = Order::find($response_parameters['id']);
                            $order_items = OrderItem::where('order_id', $order->id)->pluck('package_id');
                            $packages = Package::with('subject', 'course', 'level', 'chapter', 'language', 'packagetype')->whereIn('id', $order_items)->get();
                            $order_items_details = OrderItem::select('package_id', 'discount_amount', 'package_discount_amount', 'price')->where('order_id', $order->id)->get()->toArray();
                            $study_material = OrderItem::where('order_id', $order->id)->where('item_type', 2)->pluck('package_id');


                            $order_details = Student::where('user_id', '=', $order->user_id)->first();
                            if (count($study_material) > 0) {

                                $study_material_price = Package::select(DB::raw('sum(study_material_price) as total'))->whereIn('id', $study_material)->first();
                                $order_details['stdy_material_parice'] = $study_material_price->total;
                                $order_details['item_type'] = 2;
                            } else {
                                $order_details['item_type'] = 1;
                            }

                            $order_details['order_id'] = $order->id;
                            $order_details['net_amount'] = $order['net_amount'];
                            $order_details['packages'] = $packages;
                            $order_details['coupon_amount'] = $order['coupon_amount'];
                            $order_details['coupon_code'] = $order['coupon_code'];
                            if (@$update_order->coupon_id) {
                                $couponcode = Coupon::where('id', $update_order->coupon_id)->first();
                                $order_details['coupon_code'] = $couponcode->name;
                            }
                            if (@$update_order->holiday_offer_id) {
                                $holidayoffer = HolidayOffers::where('id', $update_order->holiday_offer_id)->first();
                                $order_details['holiday_offername'] = $holidayoffer->name;
                            }
                            $order_details['holiday_offer_amount'] = $order['holiday_offer_amount'];
                            $order_details['pendrive_price'] = $order['pendrive_price'];
                            $order_details['reward_amount'] = $order['reward_amount'];
                            if ($order['cgst']) {
                                $order_details['cgst'] = $order['cgst'];
                                $order_details['cgst_amount'] = $order['cgst_amount'];
                            }
                            if ($order['sgst']) {
                                $order_details['sgst'] = $order['sgst'];
                                $order_details['sgst_amount'] = $order['sgst_amount'];
                            }
                            if ($order['igst']) {
                                $order_details['igst'] = $order['igst'];
                                $order_details['igst_amount'] = $order['igst_amount'];
                            }
                            try {

                                $notification = new OrderCreated($user);
                                Notification::route('sms', $user->phone)->notify($notification);
                                $admin_mail = Setting::where('key', 'admin_email')->first();
                                //   $admin_mail = Setting::where('key', 'email_bcc')->first();
                                $bcc = $special_bcc = '';
                                $bcc_ids = $special_bcc_ids = $email_bcc = [];
                                $bcc_setting = Setting::where('key', 'email_bcc')->first();
                                $bcc = $bcc_setting->value;
                                if (!empty($bcc_setting->value)) {
                                    $bcc_ids = explode(",", $bcc);
                                }
                                $special_bcc_settings = Setting::where('key', 'special_bcc')->first();
                                $special_bcc = $special_bcc_settings->value;
                                if (!empty($special_bcc) && !empty($bcc_ids)) {
                                    $special_bcc_ids = explode(",", $special_bcc);
                                    $email_bcc = array_merge($bcc_ids, $special_bcc_ids);
                                } else {
                                    $email_bcc = $bcc_ids;
                                }
                                $order_details['admin_email'] = $admin_mail->value;
                                $order_details['address'] = $order['address'];
                                $order_details['phone'] = $order['phone'];
                                $order_details['location'] = $order['city'];
                                $order_details['order_items_details'] = $order_items_details;
                                $order_details['email_bcc'] = $email_bcc;
                                $order_details['email_bcc_user'] = $bcc_ids;
                                Mail::send(new PurchaseMailAdmin($order_details));

                                $email_log = new EmailLog();
                                $email_log->email_to = $admin_mail->value;
                                $email_log->email_from = env('MAIL_FROM_ADDRESS');
                                $email_log->content = "Confirmation about  course purchase - #" . $order_details['order_id'];
                                $email_log->save();

                                Mail::send(new PurchaseMail($order_details));
                                $email_log = new EmailLog();
                                $email_log->email_to = $order_details['email'];
                                $email_log->email_from = env('MAIL_FROM_ADDRESS');
                                $email_log->content = "Congrats! Here’s the confirmation about your course purchase";
                                $email_log->save();
                            } catch (\Exception $exception) {
                                info($exception->getMessage());
                            }
                        }
                        if (@$update_order->coupon_id) {

                            $couponcode = Coupon::where('id', $update_order->coupon_id)->first();


                            $update_order->coupon_code = $couponcode->name;
                        }

                        $order_items = OrderItem::where('order_id', $response_parameters['id'])->get('package_id');

                        $package_price = $response_parameters['amount'];
                        foreach ($order_items as $order_item) {
                            $package = Package::find($order_item->package_id);
                            $professors  = $package['professors'];
                            foreach ($professors as $professor) {
                                if ($package['professor_revenue']) {
                                    $professor_revenue = $package['professor_revenue'];
                                } elseif ($professor['professor_revenue']) {
                                    $professor_revenue = $professor['professor_revenue'];
                                } else {
                                    $global_settings = Setting::where('key', 'professor_revenue')->first();
                                    $professor_revenue = $global_settings->value;
                                }
                                $professor_revenue_percentage = $professor_revenue / 100;
                                $total_professors = count($professors);

                                $professor_payout = ProfessorPayout::updateOrCreate([
                                    'professor_id' => $professor['id'],
                                    'order_id' => $response_parameters['id'],
                                    'package_id' => $package->id,
                                    'amount' => ($professor_revenue_percentage / $total_professors) * $package_price,
                                    'percentage' => $professor_revenue,
                                ]);
                                $professor_payout->save();
                            }
                        }

                        $orderItems = OrderItem::where('order_id', $response_parameters['id'])->get();

                        foreach ($orderItems as $orderItem) {
                            if ($orderItem->is_prebook && !$package->is_prebook_package_launched) {
                                $orderItem->payment_status = OrderItem::PAYMENT_STATUS_PARTIALLY_PAID;
                            } else {
                                $orderItem->payment_status = OrderItem::PAYMENT_STATUS_FULLY_PAID;
                            }

                            if ($orderItem->item_type == OrderItem::ITEM_TYPE_STUDY_MATERIAL) {
                                $orderItem->order_status = OrderItem::STATUS_ORDER_PLACED;

                                $studyMaterialOrderLog = new StudyMaterialOrderLog();
                                $studyMaterialOrderLog->order_item_id = $orderItem->id;
                                $studyMaterialOrderLog->status = StudyMaterialOrderLog::STATUS_ORDER_PLACED;
                                $studyMaterialOrderLog->save();

                                $package = Package::find($orderItem->package_id);
                                $user = User::with('address')->find($orderItem->user_id);

                                try {
                                    Mail::send(new OrderPlaced([
                                        'package_id' => $package->id,
                                        'package_name' => $package->name,
                                        'order_id' => $orderItem->order_id,
                                        'name' => $user->name,
                                        'email' => $user->email,
                                        'phone' => $user->phone,
                                        'address' => optional($user->address)->address ?? '',
                                        'area' => optional($user->address)->area ?? '',
                                        'landmark' => optional($user->address)->landmark ?? '',
                                        'city' => optional($user->address)->city ?? '',
                                        'state' => optional($user->address)->state ?? '',
                                        'pin' => optional($user->address)->pin ?? '',
                                    ]));
                                    $email_log = new EmailLog();
                                    $email_log->email_to = env('DISPATCHER_MAIL');
                                    $email_log->email_from = env('MAIL_FROM_ADDRESS');
                                    $email_log->content = "JKSHAH ONLINE - ORDER PLACED";
                                    $email_log->save();
                                } catch (\Exception $exception) {
                                    info($exception->getMessage());
                                }
                            }

                            $orderItem->save();
                        }

                        if ($update_order->spin_wheel_reward_id) {
                            $tempCampaignPoint = TempCampaignPoint::find($update_order->spin_wheel_reward_id);
                            $tempCampaignPoint->is_used = 1;
                            $tempCampaignPoint->order_id = $update_order->id;
                            $tempCampaignPoint->save();
                        }

                        if ($update_order->reward_amount) {

                            $jMoney = new JMoney();
                            $jMoney->points = $update_order->reward_amount;
                            $jMoney->user_id = $update_order->user_id;
                            $jMoney->activity = JMoney::PURCHASE;
                            $jMoney->is_used = true;
                            $jMoney->transaction_type = 2;
                            $jMoney->order_id = $update_order->id;
                            $jMoney->save();
                        }

                        if ($update_order->holiday_cashback_point > 0) {
                            $jMoney = new JMoney();
                            // $jMoney->user_id = auth('api')->id();
                            $jMoney->user_id = $update_order->user_id;
                            $jMoney->activity = JMoney::CASHBACK;
                            $jMoney->points = $update_order->holiday_cashback_point;
                            $jMoney->expire_after = 365;
                            $jMoney->expire_at = Carbon::now()->addDays($jMoney->expire_after);
                            $jMoney->order_id = $update_order->id;
                            $jMoney->holiday_offer_id = $update_order->holiday_offer_id;
                            $jMoney->save();

                            try {
                                $holiday = HolidayOffers::where('id', '=', $update_order->holiday_offer_id)->first();
                                $order2 = Order::find($response_parameters['id']);
                                $order_details2 = Student::where('user_id', '=', $order2->user_id)->first();
                                $attributes['email'] = $order_details2['email'];
                                $attributes['j_amount'] = $update_order->holiday_cashback_point;
                                $attributes['holiday_offername'] = $holiday->name;
                                $attributes['holiday_jkoin'] = 1;
                                Mail::send(new JMoneyMail($attributes));

                                $email_log = new EmailLog();
                                $email_log->email_to = $attributes['email'];
                                $email_log->email_from = env('MAIL_FROM_ADDRESS');
                                $email_log->content = "Your J-Koins Gift card is here";
                                $email_log->save();
                            } catch (\Exception $exception) {
                                info($exception->getMessage(), ['exception' => $exception]);
                            }
                        }

                        //If it is first purchase, update the jmoneys table
                        $orderExist = Order::where('user_id', $update_order->user_id)->where('payment_status', 1)->first();
                        if (!$orderExist) {
                            if (JMoneySetting::first()->first_purchase_point > 0) {
                                $jMoney = new JMoney();
                                $jMoney->user_id = $update_order->user_id;
                                $jMoney->activity = JMoney::FIRST_PURCHASE;
                                $jMoney->order_id = $update_order->id;
                                $jMoney->points = JMoneySetting::first()->first_purchase_point ?? null;
                                $jMoney->expire_after = JMoneySetting::first()->first_purchase_point_expiry ?? null;
                                $jMoney->expire_at = Carbon::now()->addDays($jMoney->expire_after);
                                $jMoney->save();
                            }
                        }
                        Cart::where('user_id', $update_order->user_id)->delete();
                        Cart::where('user_id', $update_order->associate_id)->delete();
                        Cart::where('user_id', $update_order->branch_manager_id)->delete();
                    } elseif ($response_parameters['order_status'] == "userCancelled") {
                        $update_order->payment_status = Order::PAYMENT_STATUS_ABORTED;
                        $update_order->commission = null;
                    } elseif ($response_parameters['order_status'] == "Failure") {
                        $update_order->payment_status = Order::PAYMENT_STATUS_FAILED;
                        $update_order->commission = null;
                    } elseif ($response_parameters['order_status'] == "Aborted") {
                        $update_order->payment_status = Order::PAYMENT_STATUS_ABORTED;
                        $update_order->commission = null;
                    } elseif ($response_parameters['order_status'] == "Invalid") {
                        $update_order->payment_status = Order::PAYMENT_STATUS_INVALID;
                        $update_order->commission = null;
                    } else {
                        $update_order->payment_status = Order::PAYMENT_STATUS_INITIATED;
                        $update_order->commission = null;
                    }


                    //        $update_order->updated_by = $user->id;
                    $update_order->updated_method = Order::UPDATE_METHOD_EASEBUZZ;
                    $update_order->updated_ip_address = request()->ip();

                    $update_order->update();

                    DB::commit();

                    $transactionResponse = $request->input('transaction_response');
                    $paymentFlag = Payment::where('order_id', $transactionResponse['udf1'])->first();
                    if (empty($paymentFlag->id)) {
                        $payment = $this->paymentService->easebuzzcreate($transactionResponse);
                        $orderItems = OrderItem::where('order_id', $response_parameters['id'])->get();

                        foreach ($orderItems as $orderItem) {
                            $paymentOrderItem = new PaymentOrderItem;
                            $paymentOrderItem->payment_id = $payment['id'];
                            $paymentOrderItem->order_item_id = $orderItem->id;
                            $paymentOrderItem->is_balance_payment = false;
                            $paymentOrderItem->save();

                            if ($orderItem->payment_status == OrderItem::PAYMENT_STATUS_PARTIALLY_PAID || $orderItem->payment_status == OrderItem::PAYMENT_STATUS_FULLY_PAID) {
                                try {
                                    $netAmount = null;

                                    if (!$orderItem->is_prebook) {
                                        $netAmount = $orderItem->price;
                                    }

                                    if ($orderItem->is_prebook && $orderItem->payment_status == OrderItem::PAYMENT_STATUS_PARTIALLY_PAID) {
                                        $netAmount = $orderItem->booking_amount;
                                    }

                                    if ($orderItem->is_prebook && $orderItem->payment_status == OrderItem::PAYMENT_STATUS_FULLY_PAID) {
                                        $netAmount = $orderItem->balance_amount;
                                    }

                                    $this->professorRevenueService->store([
                                        'package_id' => $orderItem->package_id,
                                        'net_amount' => $netAmount,
                                        'invoice_id' => $payment->receipt_no,
                                        'invoice_date' => $payment->created_at
                                    ]);
                                } catch (Exception $exception) {
                                    info('PROFESSOR REVENUE SERVICE EXCEPTION: ' . $exception->getMessage());
                                }
                            }
                        }
                    } else {
                        $payment = $this->paymentService->easebuzzupdate($transactionResponse);
                    }

                    info('order status updated');
                    $holiday_offer_id = Order::where('id', $response_parameters['id'])->first()->holiday_offer_amount;
                    return $this->jsonResponse('Order Status', ['order_status' => $response_parameters['order_status'], 'order' => $update_order, 'holiday_offer_id' => $holiday_offer_id]);
                }
            } else {
                info('order id not found');
                return $this->jsonResponse('Order Status', ['order_status' => 'failures']);
            }
        } catch (\Exception $exception) {
            info($exception->getMessage());
            return $this->jsonResponse('Order Status' . $exception->getMessage(), ['order_status' => 'failures']);
        }
    }
    public function getTax()
    {

        $cgst = Setting::where('key', 'cgst')->first('value');
        $sgst = Setting::where('key', 'sgst')->first('value');
        $igst = Setting::where('key', 'igst')->first('value');

        return $this->jsonResponse('Tax', ['cgst' => $cgst, 'igst' => $igst, 'sgst' => $sgst]);
    }

    public function rewardPoints()
    {
        $rewards_total =  JMoney::where('user_id', Auth::id())
            ->where('is_used', JMoney::NOT_USED)
            ->where('transaction_type', 1)
            ->where('expire_at', '>=', Carbon::now())
            ->sum('points');
        $rewards_used =  JMoney::where('user_id', Auth::id())
            ->where('is_used', JMoney::USED)
            ->where('transaction_type', 2)
            ->sum('points');
        $rewards = $rewards_total - $rewards_used;
        // return  $this->jsonResponse('Rewards', $rewards);

        $spinWheelRewards = TempCampaignPoint::query()
            ->where('value_type', '!=', 4)
            ->where('is_used', 0)
            ->whereDate('expire_at', '>=', Carbon::today())
            ->whereHas('campaignRegistration', function ($query) {
                $query->where('user_id', auth('api')->id());
            })->get();

        return  $this->jsonResponse('Rewards', ['rewards' => $rewards, 'rewards_total' => $rewards_total, 'rewards_used' => $rewards_used, 'spinWheelRewards' => $spinWheelRewards]);
    }

    public function updatePaymentInitiatedAt($id)
    {
        $order = Order::query()->find($id);
        $order->payment_initiated_at = Carbon::now();
        $order->save();
    }

    //for status api
    public function apiOrdersStatus(Request $request)
    {
        DB::beginTransaction();
        $response_parameters = $request->all();
        $update_order = Order::find($response_parameters['id']);
        $update_order->transaction_id = $response_parameters['transaction_id'];
        $update_order->transaction_response = $response_parameters['transaction_response'];
        $update_order->transaction_response_status = $response_parameters['order_status'];

        $user = User::find($update_order->user_id);

        if ($response_parameters['order_status'] == "Shipped") {
            $update_order->payment_status = 1;
            $associate = Associate::where('user_id', $update_order->associate_id)->first();
            if ($associate) {
                $associateCommission = Setting::where('key', 'associate_commission')->first();
                $commission = $associate->commission ?? $associateCommission->value ?? 0;
                $commission = ($commission / 100) * $update_order->net_amount;
                $update_order->commission = $commission;
            } else {
                $order = Order::find($response_parameters['id']);
                $order_items = OrderItem::where('order_id', $order->id)->pluck('package_id');
                $packages = Package::with('subject', 'course', 'level', 'chapter', 'language')->whereIn('id', $order_items)->get();
                $order_details = Student::where('user_id', '=', $order->user_id)->first();
                $order_details['order_id'] = $order->id;
                $order_details['net_amount'] = $order->net_amount;
                $order_details['packages'] = $packages;
                if ($order['cgst']) {
                    $order_details['cgst'] = $order['cgst'];
                    $order_details['cgst_amount'] = $order['cgst_amount'];
                }
                if ($order['sgst']) {
                    $order_details['sgst'] = $order['sgst'];
                    $order_details['sgst_amount'] = $order['sgst_amount'];
                }
                if ($order['igst']) {
                    $order_details['igst'] = $order['igst'];
                    $order_details['igst_amount'] = $order['igst_amount'];
                }
                try {
                    $notification = new OrderCreated($user);
                    Notification::route('sms', $user->phone)->notify($notification);
                    $admin_mail = Setting::where('key', 'email_bcc')->first();
                    $order_details['admin_email'] = $admin_mail->value;

                    $bcc = $special_bcc = '';
                    $bcc_ids = $special_bcc_ids = $email_bcc = [];
                    $bcc_setting = Setting::where('key', 'email_bcc')->first();
                    $bcc = $bcc_setting->value;
                    if (!empty($bcc_setting->value)) {
                        $bcc_ids = explode(",", $bcc);
                    }
                    $special_bcc_settings = Setting::where('key', 'special_bcc')->first();
                    $special_bcc = $special_bcc_settings->value;
                    if (!empty($special_bcc) && !empty($bcc_ids)) {
                        $special_bcc_ids = explode(",", $special_bcc);
                        $email_bcc = array_merge($bcc_ids, $special_bcc_ids);
                    } else {
                        $email_bcc = $bcc_ids;
                    }
                    $order_details['admin_email'] = $admin_mail->value;
                    $order_details['address'] = $order['address'];
                    $order_details['phone'] = $order['phone'];
                    $order_details['email_bcc'] = $email_bcc;
                    $order_details['email_bcc_user'] = $bcc_ids;

                    Mail::send(new PurchaseMail($order_details));
                    $email_log = new EmailLog();
                    $email_log->email_to = $order_details['email'];
                    $email_log->email_from = env('MAIL_FROM_ADDRESS');
                    $email_log->content = "Congrats! Here’s the confirmation about your course purchase";
                    $email_log->save();

                    Mail::send(new PurchaseMail($order_details));
                    // $admin_mail = Setting::where('key', 'admin_email')->first();

                    // Mail::send(new PurchaseMailAdmin($order_details));
                } catch (\Exception $exception) {
                    //                    info($exception->getMessage());
                }
            }

            $order_items = OrderItem::where('order_id', $response_parameters['id'])->get('package_id');

            $package_price = $response_parameters['amount'];
            foreach ($order_items as $order_item) {
                $package = Package::find($order_item->package_id);
                $professors  = $package['professors'];
                foreach ($professors as $professor) {
                    if ($package['professor_revenue']) {
                        $professor_revenue = $package['professor_revenue'];
                    } elseif ($professor['professor_revenue']) {
                        $professor_revenue = $professor['professor_revenue'];
                    } else {
                        $global_settings = Setting::where('key', 'professor_revenue')->first();
                        $professor_revenue = $global_settings->value;
                    }
                    $professor_revenue_percentage = $professor_revenue / 100;
                    $total_professors = count($professors);

                    $professor_payout = ProfessorPayout::updateOrCreate([
                        'professor_id' => $professor['id'],
                        'order_id' => $response_parameters['id'],
                        'package_id' => $package->id,
                        'amount' => ($professor_revenue_percentage / $total_professors) * $package_price,
                        'percentage' => $professor_revenue,
                    ]);
                    $professor_payout->save();
                }
            }

            $orderItems = OrderItem::where('order_id', $response_parameters['id'])->get();

            foreach ($orderItems as $orderItem) {
                if ($orderItem->is_prebook && !$package->is_prebook_package_launched) {
                    $orderItem->payment_status = OrderItem::PAYMENT_STATUS_PARTIALLY_PAID;
                } else {
                    $orderItem->payment_status = OrderItem::PAYMENT_STATUS_FULLY_PAID;
                }

                if ($orderItem->item_type == OrderItem::ITEM_TYPE_STUDY_MATERIAL) {
                    $orderItem->order_status = OrderItem::STATUS_ORDER_PLACED;

                    $studyMaterialOrderLog = new StudyMaterialOrderLog();
                    $studyMaterialOrderLog->order_item_id = $orderItem->id;
                    $studyMaterialOrderLog->status = StudyMaterialOrderLog::STATUS_ORDER_PLACED;
                    $studyMaterialOrderLog->save();

                    $package = Package::find($orderItem->package_id);
                    $user = User::with('address')->find($orderItem->user_id);

                    try {
                        Mail::send(new OrderPlaced([
                            'package_id' => $package->id,
                            'package_name' => $package->name,
                            'order_id' => $orderItem->order_id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'address' => optional($user->address)->address ?? '',
                            'area' => optional($user->address)->area ?? '',
                            'landmark' => optional($user->address)->landmark ?? '',
                            'city' => optional($user->address)->city ?? '',
                            'state' => optional($user->address)->state ?? '',
                            'pin' => optional($user->address)->pin ?? '',
                        ]));
                        $email_log = new EmailLog();
                        $email_log->email_to = env('DISPATCHER_MAIL');
                        $email_log->email_from = env('MAIL_FROM_ADDRESS');
                        $email_log->content = "JKSHAH ONLINE - ORDER PLACED";
                        $email_log->save();
                    } catch (\Exception $exception) {
                        //                        info($exception->getMessage());
                    }
                }
                if($update_order->is_cseet==1){
                    $orderItem->payment_status=0;

                }

                $orderItem->save();
            }

            if ($update_order->spin_wheel_reward_id) {
                $tempCampaignPoint = TempCampaignPoint::find($update_order->spin_wheel_reward_id);
                $tempCampaignPoint->is_used = 1;
                $tempCampaignPoint->order_id = $update_order->id;
                $tempCampaignPoint->save();
            }

            if ($update_order->reward_amount) {
                // $jMoney = JMoney::find($update_order->reward_id);

                // if ($jMoney) {
                //     if ($jMoney->points < $update_order->reward_amount) {
                //         $jMoney->points = 0;
                //         $jMoney->is_used = true;
                //         $jMoney->save();
                //     }
                // }

                $jMoney = new JMoney();
                $jMoney->points = $update_order->reward_amount;
                $jMoney->user_id = $update_order->user_id;
                $jMoney->activity = JMoney::PURCHASE;
                $jMoney->is_used = true;
                $jMoney->transaction_type = 2;
                $jMoney->order_id = $update_order->id;
                $jMoney->save();
            }

            //If it is first purchase, update the jmoneys table
            $orderExist = Order::where('user_id', $update_order->user_id)->where('payment_status', 1)->first();
            if (!$orderExist) {
                if (JMoneySetting::first()->first_purchase_point > 0) {
                    $jMoney = new JMoney();
                    $jMoney->user_id = $update_order->user_id;
                    $jMoney->order_id = $update_order->id;
                    $jMoney->activity = JMoney::FIRST_PURCHASE;
                    $jMoney->points = JMoneySetting::first()->first_purchase_point ?? null;
                    $jMoney->expire_after = JMoneySetting::first()->first_purchase_point_expiry ?? null;
                    $jMoney->expire_at = Carbon::now()->addDays($jMoney->expire_after);
                    $jMoney->save();
                }
            }

            Cart::where('user_id', $update_order->user_id)->delete();
            Cart::where('user_id', $update_order->associate_id)->delete();
            Cart::where('user_id', $update_order->branch_manager_id)->delete();
        } elseif ($response_parameters['order_status'] == "Awaited") {
            $update_order->payment_status = Order::PAYMENT_STATUS_INITIATED;
            $update_order->commission = null;
        } elseif ($response_parameters['order_status'] == "Initiated") {
            $update_order->payment_status = Order::PAYMENT_STATUS_INITIATED;
            $update_order->commission = null;
        } else {
            $update_order->payment_status = Order::PAYMENT_STATUS_FAILED;
            $update_order->commission = null;

            try {
                Mail::send(new OrderStatusFailed([
                    'package_id' => "",
                    'package_name' => "",
                    'order_id' => $response_parameters['id'],
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => optional($user->address)->address ?? '',
                    'area' => optional($user->address)->area ?? '',
                    'landmark' => optional($user->address)->landmark ?? '',
                    'city' => optional($user->address)->city ?? '',
                    'state' => optional($user->address)->state ?? '',
                    'pin' => optional($user->address)->pin ?? '',
                ]));

                $email_log = new EmailLog();
                $email_log->email_to = $user->email;
                $email_log->email_from = env('MAIL_FROM_ADDRESS');
                $email_log->content = "JKSHAH ONLINE - ORDER FAILED";
                $email_log->save();
            } catch (\Exception $exception) {
                //                info($exception->getMessage());
            }
        }

        $update_order->updated_method = Order::UPDATE_METHOD_CCAVENUE;
        $update_order->updated_ip_address = request()->ip();

        $update_order->update();

        DB::commit();

        $transactionResponse = $request->input('transaction_response');

        $transactionResponse = (json_decode($transactionResponse, true));
        $payment = $this->paymentService->apiCreate($transactionResponse);

        $orderItems = OrderItem::where('order_id', $response_parameters['id'])->get();
        $finalresponse = 3;
        foreach ($orderItems as $orderItem) {

            $paymentOrderItem = PaymentOrderItem::where('order_item_id', $orderItem->id)->first();
            if (!empty($paymentOrderItem->order_item_id)) {
            } else {
                $paymentOrderItem = new PaymentOrderItem;
            }

            $paymentOrderItem->payment_id = $payment['id'];
            $paymentOrderItem->order_item_id = $orderItem->id;
            $paymentOrderItem->is_balance_payment = false;
            $paymentOrderItem->save();

            if ($orderItem->payment_status == OrderItem::PAYMENT_STATUS_PARTIALLY_PAID || $orderItem->payment_status == OrderItem::PAYMENT_STATUS_FULLY_PAID) {
                try {
                    $netAmount = null;

                    if (!$orderItem->is_prebook) {
                        $netAmount = $orderItem->price;
                    }

                    if ($orderItem->is_prebook && $orderItem->payment_status == OrderItem::PAYMENT_STATUS_PARTIALLY_PAID) {
                        $netAmount = $orderItem->booking_amount;
                    }

                    if ($orderItem->is_prebook && $orderItem->payment_status == OrderItem::PAYMENT_STATUS_FULLY_PAID) {
                        $netAmount = $orderItem->balance_amount;
                    }

                    $this->professorRevenueService->apiStore([
                        'package_id' => $orderItem->package_id,
                        'net_amount' => $netAmount,
                        'invoice_id' => $payment->receipt_no,
                        'invoice_date' => $payment->created_at
                    ]);
                    $finalresponse = 1;
                } catch (Exception $exception) {
                    //                    info('PROFESSOR REVENUE SERVICE EXCEPTION: ' . $exception->getMessage());
                    //                    $finalresponse = $exception->getMessage();
                }
            } else {
                $finalresponse = 2;
            }
        }
        return $this->jsonResponse('Order Status', ['order_status' => $finalresponse]);
    }

    //for cancel api
    public function apiCancelOrders(Request $request)
    {
        DB::beginTransaction();
        $response_parameters = $request->all();
        $update_order = Order::find($response_parameters['id']);
        $update_order->transaction_id = $response_parameters['transaction_id'];
        $update_order->transaction_response = $response_parameters['transaction_response'];
        $update_order->transaction_response_status = $response_parameters['order_status'];

        $user = User::find($update_order->user_id);

        $update_order->payment_status = Order::PAYMENT_STATUS_FAILED;
        $update_order->commission = null;

        try {
            $test = Mail::send(new OrderStatusFailed([
                'package_id' => "",
                'package_name' => "",
                'order_id' => $response_parameters['id'],
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => optional($user->address)->address ?? '',
                'area' => optional($user->address)->area ?? '',
                'landmark' => optional($user->address)->landmark ?? '',
                'city' => optional($user->address)->city ?? '',
                'state' => optional($user->address)->state ?? '',
                'pin' => optional($user->address)->pin ?? '',
            ]));
            $email_log = new EmailLog();
            $email_log->email_to = $data->email_id;
            $email_log->email_from = env('MAIL_FROM_ADDRESS');
            $email_log->content = "JKSHAH ONLINE - Thane Vaibhav Registration";
            $email_log->save();
        } catch (\Exception $exception) {
            //            info($exception->getMessage());
            $test = $exception->getMessage();
        }


        $update_order->updated_method = Order::UPDATE_METHOD_CCAVENUE;
        $update_order->updated_ip_address = request()->ip();

        $update_order->update();

        DB::commit();


        $payment = $this->paymentService->apiCancel($response_parameters);

        $finalresponse = 1;

        return $this->jsonResponse('Order Status', ['order_status' => $finalresponse]);
    }
    public function get_last_success_orders()
    {
        $order_details = Order::with('student', 'payment', 'orderItems.package', 'student.country', 'student.state')
            ->where('transaction_response_status', 'Success')
            ->where('payment_status', 1)
            ->latest()
            ->take(20)->get();

        return $this->jsonResponse('Orders', $order_details);
    }

    public function addJmoneyHolidayOffer(Request $request)
    {
        $jMoney = new JMoney();
        $jMoney->user_id = auth('api')->id();
        $jMoney->activity = JMoney::CASHBACK;
        $jMoney->points = $request->rakshajcoin;
        $jMoney->expire_after = 365;
        $jMoney->expire_at = Carbon::now()->addDays($jMoney->expire_after);
        //$jMoney->holiday_offer_id=1;
        $jMoney->holiday_offer_id = $request->holiday_offer_id;
        $jMoney->save();
        if ($request->rakshajcoin > 0) {
            $holiday_offers = HolidayOffers::find($request->holiday_offer_id);
            $user = User::find(auth('api')->id());
            $attributes['email'] = $user->email;
            $attributes['j_amount'] = $request->rakshajcoin;
            $attributes['holiday_offername'] = $holiday_offers->name;
            $attributes['holiday_jkoin'] = 1;
            try {
                Mail::send(new JMoneyMail($attributes));

                $email_log = new EmailLog();
                $email_log->email_to = $attributes['email'];
                $email_log->email_from = env('MAIL_FROM_ADDRESS');
                $email_log->content = "Your J-Koins Gift card is here";
                $email_log->save();
            } catch (\Exception $exception) {
                info($exception->getMessage(), ['exception' => $exception]);
            }
        }
    }
    public function getJmoneyHolidayOffer()
    {

        $jMoney = Jmoney::where('activity', JMoney::CASHBACK)->where('holiday_offer_id', '!=', NULL)->where('user_id', auth('api')->id())->where('is_used', 0)->orderBy('id', 'desc')->first();
        if (!empty($jMoney->points)) {
            $points = $jMoney->points;
        } else {
            $points = 0;
        }
        return $this->jsonResponse('jMoney', ['points' => $points]);
    }
    public function deleteJmoneyHolidayOffer()
    {

        Jmoney::where('user_id', auth('api')->id())->where('is_used', 0)->orderBy('id', 'desc')->take(1)->delete();
    }

    public function paymentTransactionHistory(Request $request)
    {

        $paymenttransactionhistory = new PaymentTransactionHistory;
        $paymenttransactionhistory->order_id = $request->order_id;
        $paymenttransactionhistory->nth_transaction_counter = $request->nth_transaction_counter + 1;
        $paymenttransactionhistory->gateway_type = $request->gateway_type;
        $paymenttransactionhistory->save();

        Setting::where('key', 'payment_counter')->update(['value' => $request->nth_transaction_counter + 1]);

        if ($request->gateway_type == 'EASEBUZZ') {
            Order::where('id', $request->order_id)->update(['updated_method' => Order::UPDATE_METHOD_EASEBUZZ]);
        }

        return $this->jsonResponse('Payment Transaction History', ['paymenttransactionhistory' => $paymenttransactionhistory->id]);
    }

    public function easeBuzzUpdatecopy(Request $request)
    {
        DB::beginTransaction();
        $response_parameters = $request->all();

        //        $paymentTransaction = PaymentTransaction::create([
        //            'transaction_id' => $response_parameters['transaction_id'],
        //            'response' => $response_parameters['transaction_response']
        //        ]);

        //        return $paymentTransaction;

        $update_order = Order::find($response_parameters['id']);
        $update_order->transaction_id = $response_parameters['transaction_id'];
        $update_order->transaction_response = $response_parameters['transaction_response'];
        $update_order->transaction_response_status = $response_parameters['order_status'];

        $user = User::find($update_order->user_id);

        if ($response_parameters['order_status'] == 'success') {

            $update_order->payment_status = 1;
            $associate = Associate::where('user_id', $update_order->associate_id)->first();
            if ($associate) {
                $associateCommission = Setting::where('key', 'associate_commission')->first();
                $commission = $associate->commission ?? $associateCommission->value ?? 0;
                $commission = ($commission / 100) * $update_order->net_amount;
                $update_order->commission = $commission;
            } else {
                $order = Order::find($response_parameters['id']);
                $order_items = OrderItem::where('order_id', $order->id)->pluck('package_id');
                $packages = Package::with('subject', 'course', 'level', 'chapter', 'language', 'packagetype')->whereIn('id', $order_items)->get();
                $order_items_details = OrderItem::select('package_id', 'discount_amount', 'package_discount_amount', 'price')->where('order_id', $order->id)->get()->toArray();
                $study_material = OrderItem::where('order_id', $order->id)->where('item_type', 2)->pluck('package_id');


                $order_details = Student::where('user_id', '=', $order->user_id)->first();
                if (count($study_material) > 0) {

                    $study_material_price = Package::select(DB::raw('sum(study_material_price) as total'))->whereIn('id', $study_material)->first();
                    $order_details['stdy_material_parice'] = $study_material_price->total;
                    $order_details['item_type'] = 2;
                } else {
                    $order_details['item_type'] = 1;
                }

                $order_details['order_id'] = $order->id;
                $order_details['net_amount'] = $order['net_amount'];
                $order_details['packages'] = $packages;
                $order_details['coupon_amount'] = $order['coupon_amount'];
                $order_details['coupon_code'] = $order['coupon_code'];
                if (@$update_order->coupon_id) {
                    $couponcode = Coupon::where('id', $update_order->coupon_id)->first();
                    $order_details['coupon_code'] = $couponcode->name;
                }
                if (@$update_order->holiday_offer_id) {
                    $holidayoffer = HolidayOffers::where('id', $update_order->holiday_offer_id)->first();
                    $order_details['holiday_offername'] = $holidayoffer->name;
                }
                $order_details['holiday_offer_amount'] = $order['holiday_offer_amount'];
                $order_details['pendrive_price'] = $order['pendrive_price'];
                $order_details['reward_amount'] = $order['reward_amount'];
                if ($order['cgst']) {
                    $order_details['cgst'] = $order['cgst'];
                    $order_details['cgst_amount'] = $order['cgst_amount'];
                }
                if ($order['sgst']) {
                    $order_details['sgst'] = $order['sgst'];
                    $order_details['sgst_amount'] = $order['sgst_amount'];
                }
                if ($order['igst']) {
                    $order_details['igst'] = $order['igst'];
                    $order_details['igst_amount'] = $order['igst_amount'];
                }
                try {

                    $notification = new OrderCreated($user);
                    Notification::route('sms', $user->phone)->notify($notification);
                    $admin_mail = Setting::where('key', 'admin_email')->first();
                    //   $admin_mail = Setting::where('key', 'email_bcc')->first();
                    $bcc = $special_bcc = '';
                    $bcc_ids = $special_bcc_ids = $email_bcc = [];
                    $bcc_setting = Setting::where('key', 'email_bcc')->first();
                    $bcc = $bcc_setting->value;
                    if (!empty($bcc_setting->value)) {
                        $bcc_ids = explode(",", $bcc);
                    }
                    $special_bcc_settings = Setting::where('key', 'special_bcc')->first();
                    $special_bcc = $special_bcc_settings->value;
                    if (!empty($special_bcc) && !empty($bcc_ids)) {
                        $special_bcc_ids = explode(",", $special_bcc);
                        $email_bcc = array_merge($bcc_ids, $special_bcc_ids);
                    } else {
                        $email_bcc = $bcc_ids;
                    }
                    $order_details['admin_email'] = $admin_mail->value;
                    $order_details['address'] = $order['address'];
                    $order_details['phone'] = $order['phone'];
                    $order_details['location'] = $order['city'];
                    $order_details['order_items_details'] = $order_items_details;
                    $order_details['email_bcc'] = $email_bcc;
                    $order_details['email_bcc_user'] = $bcc_ids;
                    Mail::send(new PurchaseMailAdmin($order_details));

                    $email_log = new EmailLog();
                    $email_log->email_to = $admin_mail->value;
                    $email_log->email_from = env('MAIL_FROM_ADDRESS');
                    $email_log->content = "Confirmation about  course purchase - #" . $order_details['order_id'];
                    $email_log->save();

                    Mail::send(new PurchaseMail($order_details));
                    $email_log = new EmailLog();
                    $email_log->email_to = $order_details['email'];
                    $email_log->email_from = env('MAIL_FROM_ADDRESS');
                    $email_log->content = "Congrats! Here’s the confirmation about your course purchase";
                    $email_log->save();
                } catch (\Exception $exception) {
                    info($exception->getMessage());
                }
            }
            if (@$update_order->coupon_id) {

                $couponcode = Coupon::where('id', $update_order->coupon_id)->first();


                $update_order->coupon_code = $couponcode->name;
            }

            $order_items = OrderItem::where('order_id', $response_parameters['id'])->get('package_id');

            $package_price = $response_parameters['amount'];
            foreach ($order_items as $order_item) {
                $package = Package::find($order_item->package_id);
                $professors  = $package['professors'];
                foreach ($professors as $professor) {
                    if ($package['professor_revenue']) {
                        $professor_revenue = $package['professor_revenue'];
                    } elseif ($professor['professor_revenue']) {
                        $professor_revenue = $professor['professor_revenue'];
                    } else {
                        $global_settings = Setting::where('key', 'professor_revenue')->first();
                        $professor_revenue = $global_settings->value;
                    }
                    $professor_revenue_percentage = $professor_revenue / 100;
                    $total_professors = count($professors);

                    $professor_payout = ProfessorPayout::updateOrCreate([
                        'professor_id' => $professor['id'],
                        'order_id' => $response_parameters['id'],
                        'package_id' => $package->id,
                        'amount' => ($professor_revenue_percentage / $total_professors) * $package_price,
                        'percentage' => $professor_revenue,
                    ]);
                    $professor_payout->save();
                }
            }

            $orderItems = OrderItem::where('order_id', $response_parameters['id'])->get();

            foreach ($orderItems as $orderItem) {
                if ($orderItem->is_prebook && !$package->is_prebook_package_launched) {
                    $orderItem->payment_status = OrderItem::PAYMENT_STATUS_PARTIALLY_PAID;
                } else {
                    $orderItem->payment_status = OrderItem::PAYMENT_STATUS_FULLY_PAID;
                }

                if ($orderItem->item_type == OrderItem::ITEM_TYPE_STUDY_MATERIAL) {
                    $orderItem->order_status = OrderItem::STATUS_ORDER_PLACED;

                    $studyMaterialOrderLog = new StudyMaterialOrderLog();
                    $studyMaterialOrderLog->order_item_id = $orderItem->id;
                    $studyMaterialOrderLog->status = StudyMaterialOrderLog::STATUS_ORDER_PLACED;
                    $studyMaterialOrderLog->save();

                    $package = Package::find($orderItem->package_id);
                    $user = User::with('address')->find($orderItem->user_id);

                    try {
                        Mail::send(new OrderPlaced([
                            'package_id' => $package->id,
                            'package_name' => $package->name,
                            'order_id' => $orderItem->order_id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'address' => optional($user->address)->address ?? '',
                            'area' => optional($user->address)->area ?? '',
                            'landmark' => optional($user->address)->landmark ?? '',
                            'city' => optional($user->address)->city ?? '',
                            'state' => optional($user->address)->state ?? '',
                            'pin' => optional($user->address)->pin ?? '',
                        ]));
                        $email_log = new EmailLog();
                        $email_log->email_to = env('DISPATCHER_MAIL');
                        $email_log->email_from = env('MAIL_FROM_ADDRESS');
                        $email_log->content = "JKSHAH ONLINE - ORDER PLACED";
                        $email_log->save();
                    } catch (\Exception $exception) {
                        info($exception->getMessage());
                    }
                }

                $orderItem->save();
            }

            if ($update_order->spin_wheel_reward_id) {
                $tempCampaignPoint = TempCampaignPoint::find($update_order->spin_wheel_reward_id);
                $tempCampaignPoint->is_used = 1;
                $tempCampaignPoint->order_id = $update_order->id;
                $tempCampaignPoint->save();
            }

            if ($update_order->reward_amount) {
                // $jMoney = JMoney::find($update_order->reward_id);

                // if ($jMoney) {
                //     if ($jMoney->points < $update_order->reward_amount) {
                //         $jMoney->points = 0;
                //         $jMoney->is_used = true;
                //         $jMoney->save();
                //     }

                //     if ($jMoney->points > $update_order->reward_amount) {
                //         $jMoney->points = ($jMoney->points - $update_order->reward_amount);
                //         $jMoney->save();
                //     }

                //     if ($jMoney->points == $update_order->reward_amount) {
                //         $jMoney->points = 0;
                //         $jMoney->is_used = true;
                //         $jMoney->save();
                //     }
                // }
                $jMoney = new JMoney();
                $jMoney->points = $update_order->reward_amount;
                $jMoney->user_id = $update_order->user_id;
                $jMoney->activity = JMoney::PURCHASE;
                $jMoney->is_used = true;
                $jMoney->transaction_type = 2;
                $jMoney->order_id = $update_order->id;
                $jMoney->save();
            }

            if ($update_order->holiday_cashback_point > 0) {
                $jMoney = new JMoney();
                $jMoney->user_id = auth('api')->id();
                $jMoney->activity = JMoney::CASHBACK;
                $jMoney->points = $update_order->holiday_cashback_point;
                $jMoney->expire_after = 365;
                $jMoney->expire_at = Carbon::now()->addDays($jMoney->expire_after);
                $jMoney->order_id = $update_order->id;
                $jMoney->holiday_offer_id = $update_order->holiday_offer_id;
                $jMoney->save();

                try {
                    $holiday = HolidayOffers::where('id', '=', $update_order->holiday_offer_id)->first();
                    $order2 = Order::find($response_parameters['id']);
                    $order_details2 = Student::where('user_id', '=', $order2->user_id)->first();
                    $attributes['email'] = $order_details2['email'];
                    $attributes['j_amount'] = $update_order->holiday_cashback_point;
                    $attributes['holiday_offername'] = $holiday->name;
                    $attributes['holiday_jkoin'] = 1;
                    Mail::send(new JMoneyMail($attributes));

                    $email_log = new EmailLog();
                    $email_log->email_to = $attributes['email'];
                    $email_log->email_from = env('MAIL_FROM_ADDRESS');
                    $email_log->content = "Your J-Koins Gift card is here";
                    $email_log->save();
                } catch (\Exception $exception) {
                    info($exception->getMessage(), ['exception' => $exception]);
                }
            }

            //If it is first purchase, update the jmoneys table
            $orderExist = Order::where('user_id', $update_order->user_id)->where('payment_status', 1)->first();
            if (!$orderExist) {
                if (JMoneySetting::first()->first_purchase_point > 0) {
                    $jMoney = new JMoney();
                    $jMoney->user_id = $update_order->user_id;
                    $jMoney->activity = JMoney::FIRST_PURCHASE;
                    $jMoney->order_id = $update_order->id;
                    $jMoney->points = JMoneySetting::first()->first_purchase_point ?? null;
                    $jMoney->expire_after = JMoneySetting::first()->first_purchase_point_expiry ?? null;
                    $jMoney->expire_at = Carbon::now()->addDays($jMoney->expire_after);
                    $jMoney->save();
                }
            }



            Cart::where('user_id', $update_order->user_id)->delete();
            Cart::where('user_id', $update_order->associate_id)->delete();
            Cart::where('user_id', $update_order->branch_manager_id)->delete();
        } elseif ($response_parameters['order_status'] == "userCancelled") {
            $update_order->payment_status = Order::PAYMENT_STATUS_ABORTED;
            $update_order->commission = null;
        } elseif ($response_parameters['order_status'] == "Failure") {
            $update_order->payment_status = Order::PAYMENT_STATUS_FAILED;
            $update_order->commission = null;
        } elseif ($response_parameters['order_status'] == "Aborted") {
            $update_order->payment_status = Order::PAYMENT_STATUS_ABORTED;
            $update_order->commission = null;
        } elseif ($response_parameters['order_status'] == "Invalid") {
            $update_order->payment_status = Order::PAYMENT_STATUS_INVALID;
            $update_order->commission = null;
        } else {
            $update_order->payment_status = Order::PAYMENT_STATUS_INITIATED;
            $update_order->commission = null;
        }


        //        $update_order->updated_by = $user->id;
        $update_order->updated_method = Order::UPDATE_METHOD_EASEBUZZ;
        $update_order->updated_ip_address = request()->ip();

        $update_order->update();

        DB::commit();

        $transactionResponse = $request->input('transaction_response');
        $payment = $this->paymentService->easebuzzcreate($transactionResponse);
        //     // $transactionResponse = (json_decode($transactionResponse, true));
        //     // return $this->jsonResponse('Order Status', ['order_status' => $transactionResponse, 'order' => '$update_order','holiday_offer_id'=>'$holiday_offer_id']);
        //    try{

        //     return $this->jsonResponse('Order Status', ['order_status' => 'mmeeem', 'order' => '$update_order','holiday_offer_id'=>'$holiday_offer_id']);
        //    }catch(Exception $e){
        //     return $this->jsonResponse('Order Status', ['order_status' => $e->getMessage(), 'order' => '$update_order','holiday_offer_id'=>'$holiday_offer_id']);
        //    }

        // return $this->jsonResponse('Order Status', ['order_status' => 'mmm', 'order' => '$update_order','holiday_offer_id'=>'$holiday_offer_id']);
        $orderItems = OrderItem::where('order_id', $response_parameters['id'])->get();

        foreach ($orderItems as $orderItem) {
            $paymentOrderItem = new PaymentOrderItem;
            $paymentOrderItem->payment_id = $payment['id'];
            $paymentOrderItem->order_item_id = $orderItem->id;
            $paymentOrderItem->is_balance_payment = false;
            $paymentOrderItem->save();

            if ($orderItem->payment_status == OrderItem::PAYMENT_STATUS_PARTIALLY_PAID || $orderItem->payment_status == OrderItem::PAYMENT_STATUS_FULLY_PAID) {
                try {
                    $netAmount = null;

                    if (!$orderItem->is_prebook) {
                        $netAmount = $orderItem->price;
                    }

                    if ($orderItem->is_prebook && $orderItem->payment_status == OrderItem::PAYMENT_STATUS_PARTIALLY_PAID) {
                        $netAmount = $orderItem->booking_amount;
                    }

                    if ($orderItem->is_prebook && $orderItem->payment_status == OrderItem::PAYMENT_STATUS_FULLY_PAID) {
                        $netAmount = $orderItem->balance_amount;
                    }

                    $this->professorRevenueService->store([
                        'package_id' => $orderItem->package_id,
                        'net_amount' => $netAmount,
                        'invoice_id' => $payment->receipt_no,
                        'invoice_date' => $payment->created_at
                    ]);
                } catch (Exception $exception) {
                    info('PROFESSOR REVENUE SERVICE EXCEPTION: ' . $exception->getMessage());
                }
            }
        }

        // $holiday_offer_id= Order::where('id',$response_parameters['id'])->first()->holiday_offer_id;
        $holiday_offer_id = Order::where('id', $response_parameters['id'])->first()->holiday_offer_amount;
        return $this->jsonResponse('Order Status', ['order_status' => $response_parameters['order_status'], 'order' => $update_order, 'holiday_offer_id' => $holiday_offer_id]);
    }
}
