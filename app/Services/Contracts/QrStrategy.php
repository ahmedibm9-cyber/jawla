<?php

namespace App\Services\Contracts;

interface QrStrategy
{
    public function generate(object $document): string;
}
