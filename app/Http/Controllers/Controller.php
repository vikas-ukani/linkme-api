<?php

namespace App\Http\Controllers;

use App\Traits\MessageHandler;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Storage;

class Controller extends BaseController
{
    use MessageHandler; // HTTP Response handler
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function preparePushMessage($constantstring, $placeholderarr)
    {
        $placeholders = array_map(function ($placeholder) {
            return "{{$placeholder}}";
        }, array_keys($placeholderarr));

        return strtr($constantstring, array_combine($placeholders, $placeholderarr));
    }

    public function cloudPath($filename)
    {
        return config('app.env') . '/' . $filename;
    }

    public function cdnUrl()
    {
        $cloud = config('filesystems.cloud');
        return config("filesystems.disks.{$cloud}.url") . '/' . config('app.env') . '/';
    }

    public function cdnStaticUrl()
    {
        $cloud = config('filesystems.cloud');
        return config("filesystems.disks.{$cloud}.url") . '/static/';
    }

    public function cloudUpload($extension, $fileContent)
    {
        $filename = rand() . '.' . $extension;
        Storage::disk()->put($this->cloudPath($filename), $fileContent);
        // Storage::disk('s3')->put($this->cloudPath($filename), $fileContent);
        return $filename;
    }
}