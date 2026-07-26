<?php

use App\Enums\StockReason;
use App\Models\Product;
use App\Services\PaymentService;
use App\Services\StockService;
use App\Support\ActiveCompanyContext;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$arguments = $argv;
array_shift($arguments);
$mode = array_shift($arguments);
$barrier = array_shift($arguments);
$companyId = array_shift($arguments);
$ids = $arguments;
app(ActiveCompanyContext::class)->setCompanyId((int) $companyId);

$deadline = microtime(true) + 10;
while (! is_file($barrier) && microtime(true) < $deadline) {
    usleep(10_000);
}
if (! is_file($barrier)) {
    fwrite(STDERR, 'barrier timeout');
    exit(3);
}

try {
    if ($mode === 'stock') {
        [$warehouseId, $productId, $userId] = array_map('intval', $ids);
        $product = Product::findOrFail($productId);
        app(StockService::class)->decrement(
            $warehouseId,
            $productId,
            null,
            1.0,
            StockReason::Sale,
            $product,
            $userId,
        );
    } elseif ($mode === 'count') {
        [$warehouseId, $productId, $userId] = array_map('intval', $ids);
        $product = Product::findOrFail($productId);
        app(StockService::class)->reconcile(
            $warehouseId,
            $productId,
            null,
            2.0,
            'Concurrent physical count',
            $userId,
            1.0,
            $product,
        );
    } elseif ($mode === 'return') {
        [$warehouseId, $productId, $userId] = array_map('intval', $ids);
        $product = Product::findOrFail($productId);
        app(StockService::class)->increment(
            $warehouseId,
            $productId,
            null,
            1.0,
            StockReason::Return,
            $product,
            $userId,
        );
    } elseif ($mode === 'payment') {
        [$userId, $customerId, $invoiceId, $intentId] = $ids;
        app(PaymentService::class)->collect(
            (int) $companyId,
            (int) $userId,
            (int) $customerId,
            50.0,
            'cash',
            (int) $invoiceId,
            intentId: $intentId,
        );
    } else {
        throw new RuntimeException('unknown mode');
    }

    fwrite(STDOUT, 'ok');
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage());
    exit(2);
}
