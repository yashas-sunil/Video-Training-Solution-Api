<?php

namespace App\Http\Controllers\V1\BranchManager;

use App\Http\Controllers\V1\Controller;
use Illuminate\Http\Request;
use App\Services\Associate\ProfileService;
use Illuminate\Support\Facades\Auth;
use App\Models\Associate;

class ProfileController extends Controller
{
    /** @var ProfileService */
    var $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();

        return $this->jsonResponse('User', $user);
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
        $response = $this->profileService->update($id, $request->input());

        return $this->jsonResponse('Profile updated', $response);
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

    public function updateAvatar(Request $request)
    {
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/associates/images', $filename, 'admin_storage');
            $associate = Associate::where('user_id', Auth::id())->first();

            $associate->image = $filename;
            $associate->save();
        }

        return $this->jsonResponse('Avatar updated', true);
    }
}
