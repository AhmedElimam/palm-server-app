<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Product Scraping System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Product Scraping System</h1>
        
        <!-- Apify Integration Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Fetch Products from Apify</h2>
            <div class="flex gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Number of Products</label>
                    <input type="number" id="apifyLimit" value="5" min="1" max="50" class="border border-gray-300 rounded px-3 py-2 w-32">
                </div>
                <button onclick="fetchFromApify()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    Fetch from Apify
                </button>
            </div>
            <div id="apifyResult" class="mt-4"></div>
        </div>

        <!-- Manual Scraping Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Manual Product Scraping</h2>
            <div class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product URL</label>
                    <input type="url" id="scrapeUrl" placeholder="https://www.amazon.com/dp/..." class="border border-gray-300 rounded px-3 py-2 w-full">
                </div>
                <button onclick="scrapeProduct()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                    Scrape Product
                </button>
            </div>
            <div id="scrapeResult" class="mt-4"></div>
        </div>

        <!-- Products List -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-700">Products Database</h2>
                <button onclick="loadProducts()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    Refresh
                </button>
            </div>
            <div id="productsList" class="space-y-4">
                <div class="text-center text-gray-500">Loading products...</div>
            </div>
        </div>
    </div>

    <script>
        // Get CSRF token from meta tag
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Load products on page load
        document.addEventListener('DOMContentLoaded', loadProducts);

        async function fetchFromApify() {
            const limit = document.getElementById('apifyLimit').value;
            const resultDiv = document.getElementById('apifyResult');
            
            resultDiv.innerHTML = '<div class="text-blue-600">Fetching products from Apify...</div>';
            
            try {
                const response = await fetch('/api/products/fetch-apify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ limit: parseInt(limit) })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `<div class="text-green-600">✅ ${data.message} (${data.count} products)</div>`;
                    loadProducts(); // Refresh the products list
                } else {
                    resultDiv.innerHTML = `<div class="text-red-600">❌ ${data.message}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="text-red-600">❌ Error: ${error.message}</div>`;
            }
        }

        async function scrapeProduct() {
            const url = document.getElementById('scrapeUrl').value;
            const resultDiv = document.getElementById('scrapeResult');
            
            if (!url) {
                resultDiv.innerHTML = '<div class="text-red-600">Please enter a valid URL</div>';
                return;
            }
            
            resultDiv.innerHTML = '<div class="text-blue-600">Scraping product...</div>';
            
            try {
                const response = await fetch('/api/products/scrape', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ url: url })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `<div class="text-green-600">✅ ${data.message}</div>`;
                    loadProducts(); // Refresh the products list
                } else {
                    resultDiv.innerHTML = `<div class="text-red-600">❌ ${data.message}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="text-red-600">❌ Error: ${error.message}</div>`;
            }
        }

        async function loadProducts() {
            const productsDiv = document.getElementById('productsList');
            
            try {
                const response = await fetch('/api/products');
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    const productsHtml = data.data.map(product => `
                        <div class="border border-gray-200 rounded p-4">
                            <h3 class="font-semibold text-gray-800">${product.title}</h3>
                            <p class="text-green-600 font-medium">$${product.price}</p>
                            <p class="text-sm text-gray-500">ID: ${product.id} | Created: ${new Date(product.created_at).toLocaleString()}</p>
                        </div>
                    `).join('');
                    
                    productsDiv.innerHTML = productsHtml;
                } else {
                    productsDiv.innerHTML = '<div class="text-center text-gray-500">No products found in database</div>';
                }
            } catch (error) {
                productsDiv.innerHTML = `<div class="text-red-600">Error loading products: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>
