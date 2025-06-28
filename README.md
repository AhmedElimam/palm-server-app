# 🚀 Palm Scraping Project - Full Stack E-commerce Data Pipeline

A comprehensive web scraping and data management system that extracts product information from multiple e-commerce platforms and presents it through a modern web interface. Built with Laravel, Next.js, and Go microservices.

## 🏗️ Architecture Overview

This project follows a **microservices architecture** with three main components working together:

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend       │    │   Proxy Service │
│   (Next.js)     │◄──►│   (Laravel)     │◄──►│   (Go)          │
│   Port: 3000    │    │   Port: 8000    │    │   Port: 8080    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │                       ▼                       │
         │              ┌─────────────────┐              │
         └──────────────►│   MySQL DB      │◄─────────────┘
                        │   Port: 3306    │
                        └─────────────────┘
```

## 🎯 How It Works

### 1. **Data Collection Pipeline**
The system fetches product data from multiple sources:

- **Apify API Integration**: Connects to Apify's scraping services for Amazon and Jumia
- **Direct Web Scraping**: Uses Guzzle HTTP client with rotating user agents
- **Proxy Management**: Go microservice handles proxy rotation and management

### 2. **Data Processing**
- **Laravel Backend**: Processes and normalizes data from different sources
- **Database Storage**: Stores products with platform identification
- **API Endpoints**: RESTful API for data retrieval and management

### 3. **Frontend Presentation**
- **Next.js App**: Modern React-based interface with TypeScript
- **Real-time Updates**: 30-second automatic refresh cycle
- **Responsive Design**: Mobile-first approach with Tailwind CSS

## 🛠️ Technology Stack

### Backend (Laravel 12)
- **Framework**: Laravel 12 with PHP 8.2+
- **Database**: MySQL with Eloquent ORM
- **HTTP Client**: Guzzle for web scraping
- **API**: RESTful endpoints with JSON responses
- **Authentication**: Laravel Sanctum (ready for implementation)

### Frontend (Next.js 15)
- **Framework**: Next.js 15 with App Router
- **Language**: TypeScript for type safety
- **Styling**: Tailwind CSS 4
- **State Management**: React Context API
- **HTTP Client**: Native fetch with custom API client

### Proxy Service (Go)
- **Language**: Go 1.22.3
- **Framework**: Gin for HTTP routing
- **Features**: Proxy rotation, health checks, statistics
- **Storage**: In-memory with persistence capabilities

### Infrastructure
- **Containerization**: Docker-ready setup
- **Process Management**: Custom shell scripts
- **Development**: Hot reloading and development tools

## 📁 Project Structure

```
delivery/
├── palm-scrapping-backend/          # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/         # API endpoints
│   │   │   ├── Services/           # Business logic
│   │   │   └── Resources/          # API responses
│   │   └── Models/                 # Database models
│   ├── database/migrations/        # Database schema
│   ├── routes/api.php             # API routes
│   └── config/                    # Configuration files
├── palm-scrapping-frontend/        # Next.js app
│   ├── src/
│   │   ├── app/                   # Next.js app router
│   │   ├── components/            # React components
│   │   ├── context/               # State management
│   │   ├── hooks/                 # Custom hooks
│   │   ├── lib/                   # Utilities
│   │   └── types/                 # TypeScript definitions
│   └── public/                    # Static assets
├── proxy-service/                  # Go microservice
│   ├── internal/
│   │   ├── handlers/              # HTTP handlers
│   │   ├── proxy/                 # Proxy management
│   │   └── storage/               # Data storage
│   └── config/                    # Service configuration
└── start-services.sh              # Development orchestration
```

## 🚀 Getting Started

### Prerequisites
- **Node.js** 18+ and npm
- **PHP** 8.2+ and Composer
- **Go** 1.22+
- **MySQL** 5.7+
- **Git**

### Quick Start
1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd delivery
   ```

2. **Run the setup script**
   ```bash
   chmod +x start-services.sh
   ./start-services.sh
   ```

3. **Access the application**
   - Frontend: http://localhost:3000
   - Backend API: http://localhost:8000
   - Proxy Service: http://localhost:8080

### Manual Setup (Alternative)

#### Backend Setup
```bash
cd palm-scrapping-backend
composer install
cp .env.example .env
# Configure database in .env
php artisan migrate
php artisan serve
```

#### Frontend Setup
```bash
cd palm-scrapping-frontend
npm install
npm run dev
```

#### Proxy Service Setup
```bash
cd proxy-service
go mod download
go run main.go
```

## 🔧 Configuration

### Environment Variables

#### Backend (.env)
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=palm_scraping
DB_USERNAME=root
DB_PASSWORD=

APIFY_AMAZON_TOKEN=your_amazon_token
APIFY_JUMIA_TOKEN=your_jumia_token
```

#### Frontend (.env.local)
```env
NEXT_PUBLIC_API_URL=http://localhost:8000
```

#### Proxy Service (config/config.go)
```go
BaseURL: "http://localhost:8080"
Port: "8080"
MaxRetries: 3
RotationDelay: 5
```

## 📊 API Endpoints

### Products API
- `GET /api/products` - Get all products with pagination
- `GET /api/products/{id}` - Get single product
- `GET /api/products/platform/{platform}` - Get products by platform
- `POST /api/products/scrape` - Scrape single product URL
- `POST /api/products/scrape-multiple` - Scrape multiple URLs
- `GET /api/products/fetch-apify` - Fetch from Apify API
- `GET /api/products/fetch-both-apis` - Fetch from both platforms

### Proxy Service API
- `GET /health` - Health check
- `GET /proxy` - Get current proxy
- `GET /proxy/rotate` - Rotate to next proxy
- `GET /proxy/list` - List all proxies
- `POST /proxy/add` - Add new proxy
- `DELETE /proxy/{id}` - Remove proxy
- `GET /proxy/stats` - Get service statistics

## 🔄 Data Flow

1. **Initialization**: Frontend loads and fetches initial data from both platforms
2. **Auto-refresh**: Every 30 seconds, new data is fetched automatically
3. **User Interaction**: Users can search, filter, and paginate through products
4. **Manual Scraping**: Admins can trigger manual scraping of specific URLs
5. **Proxy Rotation**: Go service manages proxy rotation for scraping requests

## 🎨 Features

### Frontend Features
- **Responsive Grid Layout**: Beautiful product cards with images
- **Real-time Search**: Instant filtering by title, price, or ID
- **Infinite Scroll**: Load more products as you scroll
- **Platform Filtering**: Filter by Amazon or Jumia
- **Auto-refresh**: Automatic data updates every 30 seconds
- **Loading States**: Smooth loading indicators
- **Error Handling**: Graceful error messages

### Backend Features
- **Multi-platform Support**: Amazon and Jumia integration
- **User Agent Rotation**: Prevents detection during scraping
- **Error Resilience**: Fallback mechanisms and retry logic
- **Data Normalization**: Consistent data structure across platforms
- **API Rate Limiting**: Built-in protection against abuse
- **Comprehensive Logging**: Detailed error and activity logs

### Proxy Service Features
- **Dynamic Proxy Management**: Add/remove proxies on the fly
- **Health Monitoring**: Track proxy performance and failures
- **Automatic Rotation**: Intelligent proxy selection
- **Statistics**: Monitor usage and performance metrics
- **RESTful API**: Easy integration with other services

## 🧪 Testing

### Backend Testing
```bash
cd palm-scrapping-backend
php artisan test
```

### API Testing
```bash
cd palm-scrapping-backend
php test_api.php
```

### Frontend Testing
```bash
cd palm-scrapping-frontend
npm run test
```

## 🚀 Deployment

### Production Setup
1. **Environment Configuration**: Set production environment variables
2. **Database Migration**: Run migrations on production database
3. **Asset Compilation**: Build frontend assets
4. **Service Deployment**: Deploy each service to production servers
5. **Load Balancer**: Configure reverse proxy for multiple services

### Docker Deployment
```bash
# Build images
docker build -t palm-backend ./palm-scrapping-backend
docker build -t palm-frontend ./palm-scrapping-frontend
docker build -t palm-proxy ./proxy-service

# Run containers
docker-compose up -d
```

## 🔍 Monitoring & Logging

### Application Logs
- **Laravel Logs**: `storage/logs/laravel.log`
- **Frontend Logs**: Browser console and network tab
- **Proxy Logs**: Service logs in Go

### Health Checks
- Backend: `GET /api/health`
- Proxy Service: `GET /health`
- Frontend: Built-in Next.js health monitoring

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Troubleshooting

### Common Issues

**Backend won't start**
- Check PHP version (8.2+ required)
- Verify database connection
- Run `composer install`

**Frontend build fails**
- Clear Next.js cache: `rm -rf .next`
- Reinstall dependencies: `npm install`

**Proxy service errors**
- Check Go version (1.22+ required)
- Verify port availability
- Check configuration file

**Database connection issues**
- Verify MySQL is running
- Check credentials in `.env`
- Run migrations: `php artisan migrate`

## 📞 Support

For questions or issues:
- Create an issue in the repository
- Check the troubleshooting section
- Review the API documentation

---

**Built with ❤️ using modern web technologies** 