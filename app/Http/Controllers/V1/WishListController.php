<?php

namespace App\Http\Controllers\V1;

use App\Models\Cart;
use App\Models\OrderItem;
use App\Models\WishList;
use App\Models\Package;
use Illuminate\Http\Request;
use App\Services\WishListService;
use Illuminate\Support\Facades\Auth;

class WishListController extends Controller
{
    /** @var  WishListService */
    var $wishListService;

    /**
     * WishListController constructor.
     * @param WishListService $wishListService
     */
    public function __construct(WishListService $wishListService)
    {
        $this->wishListService = $wishListService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $wishlist = WishList::user();

        return $this->jsonResponse('Wishlist', $wishlist);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'package_id' => 'required'
        ]);

        $userID = null;

        if (auth('api')->user()) {
            $userID = auth('api')->user()->id;

            $packageExist = WishList::query()->where('package_id', $request->input('package_id'))
                ->where('user_id', $userID)
                ->exists();

            if ($packageExist) {
                $packageExist = 1;
                return $this->jsonResponse('Already Added', ['exist' => $packageExist]);
            }
        }


        $wishList = $this->wishListService->create([
            'uuid' => $request->get('uuid'),
            'user_id' => Auth::id(),
            'package_id' => $request->input('package_id')
        ]);

        return $this->jsonResponse('Package added to Wishlist', $wishList);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WishList  $wishList
     * @return \Illuminate\Http\Response
     */
    public function show(WishList $wishList)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WishList  $wishList
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, WishList $wishList)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  integer $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->wishListService->delete($id);

        return $this->jsonResponse('Wishlist deleted');
    }

    public function getWishListUserPackageIds()
    {
        $wishlist = WishList::user()->pluck('package_id');

        return $this->jsonResponse('Wishlist User PackageIds', $wishlist);
    }

    public function removeFromWishList(Request $request)
    {
        $wishlist = WishList::where('user_id', Auth::id())
            ->where('package_id', $request->input('package_id'))
            ->first();
        $wishlist->delete();

        return $this->jsonResponse('Package removed from wishlist');
    }

    public function getWishListUserPackages(){
        $wishlist = WishList::user()->pluck('package_id');
        $packages = Package::with(['language','orderItems' => function($query){
                        $query->where('review_status', 'ACCEPTED');
                    }])->whereIn('id',$wishlist)->get();
        return $this->jsonResponse('Wishlist Items', $packages);
    }
}
