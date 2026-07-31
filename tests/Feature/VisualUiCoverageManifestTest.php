<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\Support\VisualUiCoverage;
use Tests\TestCase;

class VisualUiCoverageManifestTest extends TestCase
{
    public function test_every_named_public_and_rep_route_is_in_the_visual_coverage_manifest(): void
    {
        $registeredNames = collect(Route::getRoutes()->getRoutes())
            ->map(static fn ($route): ?string => $route->getName())
            ->filter()
            ->values();

        foreach (VisualUiCoverage::routeNames() as $routeName) {
            $this->assertTrue(
                $registeredNames->contains($routeName),
                "Visual coverage references missing route [{$routeName}].",
            );
        }

        $repRouteNames = $registeredNames
            ->filter(static fn (string $name): bool => str_starts_with($name, 'app.'))
            ->reject(static fn (string $name): bool => str_starts_with($name, 'app.livewire.'))
            ->sort()
            ->values()
            ->all();

        $manifestRepRouteNames = collect(VisualUiCoverage::routeNames())
            ->filter(static fn (string $name): bool => str_starts_with($name, 'app.'))
            ->sort()
            ->values()
            ->all();

        $this->assertSame($repRouteNames, $manifestRepRouteNames);
    }

    public function test_every_filament_resource_is_in_the_visual_coverage_manifest(): void
    {
        $discovered = collect(File::files(app_path('Filament/Resources')))
            ->filter(static fn ($file): bool => str_ends_with($file->getFilename(), 'Resource.php'))
            ->map(static fn ($file): string => 'App\\Filament\\Resources\\'.$file->getFilenameWithoutExtension())
            ->sort()
            ->values()
            ->all();

        $manifest = collect(VisualUiCoverage::adminResources())
            ->sort()
            ->values()
            ->all();

        $this->assertSame($discovered, $manifest);
    }

    public function test_every_custom_filament_page_and_dashboard_widget_is_manifested(): void
    {
        $this->assertClassDirectoryMatchesManifest(
            app_path('Filament/Pages'),
            'App\\Filament\\Pages\\',
            VisualUiCoverage::adminPages(),
        );

        $this->assertClassDirectoryMatchesManifest(
            app_path('Filament/Widgets'),
            'App\\Filament\\Widgets\\',
            VisualUiCoverage::dashboardWidgets(),
        );
    }

    public function test_manifest_contains_all_required_roles_locales_viewports_states_and_workflows(): void
    {
        $this->assertSame(['ar', 'en'], VisualUiCoverage::locales());

        foreach ([
            'unauthenticated',
            'super_admin',
            'admin',
            'sales_manager',
            'accounts',
            'purchasing',
            'warehouse_keeper',
            'executive',
            'hr_admin',
            'system_viewer',
            'sales_rep',
            'rep',
            'disabled',
            'multi_company',
        ] as $role) {
            $this->assertContains($role, VisualUiCoverage::roles());
        }

        $this->assertArrayHasKey('mobile-small', VisualUiCoverage::viewports());
        $this->assertArrayHasKey('laptop-14', VisualUiCoverage::viewports());
        $this->assertContains('offline', VisualUiCoverage::states());
        $this->assertContains('sync-conflict', VisualUiCoverage::states());
        $this->assertSame(
            array_map(static fn (int $number): string => sprintf('WF-%02d', $number), range(1, 9)),
            array_slice(VisualUiCoverage::workflows(), 0, 9),
        );
        $this->assertCount(19, VisualUiCoverage::workflows());
    }

    /**
     * @param  list<class-string>  $manifest
     */
    private function assertClassDirectoryMatchesManifest(string $directory, string $namespace, array $manifest): void
    {
        $discovered = collect(File::files($directory))
            ->filter(static fn ($file): bool => $file->getExtension() === 'php')
            ->map(static fn ($file): string => $namespace.$file->getFilenameWithoutExtension())
            ->filter(static fn (string $class): bool => (new ReflectionClass($class))->isInstantiable())
            ->sort()
            ->values()
            ->all();

        $expected = collect($manifest)->sort()->values()->all();

        $this->assertSame($discovered, $expected);
    }
}
