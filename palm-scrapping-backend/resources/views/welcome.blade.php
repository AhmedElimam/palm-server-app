<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Product Management Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Product Management Dashboard</h1>
            <p class="text-gray-600">Manage your scraped products with ease</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-box text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Products</p>
                        <p class="text-2xl font-semibold text-gray-900" id="totalProducts">-</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fab fa-amazon text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Amazon Products</p>
                        <p class="text-2xl font-semibold text-gray-900" id="amazonProducts">-</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-shopping-cart text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Jumia Products</p>
                        <p class="text-2xl font-semibold text-gray-900" id="jumiaProducts">-</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex flex-wrap gap-4">
                <button onclick="openAddModal()" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Add New Product
                </button>
                <button onclick="openBulkScrapeModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg flex items-center">
                    <i class="fas fa-download mr-2"></i>
                    Bulk Scrape
                </button>
                <button onclick="fetchFromApify()" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-3 rounded-lg flex items-center">
                    <i class="fab fa-amazon mr-2"></i>
                    Fetch from Apify
                </button>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex flex-wrap gap-4 items-center">
                <div class="flex-1 min-w-64">
                    <input type="text" id="searchInput" placeholder="Search products..." 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <select id="platformFilter" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">All Platforms</option>
                    <option value="amazon">Amazon</option>
                    <option value="jumia">Jumia</option>
                </select>
                <button onclick="loadProducts()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Products Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Products</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">Loading products...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="pagination" class="px-6 py-4 border-t border-gray-200"></div>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800" id="modalTitle">Add New Product</h3>
                </div>
                <form id="productForm" class="p-6">
                    <input type="hidden" id="productId">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                        <input type="text" id="productTitle" required 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price</label>
                        <input type="number" id="productPrice" step="0.01" min="0" required 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Image URL</label>
                        <input type="url" id="productImageUrl" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Platform</label>
                        <select id="productPlatform" required 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="amazon">Amazon</option>
                            <option value="jumia">Jumia</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeModal()" 
                                class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                            Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Scrape Modal -->
    <div id="bulkScrapeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Bulk Product Scraping</h3>
                </div>
                <div class="p-6">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Product URLs (one per line)</label>
                        <textarea id="bulkUrls" rows="6" placeholder="https://www.amazon.com/dp/...&#10;https://www.jumia.com.ng/..." 
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button onclick="closeBulkModal()" 
                                class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button onclick="bulkScrape()" 
                                class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                            Start Scraping
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Apify Fetch Modal -->
    <div id="apifyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Fetch from Apify</h3>
                </div>
                <div class="p-6">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Number of Products</label>
                        <input type="number" id="apifyLimit" value="10" min="1" max="100" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div class="flex justify-end gap-3">
                        <button onclick="closeApifyModal()" 
                                class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button onclick="confirmApifyFetch()" 
                                class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600">
                            Fetch Products
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let currentPage = 1;
        let currentPlatform = '';
        let currentSearch = '';

        document.addEventListener('DOMContentLoaded', () => {
            loadProducts();
            loadStats();
        });

        document.getElementById('searchInput').addEventListener('input', debounce(() => {
            currentSearch = document.getElementById('searchInput').value;
            currentPage = 1;
            loadProducts();
        }, 300));

        document.getElementById('platformFilter').addEventListener('change', () => {
            currentPlatform = document.getElementById('platformFilter').value;
            currentPage = 1;
            loadProducts();
        });

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        async function loadStats() {
            try {
                const response = await fetch('/api/products');
                const data = await response.json();
                
                if (data.success) {
                    const products = data.data;
                    const total = products.length;
                    const amazon = products.filter(p => p.platform === 'amazon').length;
                    const jumia = products.filter(p => p.platform === 'jumia').length;
                    
                    document.getElementById('totalProducts').textContent = total;
                    document.getElementById('amazonProducts').textContent = amazon;
                    document.getElementById('jumiaProducts').textContent = jumia;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        async function loadProducts() {
            const tableBody = document.getElementById('productsTableBody');
            tableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Loading products...</td></tr>';
            
            try {
                let url = `/api/products?page=${currentPage}`;
                if (currentPlatform) url += `&platform=${currentPlatform}`;
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    const productsHtml = data.data.map(product => `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <img src="${product.image_url || '/placeholder-image.jpg'}" alt="${product.title}" 
                                         class="w-12 h-12 rounded-lg object-cover mr-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">${product.title}</div>
                                        <div class="text-sm text-gray-500">ID: ${product.id}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-lg font-semibold text-green-600">$${product.price}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                    product.platform === 'amazon' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'
                                }">
                                    <i class="fab fa-${product.platform === 'amazon' ? 'amazon' : 'shopping-cart'} mr-1"></i>
                                    ${product.platform}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                ${new Date(product.created_at).toLocaleDateString()}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <button onclick="editProduct(${product.id})" 
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteProduct(${product.id})" 
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                    
                    tableBody.innerHTML = productsHtml;
                    
                    if (data.meta && data.meta.last_page > 1) {
                        renderPagination(data.meta);
                    } else {
                        document.getElementById('pagination').innerHTML = '';
                    }
                } else {
                    tableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No products found</td></tr>';
                    document.getElementById('pagination').innerHTML = '';
                }
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Error loading products: ${error.message}</td></tr>`;
            }
        }

        function renderPagination(meta) {
            const pagination = document.getElementById('pagination');
            let paginationHtml = '<div class="flex items-center justify-between">';
            
            if (meta.current_page > 1) {
                paginationHtml += `<button onclick="changePage(${meta.current_page - 1})" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Previous</button>`;
            }
            
            paginationHtml += '<div class="flex space-x-1">';
            for (let i = 1; i <= meta.last_page; i++) {
                if (i === meta.current_page) {
                    paginationHtml += `<span class="px-3 py-2 text-sm bg-blue-500 text-white rounded">${i}</span>`;
                } else {
                    paginationHtml += `<button onclick="changePage(${i})" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">${i}</button>`;
                }
            }
            paginationHtml += '</div>';
            
            if (meta.current_page < meta.last_page) {
                paginationHtml += `<button onclick="changePage(${meta.current_page + 1})" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Next</button>`;
            }
            
            paginationHtml += '</div>';
            pagination.innerHTML = paginationHtml;
        }

        function changePage(page) {
            currentPage = page;
            loadProducts();
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('productModal').classList.remove('hidden');
        }

        function openEditModal(product) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('productId').value = product.id;
            document.getElementById('productTitle').value = product.title;
            document.getElementById('productPrice').value = product.price;
            document.getElementById('productImageUrl').value = product.image_url || '';
            document.getElementById('productPlatform').value = product.platform;
            document.getElementById('productModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
        }

        function openBulkScrapeModal() {
            document.getElementById('bulkScrapeModal').classList.remove('hidden');
        }

        function closeBulkModal() {
            document.getElementById('bulkScrapeModal').classList.add('hidden');
        }

        function openApifyModal() {
            document.getElementById('apifyModal').classList.remove('hidden');
        }

        function closeApifyModal() {
            document.getElementById('apifyModal').classList.add('hidden');
        }

        async function editProduct(id) {
            try {
                const response = await fetch(`/api/products/${id}`);
                const data = await response.json();
                
                if (data.success) {
                    openEditModal(data.data);
                } else {
                    alert('Error loading product: ' + data.message);
                }
            } catch (error) {
                alert('Error loading product: ' + error.message);
            }
        }

        async function deleteProduct(id) {
            if (!confirm('Are you sure you want to delete this product?')) {
                return;
            }
            
            try {
                const response = await fetch(`/api/products/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadProducts();
                    loadStats();
                    alert('Product deleted successfully');
                } else {
                    alert('Error deleting product: ' + data.message);
                }
            } catch (error) {
                alert('Error deleting product: ' + error.message);
            }
        }

        document.getElementById('productForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const productId = document.getElementById('productId').value;
            const formData = {
                title: document.getElementById('productTitle').value,
                price: parseFloat(document.getElementById('productPrice').value),
                image_url: document.getElementById('productImageUrl').value,
                platform: document.getElementById('productPlatform').value
            };
            
            try {
                const url = productId ? `/api/products/${productId}` : '/api/products';
                const method = productId ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    closeModal();
                    loadProducts();
                    loadStats();
                    alert(productId ? 'Product updated successfully' : 'Product created successfully');
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });

        async function bulkScrape() {
            const urls = document.getElementById('bulkUrls').value.split('\n').filter(url => url.trim());
            
            if (urls.length === 0) {
                alert('Please enter at least one URL');
                return;
            }
            
            try {
                const response = await fetch('/api/products/scrape-multiple', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ urls: urls })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    closeBulkModal();
                    loadProducts();
                    loadStats();
                    alert(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        function fetchFromApify() {
            openApifyModal();
        }

        async function confirmApifyFetch() {
            const limit = document.getElementById('apifyLimit').value;
            
            try {
                const response = await fetch('/api/products/fetch-apify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ limit: parseInt(limit) })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    closeApifyModal();
                    loadProducts();
                    loadStats();
                    alert(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
    </script>
</body>
</html>