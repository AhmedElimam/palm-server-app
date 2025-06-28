<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Http\Services\ProductsServices;
class TestScraping extends Command
{
    protected $signature = 'test:scraping {platform=amazon} {limit=5} {--both : Test both Amazon and Jumia APIs}';
    protected $description = 'Test scraping from different platforms';
    public function handle(ProductsServices $productsService)
    {
        if ($this->option('both')) {
            return $this->testBothApis($productsService);
        }

        $platform = $this->argument('platform');
        $limit = (int) $this->argument('limit');

        $this->info("Testing {$platform} API with limit: {$limit}");

        try {
            if ($platform === 'amazon') {
                $products = $productsService->fetchFromApify($limit);
                $this->info("Successfully fetched " . count($products) . " Amazon products");
            } elseif ($platform === 'jumia') {
                $products = $productsService->fetchFromJumiaApify($limit);
                $this->info("Successfully fetched " . count($products) . " Jumia products");
            } else {
                $this->error("Unsupported platform: {$platform}. Use 'amazon' or 'jumia'");
                return 1;
            }

            foreach ($products as $product) {
                $this->line("- {$product->title} ({$product->platform}) - \${$product->price}");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    private function testBothApis(ProductsServices $productsService)
    {
        $this->info("Testing both Amazon and Jumia APIs...");
        
        try {
            $results = $productsService->fetchFromBothApis(3, 3);
            
            $this->info("=== Results Summary ===");
            $this->info("Total products fetched: {$results['total_products']}");
            
            if ($results['amazon']['success']) {
                $this->info("✅ Amazon: {$results['amazon']['count']} products");
                foreach ($results['amazon']['products'] as $product) {
                    $this->line("  - {$product->title} ({$product->platform}) - \${$product->price}");
                }
            } else {
                $this->error("❌ Amazon failed: {$results['amazon']['error']}");
            }
            
            if ($results['jumia']['success']) {
                $this->info("✅ Jumia: {$results['jumia']['count']} products");
                if ($results['jumia']['count'] > 0) {
                    foreach ($results['jumia']['products'] as $product) {
                        $this->line("  - {$product->title} ({$product->platform}) - \${$product->price}");
                    }
                } else {
                    $this->warn("  ⚠️  Jumia API is working but returned 0 products.");
                }
            } else {
                $this->error("❌ Jumia failed: {$results['jumia']['error']}");
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error testing both APIs: " . $e->getMessage());
            return 1;
        }
    }
}
