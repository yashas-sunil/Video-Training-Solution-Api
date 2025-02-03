<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\V1\Controller;
use App\Models\OrderItem;
use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{

    public function index(Request $request)
    {
        $orderItemId = $request->input('order_item_id');
        $orderItem = OrderItem::find($orderItemId);
        return $this->jsonResponse('Order Item', $orderItem);
    }

    public function store(Request $request)
    {
//        info($request->input('modal_order_item_id'));
//        info($request->input('rating_comment'));
//        info($request->input('rating_value'));
//        info($request->input('review_title'));

        $orderItemId = $request->input('modal_order_item_id');
        $feedbackComment = $request->input('rating_comment');
        $feedbackRating = $request->input('rating_value');
        $feedbackRatingTitle = $request->input('review_title');

        $orderItem = OrderItem::find($orderItemId);
        $orderItem->rating = $feedbackRating;
        $orderItem->review = $feedbackComment;
        $orderItem->review_title = $feedbackRatingTitle;
        $orderItem->reviewed_at = Carbon::now();
        $orderItem->save();

        $packageId = $orderItem->package_id;

        $orderItems = OrderItem::where('package_id', $packageId)->where('rating', '!=', null)->get();
        if(count($orderItems) >0){
            $orderItemTotalRating = $orderItems->sum('rating');
            $packageRating = $orderItemTotalRating / count($orderItems);

            $package = Package::where('id', $packageId)->first();
            $package->rating = $packageRating;
            $package->save();
        }

        $reviewCount = OrderItem::where('package_id', $packageId)->where('review', '!=', null)->count();

        $package = Package::where('id', $packageId)->first();
        $package->number_of_reviews = $reviewCount;
        $package->save();

        return $this->jsonResponse('Review added', $orderItem);
    }

}
