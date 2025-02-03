<?php

namespace App\Http\Controllers\V1;

use App\Models\OrderItem;
use App\Models\StudyMaterialOrderLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $orders= OrderItem::getAll($request->input('package_id'), $request->input('with'));
        $orderItems = $orders->first();

        return $this->jsonResponse('Order Items', $orderItems);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\OrderItem  $orderItem
     * @return \Illuminate\Http\Response
     */
    public function show(OrderItem $orderItem)
    {
        $relations = request()->input('relations');

        if ($relations) {
            $orderItem->load($relations);
        }

        return $this->jsonResponse('Order Item', $orderItem);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\OrderItem  $orderItem
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, OrderItem $orderItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\OrderItem  $orderItem
     * @return \Illuminate\Http\Response
     */
    public function destroy(OrderItem $orderItem)
    {
        //
    }

    public function markAsCompleted($id)
    {
        $orderItem = OrderItem::find($id);
        $orderItem->is_completed = true;
        $orderItem->save();

        return $this->jsonResponse('Order item marked as completed', $orderItem);
    }

    //order history
    public function getOrderHistory(Request  $request){
       $orderhistory=StudyMaterialOrderLog::select('status','created_at')->where('order_item_id',$request->id)->get();
       return $this->jsonResponse('Order History',$orderhistory );
    }

    public function packageOrderItems(Request $request)
    {
        $orderItems = OrderItem::where('package_id', $request->input('package_id'))->whereIn('payment_status', [
            OrderItem::PAYMENT_STATUS_PARTIALLY_PAID,
            OrderItem::PAYMENT_STATUS_FULLY_PAID
        ])->get();

        return $this->jsonResponse('Order Items', $orderItems);
    }
}
