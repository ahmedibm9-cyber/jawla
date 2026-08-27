<?php

namespace App\Support;

/**
 * Canonical catalogue of public-API token abilities (scopes). A Sanctum token
 * carries a subset of these; each read endpoint is gated on the matching ability.
 * Keep this list in lock-step with the routes in routes/api.php.
 */
final class ApiAbilities
{
    public const READ_PRODUCTS = 'read:products';

    public const READ_CUSTOMERS = 'read:customers';

    public const READ_INVOICES = 'read:invoices';

    /**
     * @return array<string, array{ar: string, en: string}> ability => bilingual label
     */
    public static function all(): array
    {
        return [
            self::READ_PRODUCTS => ['ar' => 'قراءة المنتجات', 'en' => 'Read products'],
            self::READ_CUSTOMERS => ['ar' => 'قراءة العملاء', 'en' => 'Read customers'],
            self::READ_INVOICES => ['ar' => 'قراءة الفواتير', 'en' => 'Read invoices'],
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /** @return array<string, string> Bilingual label => key map for a Filament CheckboxList. */
    public static function options(): array
    {
        $ar = app()->getLocale() === 'ar';

        return collect(self::all())
            ->mapWithKeys(fn (array $label, string $key) => [$key => $ar ? $label['ar'] : $label['en']])
            ->all();
    }
}
