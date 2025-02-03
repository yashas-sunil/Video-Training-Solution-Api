<?php

namespace App\Http\Controllers\V1\Professor;

use App\Http\Controllers\V1\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\Professor\ProfileService;
use Illuminate\Support\Facades\Auth;
use App\Models\Professor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /** @var ProfileService $profileService */
    var $profileService;

    /**
     * ProfileController constructor.
     * @param ProfileService $profileService
     */
    public function __construct(ProfileService $profileService)
    {
        return $this->profileService = $profileService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $response = request()->user()->load('professor');

        return $this->jsonResponse('Professor', $response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs(env('IMAGE_URL').'/public/professors/images', $filename);
            $image->storeAs('public/professors/images', $filename, 'admin_storage');
            $professor = Professor::where('user_id', Auth::id())->first();

            $professor->image = $filename;
            $professor->save();
        }

        return $this->jsonResponse('Profile successfully updated');
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

        return $this->jsonResponse('Profile successfully updated', $response);
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
    public function changePassword(Request $request, $id)
    {
        $professor_id = Auth::id();
        $user = User::find($professor_id);
        $password = Hash::make($request->input('confirm_password'));
        $user->password = $password;
        $user->update();
        return $this->jsonResponse('Profile successfully updated');
    }
}
