<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Cart extends BaseModel
{
    public function package()
    {
        return $this->belongsTo('App\Models\Package');
    }

    public static function findByUserOrUuid($userId = null, $uuid = null)
    {

        if (empty($userId) && empty($uuid)) {
            return null;
        }

        /** @var Builder $query */
        $package = Package::where('is_archived', 1)->pluck('id');
        $query = Cart::with('package.language');

        if ($userId) {
            $query->where('user_id', $userId);
        } else if ($uuid) {
            $query->where('uuid', $uuid);
        }
        $query->whereNotIn('package_id', $package);
        $cartItems = $query->get();
        $cartItems = $cartItems->map(function ($item) {
            $item->package->cart_item_id = $item->id;
            return $item;
        });
        $cartItems = $cartItems->pluck('package');

        $subtotal = $cartItems->sum('price');
        $total = $cartItems->sum('selling_price');
        $discountPercentage = 0;
        $discountPrice = 0;

        if ($subtotal) {
            $discountPercentage = round((1 - $total / $subtotal) * 100 * 100) / 100;
            $discountPrice = $subtotal - $total;
        }
        //rakshabandhan starts
        $totalrakshabandanprice = 0;
        $rakshajcoin = 0;

        //previous
        $dataarray = array();
        $cartarray = $cartItems->all();
        if(count($cartarray)>0){

            $sp=0;
            foreach ($cartarray as $val) {
                $courseid = $val->course_id;
                array_push($dataarray, $courseid);
            }
        }
        $holiday_offers = HolidayOffers::where('from_date','<=',Carbon::now())->where('to_date', '>=', Carbon::now())->where('is_published',true)->first();
        if(!empty($holiday_offers['id']) && $total >= $holiday_offers['min_cart_amount']){
            if(empty($holiday_offers['courses'])){                                       
              
                    if($holiday_offers['discount_type']==1)
                    {
                    $totalrakshabandanprice=$holiday_offers['discount_amount'];
                    }
                    else
                    {
                        $totalrakshabandanprice=round(($total * $holiday_offers['discount_amount'])/100);
                    }
                    // $totalrakshabandanprice=round(($total*2.5)/100);
                    if($holiday_offers['cashback_type']==1){
                        $rakshajcoin=$holiday_offers['cashback_amount'];
                    }
                    else{
                        $rakshajcoin=round(($total * $holiday_offers['cashback_amount'])/100);
                        if($holiday_offers['max_cashback']!=null){
                            if($rakshajcoin>=$holiday_offers['max_cashback'])
                            {
                                $rakshajcoin=$holiday_offers['max_cashback'];
                            }
                        }
                    }
            }        
            elseif(!empty($holiday_offers['courses'])){                                       
                $sel_course = explode(',' ,$holiday_offers['courses']); 
                if(!empty($holiday_offers['level_id']) && !empty($holiday_offers['package_type'])){
                        $sel_levels = explode(',' ,$holiday_offers['level_id']); 
                        $sel_type=explode(',' ,$holiday_offers['package_type']); 
                        $ap_package_price=0; 
                        $pkg_price=0;                  
                        foreach ($cartarray as $val) {
                            if(in_array($val->level_id,$sel_levels) && in_array($val->type,$sel_type)){
                                $ap_package_price += $val->selling_price;
                            }
                                            
                        }
                }
                elseif(!empty($holiday_offers['level_id'])){
                    $sel_levels = explode(',' ,$holiday_offers['level_id']); 
                    $ap_package_price=0; 
                    $pkg_price=0;                  
                    foreach ($cartarray as $val) {
                        if(in_array($val->level_id,$sel_levels)){
                            $ap_package_price += $val->selling_price;
                        }
                                        
                    }
                 }
                else if(!empty($holiday_offers['package_type'])){
                    $sel_type=explode(',' ,$holiday_offers['package_type']); 
                    $ap_package_price=0; 
                    $pkg_price=0;                  
                    foreach ($cartarray as $val) {
                        if(in_array($val->course_id,$sel_course) && in_array($val->type,$sel_type)){
                            $ap_package_price += $val->selling_price;
                        }
                                        
                    }
                }
                else{
                    $ap_package_price=0; 
                        $pkg_price=0;                  
                        foreach ($cartarray as $val) {
                            if(in_array($val->course_id,$sel_course)){
                                $ap_package_price += $val->selling_price;
                            }
                                            
                        }
                }    
                if($holiday_offers['discount_type']==1)
                    {
                    $totalrakshabandanprice=$holiday_offers['discount_amount'];
                    }
                    else
                    {
                        $totalrakshabandanprice=round(($ap_package_price * $holiday_offers['discount_amount'])/100);
                    }
                    // $totalrakshabandanprice=round(($total*2.5)/100);
                    if($holiday_offers['cashback_type']==1){
                        $rakshajcoin=$holiday_offers['cashback_amount'];
                    }
                    else{
                        $rakshajcoin=round(($ap_package_price * $holiday_offers['cashback_amount'])/100);
                        if($holiday_offers['max_cashback']!=null){
                            if($rakshajcoin>=$holiday_offers['max_cashback'])
                            {
                                $rakshajcoin=$holiday_offers['max_cashback'];
                            }
                        }
                    }
        
            }
                $total=$total-$totalrakshabandanprice;
            //ends
        }
        // //ends
        //previous
        //new
        // $dataarray = array();
        // $cartarray = $cartItems->all();
        // if(count($cartarray)>0){

        
        //     foreach ($cartarray as $val) {
        //         $courseid = $val->course_id;
        //         array_push($dataarray, $courseid);
        //     }

        //     if ((Carbon::now()->format('Y-m-d') >= "2022-08-14" && Carbon::now()->format('Y-m-d') <= "2022-08-18") && (in_array(3, $dataarray) || in_array(7, $dataarray))) {
        //         $totalrakshabandanprice = round(($total * 7.5) / 100);
        //         $rakshajcoin = $totalrakshabandanprice;

        //         $total = $total - $totalrakshabandanprice;
        //     }
        // }
      


        //new code

        return [
            'uuid' => $uuid,
            'subtotal' => $subtotal,
            'total' => $total,
            'discount' => $discountPrice,
            'discount_percentage' => $discountPercentage,
            'items' => $cartItems->all(),
            'totalrakshabandanprice' => $totalrakshabandanprice,
            'rakshajcoin' => $rakshajcoin,
            
        ];
    }
}
