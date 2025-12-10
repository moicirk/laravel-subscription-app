<?php

namespace App\Repositories;

use App\Models\PromoCode;

class PromoCodeRepository
{
    public function findByCode(string $code): PromoCode
    {
        return PromoCode::where('code', $code)->first();
    }
}
