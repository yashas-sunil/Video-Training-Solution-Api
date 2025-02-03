<?php

namespace App\Http\Controllers\V1;

use App\Models\UserFreemium;
use App\Models\Package;
use Illuminate\Http\Request;
use App\Services\UserFreemiumService;
use Illuminate\Support\Facades\Auth;
use App\Models\Language;

class UserFreemiumController extends Controller
{
    /** @var  UserFreemiumService */
    var $userFreemiumService;

    /**
     * UserFreemiumController constructor.
     * @param UserFreemiumService $userFreemiumService
     */
    public function __construct(UserFreemiumService $userFreemiumService)
    {
        $this->userFreemiumService = $userFreemiumService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userFreemium = UserFreemium::user();

        return $this->jsonResponse('Wishlist', $userFreemium);
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

        if ( auth('api')->user()) {
            $userID = auth('api')->user()->id;

            $packageExist = UserFreemium::query()->where('package_id', $request->input('package_id'))
                ->where('user_id', $userID)
                ->exists();

            if ($packageExist) {
                $packageExist = 1;
                return $this->jsonResponse('Already Started', ['exist' => $packageExist]);
            }
        }

        // check if package is enabled for freemium
        $packageData = Package::query()->where('id',$request->input('package_id'))->where('is_freemium',1)->exists();
        if(!$packageData) {
            $packageData = 1;
            return $this->jsonResponse('Freemium Disabled for this package', ['exist' => $packageData]);
        }

        $userFreemium = $this->userFreemiumService->create([
            'user_id' => Auth::id(),
            'package_id' => $request->input('package_id')
        ]);

        return $this->jsonResponse('Free Trial Started', $userFreemium);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UserFreemium  $wishList
     * @return \Illuminate\Http\Response
     */
    public function show(UserFreemium $wishList)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UserFreemium  $wishList
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserFreemium $wishList)
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
        //
    }

    public function getUserFreemiumPackageIds()
    {
        
        $userFreemium = UserFreemium::user()->pluck('package_id');

        return $this->jsonResponse('User Freemium PackageIds', $userFreemium);
    }

    public function removeFromUserFreemium(Request $request)
    {
        $userFreemium = UserFreemium::where('user_id', Auth::id())
            ->where('package_id', $request->input('package_id'))
            ->first();
        $userFreemium->delete();

        return $this->jsonResponse('Package removed from userFreemium');
    }

    public function getUserFreemiumPackages(Request $request){
        $userFreemium = UserFreemium::query()->with('package')
            ->where('user_id', Auth::id())
            ->whereHas('package', function ($query) use ($request){
                if ($request->filter) {
                    $query->where('name', 'like', '%' . $request->filter . '%');
//                        ->orWhereHas('course', function ($query) use ($request) {
//                            $query->where('name', 'like', '%' . $request->filter . '%');
//                        });
                }
                if ($request->subject) {
                    $query->where('subject_id', $request->subject);
                }
                if ($request->recent_view && $request->recent_view != 4 ) {
                    $query->whereHas('videoHistories', function ($query) use ($request){
                        if ($request->recent_view == 1) {
                            $query->latest();
                        }
                        if ($request->recent_view == 2) {

                            $query->whereDate('created_at', '>', Carbon::now()->subWeek());
                        }
                        if ($request->recent_view == 3) {
                            $query->whereDate('created_at', '>', Carbon::now()->subMonth());
                        }
                    });
                }
                if ($request->professor) {
                    $query->whereHas('videos', function ($query) use ($request){
                        $query->where('professor_id', $request->professor);
                    });
                }
                $query->where('is_freemium',1);
            })
            ->limit($request->limit)
            ->get();

            /***************Added BY TE *************/
            foreach ($userFreemium as $orderItem){
               
            $language = Language::where('id',$orderItem['package']->language_id)->first(); 
            $orderItem['package']['language']=$language;
            }
            /**********END******************************/
            $response = $userFreemium;
        return $this->jsonResponse('User Freemium Items', $response);
    }
}
