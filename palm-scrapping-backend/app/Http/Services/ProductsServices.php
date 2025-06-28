<?php
namespace App\Http\Services;
use App\Models\Product;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use DOMDocument;
use DOMXPath;
class ProductsServices
{
    private Client $client;
    private string $apifyToken;
    private string $jumiaApifyToken;
    private array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:89.0) Gecko/20100101 Firefox/89.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Edge/91.0.864.59',
    ];
    public function __construct()
    {
        $this->apifyToken = config('apify.tokens.amazon');
        $this->jumiaApifyToken = config('apify.tokens.jumia');
        
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false,
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ]
        ]);
    }
    public function fetchFromApify(int $limit = 10): array
    {
        try {
            $datasetUrl = config('apify.endpoints.amazon_dataset');
            $url = $datasetUrl . "?token=" . $this->apifyToken . "&limit=" . $limit;
            
            try {
                $options = $this->getProxyOptions();
                $response = $this->client->get($url, $options);
            } catch (\Exception $proxyError) {
                Log::warning('Proxy request failed, trying direct connection: ' . $proxyError->getMessage());
                $response = $this->client->get($url);
            }
            
            $data = json_decode($response->getBody()->getContents(), true);
            if (!$data || !is_array($data)) {
                throw new \Exception('Invalid response from Apify API');
            }
            return $this->processAndSaveProducts($data, 'amazon');
        } catch (\Exception $e) {
            Log::error('Apify API fetch failed: ' . $e->getMessage());
            throw $e;
        }
    }
    public function fetchFromJumiaApify(int $limit = 10): array
    {
        if ($this->jumiaApifyToken === '***' || empty($this->jumiaApifyToken)) {
            throw new \Exception('Jumia API token not configured');
        }
        try {
            $actorRunUrl = config('apify.endpoints.jumia_actor_run') . "?token=" . $this->jumiaApifyToken;
            
            try {
                $options = $this->getProxyOptions();
                $response = $this->client->get($actorRunUrl, $options);
            } catch (\Exception $proxyError) {
                Log::warning('Proxy request failed, trying direct connection: ' . $proxyError->getMessage());
                $response = $this->client->get($actorRunUrl);
            }
            
            $runData = json_decode($response->getBody()->getContents(), true);
            if (!isset($runData['data']['defaultDatasetId'])) {
                throw new \Exception('Could not find defaultDatasetId in Jumia actor run metadata');
            }
            
            $datasetId = $runData['data']['defaultDatasetId'];
            $datasetUrl = "https://api.apify.com/v2/datasets/{$datasetId}/items?token={$this->jumiaApifyToken}&limit={$limit}";
            
            try {
                $response = $this->client->get($datasetUrl, $options);
            } catch (\Exception $proxyError) {
                Log::warning('Proxy request failed, trying direct connection: ' . $proxyError->getMessage());
                $response = $this->client->get($datasetUrl);
            }
            
            $data = json_decode($response->getBody()->getContents(), true);
            if (!$data || !is_array($data)) {
                throw new \Exception('Invalid response from Jumia Apify dataset API');
            }
            return $this->processAndSaveProducts($data, 'jumia');
        } catch (\Exception $e) {
            Log::error('Jumia Apify API fetch failed: ' . $e->getMessage());
            throw $e;
        }
    }
    public function fetchFromBothApis(?int $amazonLimit = null, ?int $jumiaLimit = null): array
    {
        $amazonLimit = $amazonLimit ?? 10;
        $jumiaLimit = $jumiaLimit ?? 10;
        
        $allProducts = [];
        $errors = [];
        
        try {
            $amazonProducts = $this->fetchFromApify($amazonLimit);
            $allProducts = array_merge($allProducts, $amazonProducts);
        } catch (\Exception $e) {
            $errors[] = 'Amazon API: ' . $e->getMessage();
        }
        
        try {
            $jumiaProducts = $this->fetchFromJumiaApify($jumiaLimit);
            $allProducts = array_merge($allProducts, $jumiaProducts);
        } catch (\Exception $e) {
            $errors[] = 'Jumia API: ' . $e->getMessage();
        }
        
        return [
            'success' => !empty($allProducts),
            'products' => $allProducts,
            'total_count' => count($allProducts),
            'amazon_count' => count(array_filter($allProducts, fn($p) => $p->platform === 'amazon')),
            'jumia_count' => count(array_filter($allProducts, fn($p) => $p->platform === 'jumia')),
            'limits_used' => [
                'amazon' => $amazonLimit,
                'jumia' => $jumiaLimit
            ],
            'errors' => $errors
        ];
    }
    private function processAndSaveProducts(array $data, string $platform): array
    {
        $savedProducts = [];
        
        foreach ($data as $productData) {
            try {
                $processedData = $platform === 'jumia' 
                    ? $this->processJumiaApifyProduct($productData)
                    : $this->processApifyProduct($productData, $platform);
                
                if ($processedData) {
                    $product = $this->saveProduct($processedData);
                    $savedProducts[] = $product;
                }
            } catch (\Exception $e) {
                Log::error("Failed to process {$platform} product: " . $e->getMessage());
                continue;
            }
        }
        
        return $savedProducts;
    }
    private function processJumiaApifyProduct(array $productData): ?array
    {
        if (isset($productData['product'])) {
            $productData = $productData['product'];
        }
        
        $title = $productData['name'] ?? $productData['displayName'] ?? $productData['title'] ?? '';
        $price = $this->extractPrice($productData);
        $imageUrl = $this->extractImageUrl($productData);
        
        if (empty($title)) {
            return null;
        }
        
        return [
            'title' => $title,
            'price' => $price,
            'image_url' => $imageUrl,
            'platform' => 'jumia',
            'source_url' => $productData['url'] ?? ''
        ];
    }
    private function processApifyProduct(array $productData, string $platform = 'amazon'): ?array
    {
        $title = $productData['title'] ?? $productData['name'] ?? '';
        $price = $this->extractPrice($productData);
        $imageUrl = $this->extractImageUrl($productData);
        
        if (empty($title)) {
            return null;
        }
        
        return [
            'title' => $title,
            'price' => $price,
            'image_url' => $imageUrl,
            'platform' => $platform,
            'source_url' => $productData['url'] ?? ''
        ];
    }
    private function extractPrice(array $productData): float
    {
        if (isset($productData['prices'])) {
            if (isset($productData['prices']['rawPrice'])) {
                return (float) $productData['prices']['rawPrice'];
            }
            if (isset($productData['prices']['price'])) {
                return (float) preg_replace('/[^0-9.]/', '', $productData['prices']['price']);
            }
        }
        
        if (isset($productData['price'])) {
            if (is_array($productData['price'])) {
                return (float) ($productData['price']['value'] ?? 0);
            }
            return (float) preg_replace('/[^0-9.]/', '', $productData['price']);
        }
        
        return 0.0;
    }
    private function extractImageUrl(array $productData): string
    {
        $imageFields = [
            'highResolutionImages' => 0,
            'galleryThumbnails' => 0,
            'thumbnailImage' => null,
            'images' => 0,
            'image' => null,
            'img' => null,
            'imageUrl' => null,
            'image_url' => null
        ];
        
        foreach ($imageFields as $field => $index) {
            if (!isset($productData[$field])) {
                continue;
            }
            
            $value = $productData[$field];
            
            if (is_array($value)) {
                if ($index !== null && isset($value[$index])) {
                    $item = $value[$index];
                    if (is_array($item)) {
                        return $item['url'] ?? $item['src'] ?? '';
                    }
                    return (string) $item;
                }
                if (!empty($value)) {
                    $first = $value[0];
                    if (is_array($first)) {
                        return $first['url'] ?? $first['src'] ?? '';
                    }
                    return (string) $first;
                }
            } else {
                return (string) $value;
            }
        }
        
        return '';
    }
    public function scrapeAmazonProduct(string $url): ?array
    {
        try {
            $html = $this->fetchPage($url);
            return $this->parseAmazonProduct($html, $url);
        } catch (\Exception $e) {
            Log::error('Amazon scraping failed: ' . $e->getMessage());
            return null;
        }
    }
    public function scrapeJumiaProduct(string $url): ?array
    {
        try {
            $html = $this->fetchPage($url);
            return $this->parseJumiaProduct($html, $url);
        } catch (\Exception $e) {
            Log::error('Jumia scraping failed: ' . $e->getMessage());
            return null;
        }
    }
    public function scrapeProduct(string $url): ?array
    {
        if (str_contains($url, 'amazon')) {
            return $this->scrapeAmazonProduct($url);
        }
        
        if (str_contains($url, 'jumia')) {
            return $this->scrapeJumiaProduct($url);
        }
        
        throw new \Exception('Unsupported eCommerce site');
    }
    private function fetchPage(string $url): string
    {
        $userAgent = $this->userAgents[array_rand($this->userAgents)];
        
        $options = [
            "headers" => ["User-Agent" => $userAgent],
            "timeout" => 30,
            "verify" => false,
        ];
        
        try {
            $proxyOptions = $this->getProxyOptions();
            $options = array_merge($options, $proxyOptions);
        } catch (\Exception $e) {
            Log::warning("Failed to get proxy from service: " . $e->getMessage());
        }
        
        try {
            $response = $this->client->get($url, $options);
        } catch (\Exception $proxyError) {
            Log::warning('Proxy request failed, trying direct connection: ' . $proxyError->getMessage());
            $options = [
                "headers" => ["User-Agent" => $userAgent],
                "timeout" => 30,
                "verify" => false,
            ];
            $response = $this->client->get($url, $options);
        }
        
        return $response->getBody()->getContents();
    }
    private function getProxyOptions(): array
    {
        $options = [];
        try {
            $proxy = $this->getProxyFromService();
            if ($proxy && !empty($proxy["host"]) && !str_contains($proxy["host"], 'example.com')) {
                $proxyUrl = $proxy["protocol"] . "://" . $proxy["host"] . ":" . $proxy["port"];
                if (!empty($proxy["username"]) && !empty($proxy["password"])) {
                    $proxyUrl = $proxy["protocol"] . "://" . $proxy["username"] . ":" . $proxy["password"] . "@" . $proxy["host"] . ":" . $proxy["port"];
                }
                $options["proxy"] = $proxyUrl;
            }
        } catch (\Exception $e) {}
        return $options;
    }
    private function getProxyFromService(): ?array
    {
        try {
            $response = $this->client->get("http://localhost:8080/proxy");
            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($data["success"] && isset($data["data"])) {
                return $data["data"];
            }
        } catch (\Exception $e) {
            Log::warning("Failed to get proxy from service: " . $e->getMessage());
        }
        
        return null;
    }
    private function parseAmazonProduct(string $html, string $url): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $titleNode = $xpath->query('//span[@id="productTitle"]')->item(0);
        $title = $titleNode ? trim($titleNode->textContent) : '';

        $priceNode = $xpath->query('//span[@class="a-price-whole"]')->item(0);
        $price = $priceNode ? (float) str_replace(',', '', $priceNode->textContent) : 0;

        $imageNode = $xpath->query('//img[@id="landingImage"]')->item(0);
        $imageUrl = $imageNode ? $imageNode->getAttribute('src') : '';

        if (empty($title)) {
            throw new \Exception('Could not extract product title from Amazon page');
        }

        return [
            'title' => $title,
            'price' => $price,
            'image_url' => $imageUrl,
            'platform' => 'amazon',
            'source_url' => $url
        ];
    }
    private function parseJumiaProduct(string $html, string $url): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $titleNode = $xpath->query('//h1[@class="-fs20 -pts -pbxs"]')->item(0);
        $title = $titleNode ? trim($titleNode->textContent) : '';

        $priceNode = $xpath->query('//span[@class="-b -ltr -tal -fs24"]')->item(0);
        $price = $priceNode ? (float) preg_replace('/[^0-9.]/', '', $priceNode->textContent) : 0;

        $imageNode = $xpath->query('//img[@class="-fw -fh"]')->item(0);
        $imageUrl = $imageNode ? $imageNode->getAttribute('src') : '';

        if (empty($title)) {
            throw new \Exception('Could not extract product title from Jumia page');
        }

        return [
            'title' => $title,
            'price' => $price,
            'image_url' => $imageUrl,
            'platform' => 'jumia',
            'source_url' => $url
        ];
    }
    public function saveProduct(array $productData): Product
    {
        try {
            $product = Product::create([
                'title' => $productData['title'],
                'price' => $productData['price'],
                'image_url' => $productData['image_url'],
                'platform' => $productData['platform'] ?? 'amazon',
            ]);
            
            $this->clearProductCaches();
            
            return $product;
        } catch (\Exception $e) {
            Log::error('Failed to save product: ' . $e->getMessage());
            throw $e;
        }
    }
    public function scrapeAndSave(string $url): ?Product
    {
        $productData = $this->scrapeProduct($url);
        if (!$productData) {
            return null;
        }
        
        if (str_contains($url, 'amazon')) {
            $productData['platform'] = 'amazon';
        } elseif (str_contains($url, 'jumia')) {
            $productData['platform'] = 'jumia';
        }
        
        return $this->saveProduct($productData);
    }
    public function getAllProducts(int $perPage = 10)
    {
        $cacheKey = "products_all_{$perPage}_" . request()->get('page', 1);
        
        return Cache::remember($cacheKey, 300, function () use ($perPage) {
            return Product::orderBy('created_at', 'desc')->paginate($perPage);
        });
    }
    
    public function getUnifiedProducts(int $perPage = 10, ?string $platform = null)
    {
        $page = request()->get('page', 1);
        $cacheKey = "products_unified_{$perPage}_{$platform}_{$page}";
        
        return Cache::remember($cacheKey, 300, function () use ($perPage, $platform) {
            if ($platform) {
                return Product::where('platform', $platform)
                             ->orderBy('created_at', 'desc')
                             ->paginate($perPage);
            }
            
            $amazonPerPage = ceil($perPage / 2);
            $jumiaPerPage = $perPage - $amazonPerPage;
            
            $amazonProducts = Product::where('platform', 'amazon')
                                    ->orderBy('created_at', 'desc')
                                    ->limit($amazonPerPage)
                                    ->get();
            
            $jumiaProducts = Product::where('platform', 'jumia')
                                   ->orderBy('created_at', 'desc')
                                   ->limit($jumiaPerPage)
                                   ->get();
            
            $combinedProducts = $amazonProducts->concat($jumiaProducts)
                                             ->sortByDesc('created_at')
                                             ->values();
            
            $currentPage = request()->get('page', 1);
            $offset = ($currentPage - 1) * $perPage;
            $items = $combinedProducts->slice($offset, $perPage);
            
            $total = Product::count();
            
            return new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                [
                    'path' => request()->url(),
                    'pageName' => 'page',
                ]
            );
        });
    }
    
    public function getProductsByPlatform(string $platform, int $perPage = 15)
    {
        $page = request()->get('page', 1);
        $cacheKey = "products_platform_{$platform}_{$perPage}_{$page}";
        
        return Cache::remember($cacheKey, 300, function () use ($platform, $perPage) {
            return Product::where('platform', $platform)
                         ->orderBy('created_at', 'desc')
                         ->paginate($perPage);
        });
    }
    
    public function getProductById(int $id): ?Product
    {
        $cacheKey = "product_{$id}";
        
        return Cache::remember($cacheKey, 600, function () use ($id) {
            return Product::find($id);
        });
    }
    
    public function updateProduct(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        
        $product->update($data);
        
        $this->clearProductCaches();
        
        return $product->fresh();
    }
    
    public function deleteProduct(int $id): bool
    {
        $product = Product::findOrFail($id);
        
        $deleted = $product->delete();
        
        if ($deleted) {
            $this->clearProductCaches();
        }
        
        return $deleted;
    }
    
    private function clearProductCaches(): void
    {
        Cache::flush();
    }
}
