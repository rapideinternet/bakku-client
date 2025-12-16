<?php

namespace RapideSoftware\BakkuClient\Facades;

use Illuminate\Support\Facades\Facade;

class BakkuClient extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'bakku-client';
    }
}
