<?php

namespace App\Http\Controllers\V1;

use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentOrderItem;
use Illuminate\Http\Request;
use App\Services\PaymentService;
use Mockery\Exception;
use App\Services\ProfessorRevenueService;

class BalanceOrderController extends Controller
{
    /** @var PaymentService $paymentService */
    var $paymentService;

    /** @var ProfessorRevenueService */
    var $professorRevenueService;

    /**
     * BalanceOrderController constructor.
     * @param PaymentService $paymentService
     * @param ProfessorRevenueService $professorRevenueService
     */
    public function __construct(PaymentService $paymentService, ProfessorRevenueService $professorRevenueService)
    {
        $this->paymentService = $paymentService;
        $this->professorRevenueService = $professorRevenueService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $response = $this->paymentService->create($request->input());

        $orderItem = OrderItem::find($request->input('merchant_param2'));

        if ($response['payment_status'] == Payment::PAYMENT_STATUS_SUCCESS) {
            $orderItem->markAsPaid();
        }

        $paymentOrderItem = new PaymentOrderItem;
        $paymentOrderItem->payment_id = $response['id'];
        $paymentOrderItem->order_item_id = $orderItem->id;
        $paymentOrderItem->is_balance_payment = true;
        $paymentOrderItem->save();

        if ($orderItem->payment_status == OrderItem::PAYMENT_STATUS_PARTIALLY_PAID || $orderItem->payment_status == OrderItem::PAYMENT_STATUS_FULLY_PAID) {
            try {
                $netAmount = null;

                if (! $orderItem->is_prebook) {
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
                    'invoice_id' => $response['receipt_no'],
                    'invoice_date' => $response['created_at']
                ]);
            } catch (Exception $exception) {
//                info ('PROFESSOR REVENUE SERVICE EXCEPTION: ' . $exception->getMessage());
            }
        }

        return $this->jsonResponse('Balance Order', $response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
