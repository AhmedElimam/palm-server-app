<?php

require_once 'vendor/autoload.php';

use App\Models\Product;

// Test pagination
echo "Testing pagination...\n";

// Get total count
$totalProducts = Product::count();
echo "Total products in database: {$totalProducts}\n";

// Test first page
$products = Product::orderBy('created_at', 'desc')->paginate(10);
echo "First page products count: " . $products->count() . "\n";
echo "Current page: " . $products->currentPage() . "\n";
echo "Last page: " . $products->lastPage() . "\n";
echo "Total: " . $products->total() . "\n";
echo "Per page: " . $products->perPage() . "\n";

// Test second page if available
if ($products->lastPage() > 1) {
    $productsPage2 = Product::orderBy('created_at', 'desc')->paginate(10, ['*'], 'page', 2);
    echo "Second page products count: " . $productsPage2->count() . "\n";
    echo "Current page: " . $productsPage2->currentPage() . "\n";
}

// Test platform filtering
echo "\nTesting platform filtering...\n";
$amazonProducts = Product::where('platform', 'amazon')->orderBy('created_at', 'desc')->paginate(10);
echo "Amazon products count: " . $amazonProducts->count() . "\n";
echo "Amazon total: " . $amazonProducts->total() . "\n";

$jumiaProducts = Product::where('platform', 'jumia')->orderBy('created_at', 'desc')->paginate(10);
echo "Jumia products count: " . $jumiaProducts->count() . "\n";
echo "Jumia total: " . $jumiaProducts->total() . "\n";

echo "\nPagination test completed!\n"; 