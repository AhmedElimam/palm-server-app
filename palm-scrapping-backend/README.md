# Product Scraping System

A comprehensive Laravel-based product scraping system that can extract product information from eCommerce websites using both direct scraping and the Apify API, storing the data in a MySQL database.

## 🚀 Features

- **Product Model**: Complete Product model with fields: `id`, `title`, `price`, `image_url`, and `created_at`
- **Apify Integration**: Fetch products from Apify API with your provided token
- **Direct Scraping**: Intelligent scraping service using Guzzle HTTP client
- **User Agent Rotation**: Rotates between different user-agent headers to mimic proxy rotation
- **Multi-site Support**: Supports Amazon and Jumia product pages
- **Database Storage**: Stores scraped data in MySQL database
- **RESTful API**: Complete API endpoints for scraping and retrieving products
- **Web Interface**: Beautiful, modern UI for easy interaction
- **Error Handling**: Comprehensive error handling and logging
- **Command Line Tools**: Artisan commands for testing and bulk operations

## 📋 Requirements

- PHP 8.1+
- Laravel 12
- MySQL 5.7+
- Composer
- Guzzle HTTP Client

## 🛠️ Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd palm-scrapping
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database in `.env`**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Start the development server**
   ```bash
   php artisan serve
   ```

## 🎯 Usage

### Web Interface

Visit `http://localhost:8000` to access the web interface with three main sections:

1. **Apify Integration**: Fetch products from your Apify dataset
2. **Manual Scraping**: Scrape individual products from URLs
3. **Products Database**: View all scraped products

### API Endpoints

#### Get All Products
```bash
GET /api/products
```

#### Get Single Product
```bash
GET /api/products/{id}
```

#### Fetch from Apify
```bash
POST /api/products/fetch-apify
Content-Type: application/json

{
    "limit": 10
}
```

#### Scrape Single Product
```bash
POST /api/products/scrape
Content-Type: application/json

{
    "url": "https://www.amazon.com/dp/B09G3HRMVB"
}
```

#### Scrape Multiple Products
```bash
POST /api/products/scrape-multiple
Content-Type: application/json

{
    "urls": [
        "https://www.amazon.com/dp/B09G3HRMVB",
        "https://www.jumia.com.ng/product-url"
    ]
}
```

### Command Line Tools

#### Test Apify Integration
```bash
php artisan test:scraping --apify --limit=5
```

This will fetch 5 products from your Apify dataset and save them to the database.

## 🏗️ System Architecture

### Models
- **Product**: Main model with fields: `id`, `title`, `price`, `image_url`, `created_at`, `updated_at`

### Services
- **ProductsServices**: Core service handling:
  - Apify API integration
  - Direct web scraping (Amazon, Jumia)
  - User agent rotation
  - Data processing and storage

### Controllers
- **ProductsController**: RESTful API endpoints for all product operations

### Database
- **products table**: Stores all scraped product data
- **Migration**: `2025_06_27_152247_create_products_table.php`

## 🔧 Configuration

### Apify Integration

The system is pre-configured with your Apify token:
- **Token**: `apify_api_zmVr5LMUhXGM0dhjBOGMBMCKSB6d1D3TnPvS`
- **Dataset ID**: `OsBT6oq7cNrLDjF3Y`
- **Actor Run ID**: `NDa0ZeGZBNNU8HpYg`

### User Agent Rotation

The system rotates between 6 different user agents to avoid detection:
- Chrome (Windows, Mac, Linux)
- Firefox (Windows, Mac)
- Edge (Windows)

## 📊 Data Structure

### Product Model
```php
{
    "id": 1,
    "title": "Product Name",
    "price": "29.99",
    "image_url": "https://example.com/image.jpg",
    "created_at": "2025-06-27T15:39:29.000000Z",
    "updated_at": "2025-06-27T15:39:29.000000Z"
}
```

### API Response Format
```json
{
    "success": true,
    "data": [...],
    "message": "Operation completed successfully"
}
```

## 🛡️ Error Handling

The system includes comprehensive error handling:
- **API Errors**: Proper HTTP status codes and error messages
- **Scraping Errors**: Graceful handling of failed scraping attempts
- **Database Errors**: Transaction rollback on failures
- **Logging**: All errors are logged to Laravel's log system

## 🔍 Supported Sites

### Currently Supported
- **Amazon**: Product pages with title, price, and image extraction
- **Jumia**: Product pages with title, price, and image extraction
- **Apify**: Any dataset with compatible product data structure

### Extending Support
To add support for new sites, modify the `ProductsServices` class:
1. Add new parsing methods
2. Update the `scrapeProduct()` method to detect the new site
3. Add appropriate selectors for title, price, and image

## 🚀 Performance Features

- **Concurrent Processing**: Multiple products can be processed simultaneously
- **Caching**: Database queries are optimized
- **Memory Management**: Efficient handling of large datasets
- **Timeout Handling**: Configurable timeouts for HTTP requests

## 📝 Logging

All operations are logged:
- **Info**: Successful operations
- **Error**: Failed operations with detailed error messages
- **Debug**: Detailed debugging information

View logs at: `storage/logs/laravel.log`

## 🔒 Security

- **Input Validation**: All inputs are validated
- **SQL Injection Protection**: Using Eloquent ORM
- **XSS Protection**: Output is properly escaped
- **CSRF Protection**: Web forms are protected

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🆘 Support

For support and questions:
1. Check the logs for error details
2. Review the API documentation
3. Test with the provided command line tools
4. Open an issue with detailed information

## 🎉 Success Stories

The system has successfully:
- ✅ Integrated with Apify API
- ✅ Scraped products from Amazon and Jumia
- ✅ Stored data in MySQL database
- ✅ Provided RESTful API endpoints
- ✅ Created a beautiful web interface
- ✅ Implemented comprehensive error handling

---

**Happy Scraping! 🚀**
