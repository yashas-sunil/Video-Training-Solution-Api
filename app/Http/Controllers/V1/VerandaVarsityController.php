<?php

namespace App\Http\Controllers\V1;

use App\Models\User;
use App\Models\OrderItem;
use App\Models\PackageRulebook;
use App\Models\PackageRulebookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Language;

class VerandaVarsityController extends Controller
{
  
    public function __construct()
    {
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
        $request->validate([
            'agreeTnC' => 'required'
        ]);
        // PackageRulebookLog
        $package_rulebook_id = $request->input('agreeTnC');
        $userID = auth('api')->user()->id;
        if ( $userID && $package_rulebook_id) {
            $Exist = PackageRulebookLog::query()
                ->where('user_id', $userID)
                ->where('package_rulebook_id', $package_rulebook_id)
                ->exists();

            if ($Exist) {
                $Exist = 1;
                return $this->jsonResponse('Already Agreed', ['exist' => $Exist]);
            }
        }
        
        $agreeTnC = new PackageRulebookLog;
        $agreeTnC->user_id = $userID;
        $agreeTnC->package_rulebook_id = $package_rulebook_id;
        $agreeTnC->save();
        
        return $this->jsonResponse('Already Agreed', $agreeTnC);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $data
     * @return \Illuminate\Http\Response
     */
    public function show(User $data)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $data
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $data)
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

    public function getUserAgreeTnC()
    {
        $userID = auth('api')->user()->id;
        $already_agree_rulebook = 1;
        $show_popup = 0;
        $show_package_rulebook = null;

        $userPackages = OrderItem::query()
        ->where('user_id', auth('api')->id())
        ->pluck('package_id')
        ->unique();

        $package_rulebook_data = PackageRulebook::query()
        ->whereIn('package_id',$userPackages)
        ->get();        
        
        if (!empty($package_rulebook_data) && $userID) {
            
            $show_popup = 1;
            
            foreach ($package_rulebook_data as $package_rulebook){
                $agreeTnC = PackageRulebookLog::query()
                ->where('user_id', $userID)
                ->where('package_rulebook_id', $package_rulebook->id)
                ->exists();
                if (!$agreeTnC) {
                    $already_agree_rulebook = 0;
                    $show_package_rulebook = $package_rulebook;
                    break;
                }
            }
        }
        return $this->jsonResponse('agreeTnC', [
            'all_rulebooks' => $package_rulebook_data,
            'show_popup' => $show_popup,
            'show_rulebook' => $show_package_rulebook,
            'success' => $already_agree_rulebook,
        ]);
    }

}
