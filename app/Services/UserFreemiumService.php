<?php


namespace App\Services;

use App\Models\UserFreemium;

class UserFreemiumService
{
    function create(array $attributes) {
        $wishList = new UserFreemium();
        $wishList->user_id = $attributes['user_id'];
        $wishList->package_id = $attributes['package_id'];
        $wishList->save();

        return $wishList;
    }

    function delete($id = null)
    {
        UserFreemium::findOrFail($id)->delete();
    }
}
