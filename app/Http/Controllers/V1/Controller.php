<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Response;

class Controller extends BaseController
{
    public function jsonResponse($message = '', $data = '', $errors = '', $status = 200) {
        return response()->json(['message' => $message, 'data' => $data, 'errors' => $errors], $status);
    }

    public function success($message = '', $data = null, $status = Response::HTTP_OK) {
        $response = [
            'message' => $message,
            'data' => $data
        ];

        return response()->json($response, $status);
    }

    public function error($status, $message, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ];

        return response()->json($response, $status);
    }
}
