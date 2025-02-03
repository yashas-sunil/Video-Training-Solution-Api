<?php

namespace App\Services;

use App\Models\CallRequest;

class CallRequestService
{
    /**
     * @param array $attributes
     * @return mixed
     */
    public function create($attributes = [])
    {
        return CallRequest::create($attributes);
    }
}
