<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;

class CompanyAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        return asset('pfp.png.jpg');
    }
}
