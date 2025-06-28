<?php
namespace App\Http\Controllers;
use App\Http\Services\ProductsServices;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
class ProductsController
{
    private ProductsServices $productsService;
    public function __construct(ProductsServices $productsService)
    {
        $this->productsService = $productsService;
    }
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $platform = $request->get('platform');
            
            $products = $this->productsService->getUnifiedProducts($perPage, $platform);
            
            return response()->json([
                'success' => true,
                'data' => ProductResource::collection($products),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'platform_filter' => $platform,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getByPlatform(Request $request, string $platform): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $products = $this->productsService->getProductsByPlatform($platform, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => ProductResource::collection($products),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'platform' => $platform,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products: ' . $e->getMessage()
            ], 500);
        }
    }
    public function show(int $id): JsonResponse
    {
        try {
            $product = $this->productsService->getProductById($id);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => new ProductResource($product)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product: ' . $e->getMessage()
            ], 500);
        }
    }
    public function fetchFromApify(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'integer|min:1|max:100'
        ]);
        try {
            $limit = $request->get('limit', 10);
            $products = $this->productsService->fetchFromApify($limit);
            return response()->json([
                'success' => true,
                'message' => 'Products fetched from Apify and saved successfully',
                'count' => count($products),
                'data' => ProductResource::collection($products)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch from Apify: ' . $e->getMessage()
            ], 500);
        }
    }
    public function fetchFromApifyGet(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'integer|min:1|max:100'
        ]);
        try {
            $limit = $request->get('limit', 10);
            $products = $this->productsService->fetchFromApify($limit);
            return response()->json([
                'success' => true,
                'message' => 'Products fetched from Apify and saved successfully',
                'count' => count($products),
                'data' => ProductResource::collection($products)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch from Apify: ' . $e->getMessage()
            ], 500);
        }
    }
    public function scrape(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url'
        ]);
        try {
            $product = $this->productsService->scrapeAndSave($request->url);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to scrape product from the provided URL'
                ], 400);
            }
            return response()->json([
                'success' => true,
                'message' => 'Product scraped and saved successfully',
                'data' => new ProductResource($product)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Scraping failed: ' . $e->getMessage()
            ], 500);
        }
    }
    public function scrapeMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'urls' => 'required|array',
            'urls.*' => 'url'
        ]);
        $results = [];
        $successCount = 0;
        $failureCount = 0;
        foreach ($request->urls as $url) {
            try {
                $product = $this->productsService->scrapeAndSave($url);
                if ($product) {
                    $results[] = [
                        'url' => $url,
                        'success' => true,
                        'product_id' => $product->id
                    ];
                    $successCount++;
                } else {
                    $results[] = [
                        'url' => $url,
                        'success' => false,
                        'message' => 'Failed to scrape product'
                    ];
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $results[] = [
                    'url' => $url,
                    'success' => false,
                    'message' => $e->getMessage()
                ];
                $failureCount++;
            }
        }
        return response()->json([
            'success' => true,
            'message' => "Scraping completed. Success: {$successCount}, Failures: {$failureCount}",
            'results' => $results
        ]);
    }
    public function fetchFromJumiaApify(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'integer|min:1|max:100'
        ]);
        try {
            $limit = $request->get('limit', 10);
            $products = $this->productsService->fetchFromJumiaApify($limit);
            return response()->json([
                'success' => true,
                'message' => 'Products fetched from Jumia Apify and saved successfully',
                'count' => count($products),
                'data' => ProductResource::collection($products)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch from Jumia Apify: ' . $e->getMessage()
            ], 500);
        }
    }
    public function fetchFromBothApis(Request $request): JsonResponse
    {
        $request->validate([
            'amazon_limit' => 'nullable|integer|min:1|max:100',
            'jumia_limit' => 'nullable|integer|min:1|max:100',
            'total_limit' => 'nullable|integer|min:1|max:200'
        ]);
        
        try {
            $amazonLimit = $request->get('amazon_limit');
            $jumiaLimit = $request->get('jumia_limit');
            $totalLimit = $request->get('total_limit');
            
            if ($totalLimit && !$amazonLimit && !$jumiaLimit) {
                $amazonLimit = ceil($totalLimit / 2);
                $jumiaLimit = $totalLimit - $amazonLimit;
            }
            
            if (!$amazonLimit && !$jumiaLimit) {
                $amazonLimit = 10;
                $jumiaLimit = 10;
            } elseif (!$amazonLimit) {
                $amazonLimit = 10;
            } elseif (!$jumiaLimit) {
                $jumiaLimit = 10;
            }
            
            $results = $this->productsService->fetchFromBothApis($amazonLimit, $jumiaLimit);
            
            $successMessage = "Products fetched successfully. ";
            $successMessage .= "Amazon: {$results['amazon_count']}/{$results['limits_used']['amazon']}, ";
            $successMessage .= "Jumia: {$results['jumia_count']}/{$results['limits_used']['jumia']}, ";
            $successMessage .= "Total: {$results['total_count']} products.";
            
            if (!empty($results['errors'])) {
                $successMessage .= " Errors: " . implode(', ', $results['errors']);
            }
            
            return response()->json([
                'success' => $results['success'],
                'message' => $successMessage,
                'data' => ProductResource::collection($results['products']),
                'meta' => [
                    'total_count' => $results['total_count'],
                    'amazon_count' => $results['amazon_count'],
                    'jumia_count' => $results['jumia_count'],
                    'limits_used' => $results['limits_used'],
                    'errors' => $results['errors']
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch from both APIs: ' . $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'image_url' => 'sometimes|url|nullable',
            'platform' => 'sometimes|string|max:50'
        ]);
        
        try {
            $product = $this->productsService->getProductById($id);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            
            $updatedProduct = $this->productsService->updateProduct($id, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => new ProductResource($updatedProduct)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product: ' . $e->getMessage()
            ], 500);
        }
    }
    public function destroy(int $id): JsonResponse
    {
        try {
            $product = $this->productsService->getProductById($id);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            
            $this->productsService->deleteProduct($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product: ' . $e->getMessage()
            ], 500);
        }
    }
}
