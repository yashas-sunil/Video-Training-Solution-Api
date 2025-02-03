<?php


namespace App\Services;

use App\Models\WishList;

class WishListService
{
    function create(array $attributes) {
        $wishList = new WishList();
        $wishList->uuid = $attributes['uuid'];
        $wishList->user_id = $attributes['user_id'];
        $wishList->package_id = $attributes['package_id'];
        $wishList->save();

        return $wishList;
    }

    function delete($id = null)
    {
        WishList::findOrFail($id)->delete();
    }
}
