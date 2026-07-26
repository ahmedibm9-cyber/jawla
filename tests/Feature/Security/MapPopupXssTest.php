<?php

namespace Tests\Feature\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MapPopupXssTest extends TestCase
{
    #[DataProvider('mapViewProvider')]
    public function test_map_popups_build_untrusted_labels_with_dom_text_nodes(string $view): void
    {
        $source = file_get_contents(resource_path("views/filament/pages/{$view}.blade.php"));
        $popupBuilder = file_get_contents(resource_path('js/maps/popup-content.js'));

        $this->assertIsString($source);
        $this->assertIsString($popupBuilder);
        $this->assertStringContainsString('document.createTextNode', $popupBuilder);
        $this->assertStringContainsString('.textContent =', $popupBuilder);
        $this->assertStringContainsString('bindPopup(content)', $source);
        $this->assertStringNotContainsString("parts.join('<br>')", $source);
        $this->assertStringNotContainsString('${p.name', $source);
    }

    public static function mapViewProvider(): array
    {
        return [
            'rep live map' => ['rep-live-map'],
            'customer map' => ['customer-map'],
        ];
    }
}
