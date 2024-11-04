<?php

namespace App\Helpers;

class Helper
{
    public static function APIResponse($msg, $resCode, $err, $data)
    {
        return response()->json([
            'code' => $resCode,
            'msg' => $msg,
            'error' => $err,
            'data' => $data
        ], $resCode);
    }
}
