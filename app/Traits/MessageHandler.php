<?php

namespace App\Traits;

trait MessageHandler
{

    /**
     * Returning json message
     *
     * @param array $data
     * @param integer $code
     * @return void
     */
    public function returnResponse($data, $code = 200)
    {
        return response()->json($data, $code);
    }
}
