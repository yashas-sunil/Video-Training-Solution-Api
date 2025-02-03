<?php


namespace App\Services;

use App\Models\Address;

class AddressService
{
    /**
     * @param $attributes array
     * @return Address
     */
    public function create($attributes) {
        return Address::create($attributes);
    }

    /**
     * @param $attributes array
     * @param Address $address
     * @return mixed
     */
    public function update($attributes, $address) {
        return $address->update($attributes);
    }
}
