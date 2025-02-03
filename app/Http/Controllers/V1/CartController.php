<?php

namespace App\Http\Controllers\V1;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\VideoHistory;
use App\Models\WishList;
use App\Models\HolidayOffers;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CartController extends Controller
{

    /** @var  CartService */
    var $cartService;

    /**
     * CartController constructor.
     * @param CartService $cartService
     */
    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }


    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $cart = Cart::findByUserOrUuid(auth('api')->id(), $request->input('uuid'));
        $wishlist = array();//WishList::findByUserOrUuid(auth('api')->id(), $request->input('uuid'))->unique();
        return $this->jsonResponse('Carts', ['cart'=>$cart,'wishlist'=>$wishlist]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'package_id' => 'required'
        ]);

        $packageExist = Cart::query()->where('package_id', $request->input('package_id'))
            ->where('uuid', $request->input('uuid'))
            ->exists();

        if ($packageExist) {
            abort(403);
        }

        $userID = null;

        if (auth('api')->user()) {
            $userID = auth('api')->user()->id;

            $packageExist = Cart::query()->where('package_id', $request->input('package_id'))
                ->where('user_id', $userID)
                ->exists();

            $orderItem = OrderItem::where('package_id', $request->input('package_id'))
                ->whereHas('order', function($query) use($userID) {
                    $query->where('user_id', $userID)
                        ->where('payment_status', '!=', Order::PAYMENT_STATUS_INITIATED);
                })->first();

            $isPackageExpired = false;

            if ($orderItem) {
                $isPackageExpired = VideoHistory::isPackageExpired($request->input('package_id'), $orderItem->id);
            }

            if ($packageExist) {
                abort(403);
            }

            if ($orderItem && !$isPackageExpired) {
                abort(403);
            }
        }

        $cart = $this->cartService->addToCart([
            'uuid' => $request->get('uuid'),
            'user_id' => $userID,
            'package_id' => $request->get('package_id'),
        ]);



        return $this->jsonResponse('Package added to cart', $cart);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Cart  $cart
     * @return \Illuminate\Http\Response
     */
    public function show(Cart $cart)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cart  $cart
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cart $cart)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Cart  $cart
     * @return \Illuminate\Http\Response
     */
    public function destroy(Cart $cart)
    {
        $cart = $this->cartService->delete($cart);

        return $this->jsonResponse($cart);
    }
    
    public function getHolidayScheme(){
        
        
        $holiday_offers = HolidayOffers::where('from_date','<=',Carbon::now())->where('to_date', '>=', Carbon::now())
                                        ->where('is_published',true)->first();
         return $this->jsonResponse('holiday',$holiday_offers);
 
     }
     public function getHolidaySchemeDet(Request $request){
        
         $holiday_offers = HolidayOffers::where('id',$request->input('id'))->first();
        
         return $this->jsonResponse('holidayofer',$holiday_offers);
 
     }
}
