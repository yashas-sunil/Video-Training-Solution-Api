<?php

namespace App\Http\Controllers\V1;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Package;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
       
        // FIXED PRICE COUPON

        $coupon = Coupon::query()
            ->where('name', $request->input('coupon'))
            ->where('amount_type', Coupon::TYPE_FIXED_PRICE)
            ->whereDate('valid_from', '<=', Carbon::today())
            ->whereDate('valid_to', '>=', Carbon::today())
            ->where('status', Coupon::PUBLISH)
            ->where('min_purchase_amount','<=' ,$request->input('amount'))
            ->first();

            // if( @$coupon->min_purchase_amount <= $request->input('amount') ){
            //     $data['total_coupon_amount'] = 0;

            //     return $this->jsonResponse('minimum cart amount value should be Rs '.@$coupon->min_purchase_amount.' to avail this coupon.', $data);
            // }

        if ($coupon) {
            
            $isCouponUsed = Order::query()
                ->where('user_id', auth('api')->id())
                ->where('coupon_id', $coupon->id)
                ->where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
                ->exists();

            if ($isCouponUsed) {
                $data['total_coupon_amount'] = 0;
                return $this->jsonResponse('Coupon already used.', $data);
            }

            $packageIDs = Cart::query()
                ->where('user_id', auth('api')->id())
                ->pluck('package_id');

            $packages = Package::query()
                ->whereIn('id', $packageIDs)
                ->get();

            if (count($packages) != Coupon::FIXED_PRICE_PACKAGE_COUNT) {
                $data['total_coupon_amount'] = 0;
                return $this->jsonResponse('This coupon is only applicable with ' . Coupon::FIXED_PRICE_PACKAGE_COUNT . ' chapter packages.', $data);
            }

            foreach ($packages as $package) {
                if ($package->type != Package::TYPE_CHAPTER_LEVEL) {
                    $data['total_coupon_amount'] = 0;
                    return $this->jsonResponse('This coupon is only applicable with ' . Coupon::FIXED_PRICE_PACKAGE_COUNT . ' chapter packages.', $data);
                }
            }

            $netAmount = intval($request->input('amount'));
            $couponAmount = intval($coupon->amount);

            if ($netAmount <= $couponAmount) {
                $data['total_coupon_amount'] = 0;
                return $this->jsonResponse('Coupon is invalid.', $data);
            }

            $discountedAmount = $netAmount - $couponAmount;
            $data['total_coupon_amount'] = $discountedAmount;

            return $this->jsonResponse('Coupon applied.', $data);
        }

        // FIXED PRICE COUPON [END]

        $total = $request->input('amount');
        $code = $request->input('coupon');

        $valid_coupon = Coupon::where('name', 'LIKE BINARY', $request->input('coupon')) 
            ->first();

            
          

        if ($valid_coupon) {
            if( $valid_coupon->min_purchase_amount > $request->input('amount') ){
                $data['total_coupon_amount'] = 0;

                return $this->jsonResponse('Minimum cart amount value should be Rs '.$valid_coupon->min_purchase_amount.' to avail this coupon.', $data);
            }


            $public_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                ->where('name', $code)
                ->where('coupon_type', Coupon::PUBLIC)
                ->where('valid_from', '<=', Carbon::today())->where('valid_to', '>', Carbon::today())
                ->where('status', Coupon::PUBLISH)->first();
            if ($public_coupon) {
                $data['coupon_type']=1;

                if ($public_coupon->coupon_type == Coupon::PUBLIC) {


                    $coupon_used_user_count = Order::select('user_id')
                                                ->where('coupon_code','=', $public_coupon->name)
                                                ->where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
                                                ->get();
                    if(count($coupon_used_user_count)>0){
                        foreach($coupon_used_user_count as $coupon_user_count){
                            $user_ids[] = $coupon_user_count->user_id;
                        }
    
                        $user_ids = array_unique($user_ids);
                    } else{
                        $user_ids = [];
                    }  

                    if (!in_array(Auth::id(), $user_ids))
                    {
                        if(count($user_ids) >= $public_coupon->total_coupon_limit){
                            $data['total_coupon_amount'] = 0;

                            return $this->jsonResponse("Coupon limit exceeded", $data);
                        }
                    }
                    
                    

                    $used_coupon_count  = Order::where('coupon_code', '=', $public_coupon->name)
                        ->where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
                        ->where('user_id', Auth::id())
                        ->count();

                    if ($used_coupon_count >= $public_coupon->coupon_per_user) {
                        $data['total_coupon_amount'] = 0;
                        return $this->jsonResponse("Maximum coupons used", $data);
                    }
                    //$coupon_limit_count = Order::where('user_id',Auth::id())->where('payment_status', Order::PAYMENT_STATUS_SUCCESS)->where('coupon_code','=',$public_coupon->name)->count();

                   
                    if ($public_coupon->amount_type == Coupon::FLAT) {
                        $coupon_amount = $public_coupon->amount;
                        
                    } else {
                        $coupon_amount = round(($public_coupon->amount / 100) * $total);
                    }
                   
                    if ($public_coupon->amount_type != Coupon::FLAT) {
                        if ($coupon_amount < $public_coupon->max_discount_amount) {

                            $total_coupon_amount =  $coupon_amount;
                        } else {
                            $total_coupon_amount =  $public_coupon->max_discount_amount;
                        }
                    } else {
                        $total_coupon_amount =  $coupon_amount;
                    }
                  
                }
            } else {
                $data['coupon_type']=2;
                $student = Student::query()->where('user_id', Auth::id())->first();
                if($request->input('package_id')){
                    $packageIDs[]=$request->input('package_id');
                }else{
                      $packageIDs = Cart::query()
                    ->where('user_id', auth('api')->id())
                    ->pluck('package_id');
                }

              

                $packages = Package::select('id', 'course_id', 'level_id', 'subject_id','package_type')
                    ->whereIn('id', $packageIDs)
                    ->get();






                // foreach ($packages as $package) {
                //     $p_courses[] = $package->course_id;
                //     $p_levels[]  = $package->level_id;
                //     $p_subjects[] = $package->subject_id;
                //     $p_prof = [];
                //     $prof_package = Package::query()
                //         /* ->with(['packageStudyMaterials','orderItems' => function($query){
                //                 $query->where('review_status', 'ACCEPTED');
                //             },'orderItems.user.student', 'course', 'level'])*/
                //         ->where('id', $package->id)
                //         ->first();
                //     $professors = $prof_package->professors;
                //     foreach ($professors as $professor) {
                //         $p_prof[] = $professor->id;
                //     }
                // }

                $private_coupon_details = Coupon::select('private_coupons.*', 'coupons.name', 'coupons.coupon_type', 'coupons.min_purchase_amount', 'coupons.max_discount_amount', 'coupons.amount_type', 'coupons.total_coupon_limit', 'coupons.coupon_per_user', 'coupons.amount')
                    ->join('private_coupons', 'coupons.id', '=', 'private_coupons.coupon_id')
                    ->where('coupons.name', $code)
                    ->where('coupons.coupon_type', Coupon::PRIVATE)
                  //  ->where('coupons.valid_from','<=', Carbon::today())->where('coupons.valid_to','>' ,Carbon::today())
                    ->where('coupons.status', Coupon::PUBLISH)
                    ->get();

                    $c_courses=[];
                    $c_levels=[];
                    $c_p_types=[];
                    $c_subjects=[];
                    $c_prof=[];
                    $c_student = [];
                foreach ($private_coupon_details as $row) {
                    if($row->course_id !=NULL){
                     $c_courses[] = $row->course_id; 
                    }                 
                    if($row->level_id!=NULL){
                     $c_levels[]=$row->level_id;
                    }
                    if($row->package_type_id!=NULL)
                    {
                    $c_p_types[]  = $row->package_type_id;
                    }
                    if($row->subject_id!=NULL)
                    {
                    $c_subjects[] = $row->subject_id;
                     }
                    if($row->professor_id!=NULL){
                    $c_prof[] = $row->professor_id;
                    }
                    $c_student[] = $row->student_id;
                }
                if ($c_student[0] == 0) {
                    $c_student = [];
                }
                $packageid=[];
                foreach ($packages as $package) {
                    $p_courses = $package->course_id;
                    $p_levels  = $package->level_id;
                    $p_package_type_id=$package->package_type;
                    $p_subjects = $package->subject_id;
                    $p_prof = [];
                    $prof_package = Package::query()
                        /* ->with(['packageStudyMaterials','orderItems' => function($query){
                                $query->where('review_status', 'ACCEPTED');
                            },'orderItems.user.student', 'course', 'level'])*/
                        ->where('id', $package->id)
                        ->first();
                    $professors = $prof_package->professors;
                    foreach ($professors as $professor) {
                        $p_prof[] = $professor->id;
                    }
                        if (!empty($c_student) && !empty($c_prof) ) {

                            if (!empty($c_levels) && !empty($c_courses) && !empty($c_p_types) && !empty($c_subjects)) {

                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                      ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($student, $p_prof,$p_subjects) {
                                        $query->where('student_id', $student->id ?? null);
                                        $query->whereIn('professor_id', $p_prof);
                                        $query->where('subject_id', $p_subjects);
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                                    if($private_coupon){
                                        $total_coupon_amount = 0;
        
                                        $packageid[]=$package->id;
                                    }
                            } 
                            else if ( !empty($c_levels) && !empty($c_courses) && !empty($c_p_types)) {
        
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                       ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($student, $p_levels,$p_prof,$p_package_type_id) {
                                        $query->where('student_id', $student->id ?? null);
                                        $query->where('level_id', $p_levels);
                                        $query->where('package_type_id',$p_package_type_id);
                                        $query->whereIn('professor_id', $p_prof);
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                                    if($private_coupon){
                                        $total_coupon_amount = 0;
        
                                        $packageid[]=$package->id;
                                    }
                            }else if ( !empty($c_levels) && !empty($c_courses)) {
        
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                       ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($student, $p_levels,$p_prof) {
                                        $query->where('student_id', $student->id ?? null);
                                        $query->where('level_id', $p_levels);
                                        $query->whereIn('professor_id', $p_prof);
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                                    if($private_coupon){
                                        $total_coupon_amount = 0;
        
                                        $packageid[]=$package->id;
                                    }
                            } else if ( !empty($c_courses)) {
        
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                       ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($student, $p_courses,$p_prof) {
                                        $query->where('student_id', $student->id ?? null);
                                        $query->where('course_id', $p_courses);
                                        $query->whereIn('professor_id', $p_prof);
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                                    if($private_coupon){
                                        $total_coupon_amount = 0;
        
                                        $packageid[]=$package->id;
                                    }
                            }else  {
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                  ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                ->whereHas('privateCoupon', function ($query) use ($student,$p_prof) {
                                    $query->where('student_id', $student->id ?? null);
                                
                                    $query->whereIn('professor_id', $p_prof);
                                })
        
                                ->where('status', Coupon::PUBLISH)->first();
                                if($private_coupon){
                                    $total_coupon_amount = 0;
        
                                    $packageid[]=$package->id;
                                }
        
                            }
                        }else if(!empty($c_student)){
                            if (!empty($c_levels) && !empty($c_courses) && !empty($c_subjects)&& !empty($c_p_types)) {

                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                      ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($student,$p_subjects) {
                                        $query->where('student_id', $student->id ?? null);
                                       
                                        $query->where('subject_id', $p_subjects);
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                                    if($private_coupon){
                                        $total_coupon_amount = 0;
        
                                        $packageid[]=$package->id;
                                    }
                            } 
                            else if ( !empty($c_levels) && !empty($c_courses) && !empty($c_p_types)) {
        
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                       ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($student, $p_levels,$p_package_type_id) {
                                        $query->where('student_id', $student->id ?? null);
                                        $query->where('level_id', $p_levels);
                                        $query->where('package_type_id',$p_package_type_id);
                                      
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                                    if($private_coupon){
                                        $total_coupon_amount = 0;
        
                                        $packageid[]=$package->id;
                                    }
                            }else if ( !empty($c_levels) && !empty($c_courses)) {
        
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                       ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($student, $p_levels) {
                                        $query->where('student_id', $student->id ?? null);
                                        $query->where('level_id', $p_levels);
                                      
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                                    if($private_coupon){
                                        $total_coupon_amount = 0;
        
                                        $packageid[]=$package->id;
                                    }
                            } else if ( !empty($c_courses)) {
        
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                     ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($student, $p_courses) {
                                        $query->where('student_id', $student->id ?? null);
                                        $query->where('course_id', $p_courses);
                                       
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                                    if($private_coupon){
                                        $total_coupon_amount = 0;
        
                                        $packageid[]=$package->id;
                                    }
                            }else  {
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                               ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                ->whereHas('privateCoupon', function ($query) use ($student) {
                                    $query->where('student_id', $student->id ?? null);
                                
                                   
                                })
        
                                ->where('status', Coupon::PUBLISH)->first();
                                if($private_coupon){
                                    $total_coupon_amount = 0;
        
                                    $packageid[]=$package->id;
                                }
        
                            }
        
        

                        }else if (!empty($c_prof)) {
                            if  (!empty($c_levels) && !empty($c_courses) && !empty($c_subjects) && !empty($c_p_types)) {
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                    ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                ->whereHas('privateCoupon', function ($query) use ($p_subjects, $p_prof) {
                                    $query->whereIn('professor_id', $p_prof);
                                    $query->where('subject_id', $p_subjects);
                                })
    
                                ->where('status', Coupon::PUBLISH)->first();
                                if($private_coupon){
                                    $packageid[]=$package->id;
                                }

                            }
                            else if (!empty($c_levels) && !empty($c_courses) &&!empty($c_p_types)) {
                        
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                      ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($p_prof, $p_levels,$p_package_type_id) {
                                        $query->whereIn('professor_id', $p_prof ?? null);
                                        $query->where('level_id', $p_levels);
                                        $query->where('package_type_id',$p_package_type_id);
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                            if($private_coupon){ 
                                $total_coupon_amount = 0;
        
                                $packageid[]=$package->id;
                            }
                          } 
                            else if (!empty($c_levels) && !empty($c_courses)) {
                        
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                      ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($p_prof, $p_levels) {
                                        $query->whereIn('professor_id', $p_prof ?? null);
                                        $query->where('level_id', $p_levels);
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                            if($private_coupon){ 
                                $total_coupon_amount = 0;
        
                                $packageid[]=$package->id;
                            }
                        }  else if (!empty($c_courses)) {
                 
                            $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                   ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                ->whereHas('privateCoupon', function ($query) use ($p_prof, $p_courses) {
                                    $query->whereIn('professor_id', $p_prof ?? null);
                                    $query->where('course_id', $p_courses);
                                })
    
                                ->where('status', Coupon::PUBLISH)->first();
                                if($private_coupon){ 
                                    $total_coupon_amount = 0;
            
                                    $packageid[]=$package->id;
                                }
                          
                        } 
                       else{
                        
    
                            $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                    ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                ->whereHas('privateCoupon', function ($query) use ($p_prof) {
                                    $query->whereIn('professor_id', $p_prof ?? null);
                                })
    
                                ->where('status', Coupon::PUBLISH)->first();
                                if($private_coupon){ 
                                    $total_coupon_amount = 0;
            
                                    $packageid[]=$package->id;
                                }
                        } 


                        
                    }else if (!empty($c_subjects)) {

                        $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                            ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                               ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                            ->whereHas('privateCoupon', function ($query) use ($p_subjects) {
    
                                $query->where('subject_id', $p_subjects);
                            })
    
                            ->where('status', Coupon::PUBLISH)->first();
                            if($private_coupon){ 
                                $total_coupon_amount = 0;
        
                                $packageid[]=$package->id;
                            }
                    } else if (!empty($c_levels) && !empty($c_p_types)) {
                        
                        $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                            ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                            ->whereHas('privateCoupon', function ($query) use ($p_levels,$p_package_type_id) {                                
                                $query->where('level_id', $p_levels);
                                $query->where('package_type_id',$p_package_type_id);
                            })    
                            ->where('status', Coupon::PUBLISH)->first();
                            if($private_coupon){
                               
                                $total_coupon_amount = 0;
        
                                $packageid[]=$package->id;
                               
                            }
                    } 
                    else if (!empty($c_levels)) {
                        $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                            ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                            ->whereHas('privateCoupon', function ($query) use ($p_levels) {                                
                                $query->where('level_id', $p_levels);
                            })
    
                            ->where('status', Coupon::PUBLISH)->first();
                            if($private_coupon){ 
                                $total_coupon_amount = 0;
        
                                $packageid[]=$package->id;
                            }
                    } 
                    else if (!empty($c_courses)) {

                        $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                            ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                            ->whereHas('privateCoupon', function ($query) use ($p_courses) {
    
                                $query->where('course_id', $p_courses);
                            })
    
                            ->where('status', Coupon::PUBLISH)->first();
                            if($private_coupon){ 
                                $total_coupon_amount = 0;
        
                                $packageid[]=$package->id;
                            }
                    } 


                }
                if(empty($packageid)){
                    $data['total_coupon_amount']=0;
                    return $this->jsonResponse("This coupon code is invalid ", $data);
                }
              
              

               /* $coupon_limit_count = Order::with('private_coupons')
                // ->where('user_id',Auth::id())
                ->where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
                ->where('coupon_code', '=', $private_coupon->name)
                ->count();

                if ($coupon_limit_count >= $private_coupon->total_coupon_limit) {
                    return $this->jsonResponse("Coupon limit exceeded", 0);
                } */

                $private_coupon_name = Coupon::select('*')
               
                ->where('name', $code)
                ->where('coupon_type', Coupon::PRIVATE)
              //  ->where('coupons.valid_from','<=', Carbon::today())->where('coupons.valid_to','>' ,Carbon::today())
                ->where('status', Coupon::PUBLISH)
                ->first();


                $coupon_used_user_count = Order::select('user_id')
                                            ->where('coupon_code','=', $private_coupon_name->name)
                                            ->where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
                                            ->get();

                if(count($coupon_used_user_count)>0){

                    foreach($coupon_used_user_count as $coupon_user_count){

                        $user_ids[] = $coupon_user_count->user_id;
                    }

                    $user_ids = array_unique($user_ids);
                } else{
                    $user_ids = [];
                }  

                if (!in_array(Auth::id(), $user_ids))
                {
                    if(count($user_ids) >= $private_coupon_name->total_coupon_limit){

                        return $this->jsonResponse("Coupon limit exceeded", 0);
                    }
                }



                $used_coupon_count  = Order::with('private_coupons')
                    ->where('coupon_code', '=', $private_coupon_name->name)
                    ->where('user_id', Auth::id())
                    ->where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
                    ->count();
                if ($used_coupon_count >= $private_coupon_name->coupon_per_user) {
                    return $this->jsonResponse("Maximum coupons used", 0);
                }

                //                if($private_coupon->min_purchase_amount >= $total){
                //                    return $this->jsonResponse("Coupon valid for purchase above Rs ".$private_coupon->min_purchase_amount.' /-',0);
                //                }

              
                if ($private_coupon_name->amount_type == Coupon::FLAT) {
                    $coupon_amount = $private_coupon_name->amount;
                   
                } else {
                    $coupon_amount = round(($private_coupon_name->amount / 100) * $total);
                    if ($coupon_amount < $private_coupon_name->max_discount_amount) {

                        $coupon_amount =  $coupon_amount;
                    } else {
                        $coupon_amount =  $private_coupon_name->max_discount_amount;
                    }
                    
                }

                // if ($private_coupon_name->amount_type != Coupon::FLAT) {
                //     if ($coupon_amount < $private_coupon_name->max_discount_amount) {

                //         $total_coupon_amount =  $coupon_amount;
                //     } else {
                //         $total_coupon_amount =  $private_coupon_name->max_discount_amount;
                //     }
                // } else {
                //     $total_coupon_amount =  $coupon_amount;
                // }

               
            }
$data['total_coupon_amount']=$coupon_amount;
$data['packageid']=@$packageid;
            return $this->jsonResponse("Coupon Applied", $data);
        } else {
            $data['total_coupon_amount'] = 0;
            return $this->jsonResponse("This coupon code is invalid ", $data);
        }
    }
    public function indexbk(Request $request)
    {
       
        // FIXED PRICE COUPON

        $coupon = Coupon::query()
            ->where('name', $request->input('coupon'))
            ->where('amount_type', Coupon::TYPE_FIXED_PRICE)
            ->whereDate('valid_from', '<=', Carbon::today())
            ->whereDate('valid_to', '>=', Carbon::today())
            ->where('status', Coupon::PUBLISH)
            ->where('min_purchase_amount','<=' ,$request->input('amount'))
            ->first();

            // if( @$coupon->min_purchase_amount <= $request->input('amount') ){
            //     $data['total_coupon_amount'] = 0;

            //     return $this->jsonResponse('minimum cart amount value should be Rs '.@$coupon->min_purchase_amount.' to avail this coupon.', $data);
            // }

        if ($coupon) {
            
            $isCouponUsed = Order::query()
                ->where('user_id', auth('api')->id())
                ->where('coupon_id', $coupon->id)
                ->where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
                ->exists();

            if ($isCouponUsed) {
                $data['total_coupon_amount'] = 0;
                return $this->jsonResponse('Coupon already used.', $data);
            }

            $packageIDs = Cart::query()
                ->where('user_id', auth('api')->id())
                ->pluck('package_id');

            $packages = Package::query()
                ->whereIn('id', $packageIDs)
                ->get();

            if (count($packages) != Coupon::FIXED_PRICE_PACKAGE_COUNT) {
                $data['total_coupon_amount'] = 0;
                return $this->jsonResponse('This coupon is only applicable with ' . Coupon::FIXED_PRICE_PACKAGE_COUNT . ' chapter packages.', $data);
            }

            foreach ($packages as $package) {
                if ($package->type != Package::TYPE_CHAPTER_LEVEL) {
                    $data['total_coupon_amount'] = 0;
                    return $this->jsonResponse('This coupon is only applicable with ' . Coupon::FIXED_PRICE_PACKAGE_COUNT . ' chapter packages.', $data);
                }
            }

            $netAmount = intval($request->input('amount'));
            $couponAmount = intval($coupon->amount);

            if ($netAmount <= $couponAmount) {
                $data['total_coupon_amount'] = 0;
                return $this->jsonResponse('Coupon is invalid.', $data);
            }

            $discountedAmount = $netAmount - $couponAmount;
            $data['total_coupon_amount'] = $discountedAmount;

            return $this->jsonResponse('Coupon applied.', $data);
        }

        // FIXED PRICE COUPON [END]

        $total = $request->input('amount');
        $code = $request->input('coupon');

        $valid_coupon = Coupon::where('name', 'LIKE BINARY', $request->input('coupon')) 
            ->first();

            
          

        if ($valid_coupon) {
            if( $valid_coupon->min_purchase_amount > $request->input('amount') ){
                $data['total_coupon_amount'] = 0;

                return $this->jsonResponse('Minimum cart amount value should be Rs '.$valid_coupon->min_purchase_amount.' to avail this coupon.', $data);
            }


            $public_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                ->where('name', $code)
                ->where('coupon_type', Coupon::PUBLIC)
                ->where('valid_from', '<=', Carbon::today())->where('valid_to', '>', Carbon::today())
                ->where('status', Coupon::PUBLISH)->first();
            if ($public_coupon) {
                $data['coupon_type']=1;

                if ($public_coupon->coupon_type == Coupon::PUBLIC) {


                    $coupon_used_user_count = Order::select('user_id')
                                                ->where('coupon_code','=', $public_coupon->name)
                                                ->where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
                                                ->get();
                    if(count($coupon_used_user_count)>0){
                        foreach($coupon_used_user_count as $coupon_user_count){
                            $user_ids[] = $coupon_user_count->user_id;
                        }
    
                        $user_ids = array_unique($user_ids);
                    } else{
                        $user_ids = [];
                    }  

                    if (!in_array(Auth::id(), $user_ids))
                    {
                        if(count($user_ids) >= $public_coupon->total_coupon_limit){
                            $data['total_coupon_amount'] = 0;

                            return $this->jsonResponse("Coupon limit exceeded", $data);
                        }
                    }
                    
                    

                    $used_coupon_count  = Order::where('coupon_code', '=', $public_coupon->name)
                        ->where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
                        ->where('user_id', Auth::id())
                        ->count();

                    if ($used_coupon_count >= $public_coupon->coupon_per_user) {
                        $data['total_coupon_amount'] = 0;
                        return $this->jsonResponse("Maximum coupons used", $data);
                    }
                    //$coupon_limit_count = Order::where('user_id',Auth::id())->where('payment_status', Order::PAYMENT_STATUS_SUCCESS)->where('coupon_code','=',$public_coupon->name)->count();

                   
                    if ($public_coupon->amount_type == Coupon::FLAT) {
                        $coupon_amount = $public_coupon->amount;
                        
                    } else {
                        $coupon_amount = round(($public_coupon->amount / 100) * $total);
                    }
                   
                    if ($public_coupon->amount_type != Coupon::FLAT) {
                        if ($coupon_amount < $public_coupon->max_discount_amount) {

                            $total_coupon_amount =  $coupon_amount;
                        } else {
                            $total_coupon_amount =  $public_coupon->max_discount_amount;
                        }
                    } else {
                        $total_coupon_amount =  $coupon_amount;
                    }
                  
                }
            } else {
                $data['coupon_type']=2;
                $student = Student::query()->where('user_id', Auth::id())->first();
                if($request->input('package_id')){
                    $packageIDs[]=$request->input('package_id');
                }else{
                      $packageIDs = Cart::query()
                    ->where('user_id', auth('api')->id())
                    ->pluck('package_id');
                }

              

                $packages = Package::select('id', 'course_id', 'level_id', 'subject_id')
                    ->whereIn('id', $packageIDs)
                    ->get();






                // foreach ($packages as $package) {
                //     $p_courses[] = $package->course_id;
                //     $p_levels[]  = $package->level_id;
                //     $p_subjects[] = $package->subject_id;
                //     $p_prof = [];
                //     $prof_package = Package::query()
                //         /* ->with(['packageStudyMaterials','orderItems' => function($query){
                //                 $query->where('review_status', 'ACCEPTED');
                //             },'orderItems.user.student', 'course', 'level'])*/
                //         ->where('id', $package->id)
                //         ->first();
                //     $professors = $prof_package->professors;
                //     foreach ($professors as $professor) {
                //         $p_prof[] = $professor->id;
                //     }
                // }

                $private_coupon_details = Coupon::select('private_coupons.*', 'coupons.name', 'coupons.coupon_type', 'coupons.min_purchase_amount', 'coupons.max_discount_amount', 'coupons.amount_type', 'coupons.total_coupon_limit', 'coupons.coupon_per_user', 'coupons.amount')
                    ->join('private_coupons', 'coupons.id', '=', 'private_coupons.coupon_id')
                    ->where('coupons.name', $code)
                    ->where('coupons.coupon_type', Coupon::PRIVATE)
                  //  ->where('coupons.valid_from','<=', Carbon::today())->where('coupons.valid_to','>' ,Carbon::today())
                    ->where('coupons.status', Coupon::PUBLISH)
                    ->get();

                    $c_courses=[];
                    $c_levels=[];
                    $c_subjects=[];
                    $c_prof=[];
                foreach ($private_coupon_details as $row) {
                    if($row->course_id !=NULL) 
                    $c_courses[] = $row->course_id;
                   
                    if($row->level_id!=NULL)
                    $c_levels[]  = $row->level_id;
                    if($row->subject_id!=NULL)
                    $c_subjects[] = $row->subject_id;
                    if($row->professor_id!=NULL)
                    $c_prof[] = $row->professor_id;
                    $c_student[] = $row->student_id;
                }
                if ($c_student[0] == 0) {
                    $c_student = [];
                }
                $packageid=[];
                foreach ($packages as $package) {
                    $p_courses = $package->course_id;
                    $p_levels  = $package->level_id;
                    $p_subjects = $package->subject_id;
                    $p_prof = [];
                    $prof_package = Package::query()
                        /* ->with(['packageStudyMaterials','orderItems' => function($query){
                                $query->where('review_status', 'ACCEPTED');
                            },'orderItems.user.student', 'course', 'level'])*/
                        ->where('id', $package->id)
                        ->first();
                    $professors = $prof_package->professors;
                    foreach ($professors as $professor) {
                        $p_prof[] = $professor->id;
                    }
                        if (!empty($c_student) && !empty($c_prof) ) {

                            if (!empty($c_levels) && !empty($c_courses) && !empty($c_subjects)) {

                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                      ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($student, $p_prof,$p_subjects) {
                                        $query->where('student_id', $student->id ?? null);
                                        $query->whereIn('professor_id', $p_prof);
                                        $query->where('subject_id', $p_subjects);
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                                    if($private_coupon){
                                        $total_coupon_amount = 0;
        
                                        $packageid[]=$package->id;
                                    }
                            } else if ( !empty($c_levels) && !empty($c_courses)) {
        
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                       ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($student, $p_levels,$p_prof) {
                                        $query->where('student_id', $student->id ?? null);
                                        $query->where('level_id', $p_levels);
                                        $query->whereIn('professor_id', $p_prof);
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                                    if($private_coupon){
                                        $total_coupon_amount = 0;
        
                                        $packageid[]=$package->id;
                                    }
                            } else if ( !empty($c_courses)) {
        
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                       ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($student, $p_courses,$p_prof) {
                                        $query->where('student_id', $student->id ?? null);
                                        $query->where('course_id', $p_courses);
                                        $query->whereIn('professor_id', $p_prof);
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                                    if($private_coupon){
                                        $total_coupon_amount = 0;
        
                                        $packageid[]=$package->id;
                                    }
                            }else  {
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                  ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                ->whereHas('privateCoupon', function ($query) use ($student,$p_prof) {
                                    $query->where('student_id', $student->id ?? null);
                                
                                    $query->whereIn('professor_id', $p_prof);
                                })
        
                                ->where('status', Coupon::PUBLISH)->first();
                                if($private_coupon){
                                    $total_coupon_amount = 0;
        
                                    $packageid[]=$package->id;
                                }
        
                            }
                        }else if(!empty($c_student)){
                            if (!empty($c_levels) && !empty($c_courses) && !empty($c_subjects)) {

                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                      ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($student,$p_subjects) {
                                        $query->where('student_id', $student->id ?? null);
                                       
                                        $query->where('subject_id', $p_subjects);
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                                    if($private_coupon){
                                        $total_coupon_amount = 0;
        
                                        $packageid[]=$package->id;
                                    }
                            } else if ( !empty($c_levels) && !empty($c_courses)) {
        
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                       ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($student, $p_levels) {
                                        $query->where('student_id', $student->id ?? null);
                                        $query->where('level_id', $p_levels);
                                      
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                                    if($private_coupon){
                                        $total_coupon_amount = 0;
        
                                        $packageid[]=$package->id;
                                    }
                            } else if ( !empty($c_courses)) {
        
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                     ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($student, $p_courses) {
                                        $query->where('student_id', $student->id ?? null);
                                        $query->where('course_id', $p_courses);
                                       
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                                    if($private_coupon){
                                        $total_coupon_amount = 0;
        
                                        $packageid[]=$package->id;
                                    }
                            }else  {
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                               ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                ->whereHas('privateCoupon', function ($query) use ($student) {
                                    $query->where('student_id', $student->id ?? null);
                                
                                   
                                })
        
                                ->where('status', Coupon::PUBLISH)->first();
                                if($private_coupon){
                                    $total_coupon_amount = 0;
        
                                    $packageid[]=$package->id;
                                }
        
                            }
        
        

                        }else if (!empty($c_prof)) {
                            if  (!empty($c_levels) && !empty($c_courses) && !empty($c_subjects)) {
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                    ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                ->whereHas('privateCoupon', function ($query) use ($p_subjects, $p_prof) {
                                    $query->whereIn('professor_id', $p_prof);
                                    $query->where('subject_id', $p_subjects);
                                })
    
                                ->where('status', Coupon::PUBLISH)->first();
                                if($private_coupon){
                                    $packageid[]=$package->id;
                                }

                            }else if (!empty($c_levels) && !empty($c_courses)) {
                        
                                $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                    ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                      ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                    ->whereHas('privateCoupon', function ($query) use ($p_prof, $p_levels) {
                                        $query->whereIn('professor_id', $p_prof ?? null);
                                        $query->where('level_id', $p_levels);
                                    })
        
                                    ->where('status', Coupon::PUBLISH)->first();
                            if($private_coupon){ 
                                $total_coupon_amount = 0;
        
                                $packageid[]=$package->id;
                            }
                        }  else if (!empty($c_courses)) {
                 
                            $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                   ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                ->whereHas('privateCoupon', function ($query) use ($p_prof, $p_courses) {
                                    $query->whereIn('professor_id', $p_prof ?? null);
                                    $query->where('course_id', $p_courses);
                                })
    
                                ->where('status', Coupon::PUBLISH)->first();
                                if($private_coupon){ 
                                    $total_coupon_amount = 0;
            
                                    $packageid[]=$package->id;
                                }
                          
                        } 
                       else{
                        
    
                            $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                                ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                    ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                                ->whereHas('privateCoupon', function ($query) use ($p_prof) {
                                    $query->whereIn('professor_id', $p_prof ?? null);
                                })
    
                                ->where('status', Coupon::PUBLISH)->first();
                                if($private_coupon){ 
                                    $total_coupon_amount = 0;
            
                                    $packageid[]=$package->id;
                                }
                        } 


                        
                    }else if (!empty($c_subjects)) {



                        $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                            ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                               ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                            ->whereHas('privateCoupon', function ($query) use ($p_subjects) {
    
                                $query->where('subject_id', $p_subjects);
                            })
    
                            ->where('status', Coupon::PUBLISH)->first();
                            if($private_coupon){ 
                                $total_coupon_amount = 0;
        
                                $packageid[]=$package->id;
                            }
                    } else if (!empty($c_levels)) {
                        $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                            ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                            ->whereHas('privateCoupon', function ($query) use ($p_levels) {
    
                                $query->where('level_id', $p_levels);
                            })
    
                            ->where('status', Coupon::PUBLISH)->first();
                            if($private_coupon){ 
                                $total_coupon_amount = 0;
        
                                $packageid[]=$package->id;
                            }
                    } else if (!empty($c_courses)) {
                        $private_coupon = Coupon::select('name', 'coupon_type', 'min_purchase_amount', 'max_discount_amount', 'amount_type', 'total_coupon_limit', 'coupon_per_user', 'amount')
                            ->where('name', $code)->where('coupon_type', Coupon::PRIVATE)
                                                ->where('valid_from','<=', Carbon::today())->where('valid_to','>' ,Carbon::today())
                            ->whereHas('privateCoupon', function ($query) use ($p_courses) {
    
                                $query->where('course_id', $p_courses);
                            })
    
                            ->where('status', Coupon::PUBLISH)->first();
                            if($private_coupon){ 
                                $total_coupon_amount = 0;
        
                                $packageid[]=$package->id;
                            }
                    } 





                }
                if(empty($packageid)){
                    $data['total_coupon_amount']=0;
                    return $this->jsonResponse("This coupon code is invalid ", $data);
                }
              
              

               /* $coupon_limit_count = Order::with('private_coupons')
                // ->where('user_id',Auth::id())
                ->where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
                ->where('coupon_code', '=', $private_coupon->name)
                ->count();

                if ($coupon_limit_count >= $private_coupon->total_coupon_limit) {
                    return $this->jsonResponse("Coupon limit exceeded", 0);
                } */

                $private_coupon_name = Coupon::select('*')
               
                ->where('name', $code)
                ->where('coupon_type', Coupon::PRIVATE)
              //  ->where('coupons.valid_from','<=', Carbon::today())->where('coupons.valid_to','>' ,Carbon::today())
                ->where('status', Coupon::PUBLISH)
                ->first();


                $coupon_used_user_count = Order::select('user_id')
                                            ->where('coupon_code','=', $private_coupon_name->name)
                                            ->where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
                                            ->get();

                if(count($coupon_used_user_count)>0){

                    foreach($coupon_used_user_count as $coupon_user_count){

                        $user_ids[] = $coupon_user_count->user_id;
                    }

                    $user_ids = array_unique($user_ids);
                } else{
                    $user_ids = [];
                }  

                if (!in_array(Auth::id(), $user_ids))
                {
                    if(count($user_ids) >= $private_coupon_name->total_coupon_limit){

                        return $this->jsonResponse("Coupon limit exceeded", 0);
                    }
                }



                $used_coupon_count  = Order::with('private_coupons')
                    ->where('coupon_code', '=', $private_coupon_name->name)
                    ->where('user_id', Auth::id())
                    ->where('payment_status', Order::PAYMENT_STATUS_SUCCESS)
                    ->count();
                if ($used_coupon_count >= $private_coupon_name->coupon_per_user) {
                    return $this->jsonResponse("Maximum coupons used", 0);
                }

                //                if($private_coupon->min_purchase_amount >= $total){
                //                    return $this->jsonResponse("Coupon valid for purchase above Rs ".$private_coupon->min_purchase_amount.' /-',0);
                //                }

              
                if ($private_coupon_name->amount_type == Coupon::FLAT) {
                    $coupon_amount = $private_coupon_name->amount;
                   
                } else {
                    $coupon_amount = round(($private_coupon_name->amount / 100) * $total);
                    
                }

                if ($private_coupon_name->amount_type != Coupon::FLAT) {
                    if ($coupon_amount < $private_coupon_name->max_discount_amount) {

                        $total_coupon_amount =  $coupon_amount;
                    } else {
                        $total_coupon_amount =  $private_coupon_name->max_discount_amount;
                    }
                } else {
                    $total_coupon_amount =  $coupon_amount;
                }

               
            }
$data['total_coupon_amount']=$coupon_amount;
$data['packageid']=@$packageid;
            return $this->jsonResponse("Coupon Applied", $data);
        } else {
            $data['total_coupon_amount'] = 0;
            return $this->jsonResponse("This coupon code is invalid ", $data);
        }
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
     * @param  \App\Models\Coupon  $coupon
     * @return \Illuminate\Http\Response
     */
    public function show(Coupon $coupon)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Coupon  $coupon
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Coupon $coupon)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Coupon  $coupon
     * @return \Illuminate\Http\Response
     */
    public function destroy(Coupon $coupon)
    {
        //
    }
}
