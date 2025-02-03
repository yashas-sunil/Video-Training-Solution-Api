<?php

namespace App\Services;


use App\Models\Cart;

class CartService
{

    function addToCart($data) {
        $cart = new Cart();
        $cart->uuid = $data['uuid'];
        $cart->user_id = $data['user_id'];
        $cart->package_id = $data['package_id'];
        $cart->save();

        return $cart;
    }

    function delete($cart) {
        return $cart->delete();
    }
}
