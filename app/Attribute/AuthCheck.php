<?php

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class AuthCheck
{
    public function __construct(
        public string $guard = 'web'
    ) {}
}
