<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Concerns\CanBeHidden;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\CanBeRequired;
use Filament\Forms\Components\Concerns\HasExtraAlpineAttributes;
use Filament\Forms\Components\Concerns\HasExtraAttributes;
use Filament\Forms\Components\Concerns\HasLabel;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;

class LeafletMapPicker extends Field
{
    use CanBeHidden;
    use CanBeReadOnly;
    use CanBeRequired;
    use HasExtraAlpineAttributes;
    use HasExtraAttributes;
    use HasLabel;
    use HasPlaceholder;

    protected string $view = 'filament.forms.components.leaflet-map-picker';

    protected string|float|int|null $defaultZoom = 15;

    protected string|float|int|null $defaultLatitude = 24.7136;

    protected string|float|int|null $defaultLongitude = 46.6753;

    protected string $latitudeField = 'latitude';

    protected string $longitudeField = 'longitude';

    public function defaultZoom(string|float|int|null $zoom): static
    {
        $this->defaultZoom = $zoom;

        return $this;
    }

    public function defaultLatitude(string|float|int|null $latitude): static
    {
        $this->defaultLatitude = $latitude;

        return $this;
    }

    public function defaultLongitude(string|float|int|null $longitude): static
    {
        $this->defaultLongitude = $longitude;

        return $this;
    }

    public function latitudeField(string $field): static
    {
        $this->latitudeField = $field;

        return $this;
    }

    public function longitudeField(string $field): static
    {
        $this->longitudeField = $field;

        return $this;
    }

    public function getDefaultZoom(): string|float|int|null
    {
        return $this->defaultZoom;
    }

    public function getDefaultLatitude(): string|float|int|null
    {
        return $this->defaultLatitude;
    }

    public function getDefaultLongitude(): string|float|int|null
    {
        return $this->defaultLongitude;
    }

    public function getLatitudeField(): string
    {
        return $this->latitudeField;
    }

    public function getLongitudeField(): string
    {
        return $this->longitudeField;
    }
}
