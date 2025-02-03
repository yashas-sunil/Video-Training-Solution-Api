<?php

namespace App\Http\Controllers\V1;

use App\Models\Country;
use App\Models\State;
use Illuminate\Http\Request;
use App\Services\AddressService;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    /** @var AddressService */
    var $addressService;

    /**
     * AddressController constructor.
     * @param AddressService $service
     */
    public function __construct(AddressService $service)
    {
        $this->addressService = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $addresses =  Address::authenticated()->get();

        return $this->jsonResponse('Addresses', $addresses);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'country_code' => '',
            'phone' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            'pin' => 'required',
            'address' => 'required',
            'area' => '',
            'landmark' => '',
            'address_type' => ''
        ]);

        $validated['user_id'] = Auth::id();

        $response = $this->addressService->create($validated);

        return $this->jsonResponse('Address successfully created', $response);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function show(Address $address)
    {
        return $this->jsonResponse('Address', $address->load('student'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Address $address)
    {
        $validated = $request->validate([
            'name' => 'required',
            'country_code' => '',
            'phone' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            'pin' => 'required',
            'address' => 'required',
            'area' => '',
            'landmark' => '',
            'address_type' => ''
        ]);

        $validated['user_id'] = Auth::id();
        $validated['country'] = Country::find($validated['country'])->name ?? null;
        $validated['state'] = State::find($validated['state'])->name ?? null;

        $this->addressService->update($validated, $address);

        return $this->jsonResponse('Address successfully updated', $validated);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function destroy(Address $address)
    {
        $address->delete();

        return $this->jsonResponse('Address successfully deleted');
    }
}
