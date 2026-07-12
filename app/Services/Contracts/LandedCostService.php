<?php

namespace App\Services\Contracts;

use App\Models\GoodsInTransit;

interface LandedCostService
{
    public function distribute(GoodsInTransit $git): void;
}