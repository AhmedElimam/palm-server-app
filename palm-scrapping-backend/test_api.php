<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\ProductsController;
use App\Http\Services\ProductsServices;

echo "Testing Unified Products API\n";
echo "===========================\n\n";

// Test 1: Get unified products (should mix both platforms)
echo "1. Testing unified products endpoint (mixed platforms):\n";
$controller = new ProductsController(new ProductsServices());
$request = new \Illuminate\Http\Request();
$request->merge(['per_page' => 10]);

try {
    $response = $controller->index($request);
    $data = json_decode($response->getContent(), true);
    
    echo "Success: " . ($data['success'] ? 'Yes' : 'No') . "\n";
    echo "Total products: " . $data['meta']['total'] . "\n";
    echo "Products returned: " . count($data['data']) . "\n";
    echo "Platform filter: " . ($data['meta']['platform_filter'] ?? 'None') . "\n";
    
    // Count platforms in the response
    $amazonCount = 0;
    $jumiaCount = 0;
    foreach ($data['data'] as $product) {
        if ($product['platform'] === 'amazon') {
            $amazonCount++;
        } elseif ($product['platform'] === 'jumia') {
            $jumiaCount++;
        }
    }
    
    echo "Platform distribution in response:\n";
    echo "  - Amazon: {$amazonCount} products\n";
    echo "  - Jumia: {$jumiaCount} products\n";
    
    if (!empty($data['data'])) {
        echo "\nSample products:\n";
        foreach (array_slice($data['data'], 0, 5) as $index => $product) {
            echo "  " . ($index + 1) . ". ID: " . $product['id'] . 
                 ", Platform: " . $product['platform'] . 
                 ", Title: " . substr($product['title'], 0, 40) . "...\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Get products by platform (Amazon)
echo "2. Testing Amazon products endpoint:\n";
$request = new \Illuminate\Http\Request();
$request->merge(['per_page' => 5]);

try {
    $response = $controller->getByPlatform($request, 'amazon');
    $data = json_decode($response->getContent(), true);
    
    echo "Success: " . ($data['success'] ? 'Yes' : 'No') . "\n";
    echo "Total Amazon products: " . $data['meta']['total'] . "\n";
    echo "Products returned: " . count($data['data']) . "\n";
    echo "Platform: " . $data['meta']['platform'] . "\n";
    
    if (!empty($data['data'])) {
        echo "Sample Amazon product:\n";
        $sample = $data['data'][0];
        echo "  - ID: " . $sample['id'] . "\n";
        echo "  - Title: " . substr($sample['title'], 0, 50) . "...\n";
        echo "  - Platform: " . $sample['platform'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Get products by platform (Jumia)
echo "3. Testing Jumia products endpoint:\n";
$request = new \Illuminate\Http\Request();
$request->merge(['per_page' => 5]);

try {
    $response = $controller->getByPlatform($request, 'jumia');
    $data = json_decode($response->getContent(), true);
    
    echo "Success: " . ($data['success'] ? 'Yes' : 'No') . "\n";
    echo "Total Jumia products: " . $data['meta']['total'] . "\n";
    echo "Products returned: " . count($data['data']) . "\n";
    echo "Platform: " . $data['meta']['platform'] . "\n";
    
    if (!empty($data['data'])) {
        echo "Sample Jumia product:\n";
        $sample = $data['data'][0];
        echo "  - ID: " . $sample['id'] . "\n";
        echo "  - Title: " . substr($sample['title'], 0, 50) . "...\n";
        echo "  - Platform: " . $sample['platform'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Get unified products with platform filter
echo "4. Testing unified products with Amazon filter:\n";
$request = new \Illuminate\Http\Request();
$request->merge(['per_page' => 5, 'platform' => 'amazon']);

try {
    $response = $controller->index($request);
    $data = json_decode($response->getContent(), true);
    
    echo "Success: " . ($data['success'] ? 'Yes' : 'No') . "\n";
    echo "Total products: " . $data['meta']['total'] . "\n";
    echo "Products returned: " . count($data['data']) . "\n";
    echo "Platform filter: " . $data['meta']['platform_filter'] . "\n";
    
    if (!empty($data['data'])) {
        echo "Sample product:\n";
        $sample = $data['data'][0];
        echo "  - ID: " . $sample['id'] . "\n";
        echo "  - Title: " . substr($sample['title'], 0, 50) . "...\n";
        echo "  - Platform: " . $sample['platform'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed!\n"; 