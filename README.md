# nfx MAGIC Analytics for Shopware 6

✨ **Modern Analytics Plugin for Shopware 6.6.x**

![Version](https://img.shields.io/badge/version-0.0.1-blue.svg)
![Shopware](https://img.shields.io/badge/Shopware-6.6.x-189EFF.svg)
![License](https://img.shields.io/badge/license-proprietary-red.svg)

nfx MAGIC Analytics is a comprehensive analytics and statistics plugin for Shopware 6, providing over 80 different statistics and insights for your e-commerce business. Built with modern design principles and featuring multiple color themes.

## 🎨 Features

- **80+ Statistics**: Comprehensive analytics covering sales, customers, products, and more
- **Modern UI**: Clean, responsive interface with 4 beautiful color themes
- **Real-time Data**: Live statistics with customizable date ranges
- **Advanced Filtering**: Filter by sales channels, customer groups, products, and more
- **CSV Export**: Export any statistic to CSV for further analysis
- **Performance Optimized**: Efficient queries and caching for large datasets
- **Multi-language**: Supports German (de-DE) and English (en-GB)

## 🎨 Color Themes

1. **Light Apple** - Clean, minimalist design inspired by Apple
2. **Dark Violet** - Modern dark theme with purple accents
3. **Pastel** - Soft, friendly colors for comfortable viewing
4. **90s Retro** - Nostalgic theme with vibrant colors

## 📋 Requirements

- Shopware 6.6.x (>=6.6.0, <6.7)
- PHP 8.2 or higher
- MySQL 5.7.21 or higher / MariaDB 10.3 or higher

## 🚀 Installation

### Via Composer (Recommended)

```bash
composer require nfxmedia/magic-analytics
bin/console plugin:refresh
bin/console plugin:install --activate NfxMagicAnalytics
bin/console cache:clear
```

### Manual Installation

1. Download the plugin
2. Extract to `custom/plugins/NfxMagicAnalytics`
3. Install via CLI:

```bash
bin/console plugin:refresh
bin/console plugin:install --activate NfxMagicAnalytics
bin/console cache:clear
```

## 🐳 Docker Development Setup

A Docker environment is included for easy development:

```bash
# Start the development environment
docker-compose up -d

# Install the plugin
docker exec -it shopware bash
cd /var/www/html
bin/console plugin:refresh
bin/console plugin:install --activate NfxMagicAnalytics
```

Access Shopware at: http://localhost

## 📊 Available Statistics Categories

### Sales & Revenue
- Revenue Overview
- Order Statistics
- Sales by Channel
- Payment Methods Analysis
- Shipping Methods Analysis
- Tax Analysis

### Customer Analytics
- Customer Overview
- New vs Returning Customers
- Customer Groups Analysis
- Customer Lifetime Value
- Geographic Distribution

### Product Performance
- Bestsellers
- Product Views
- Conversion Rates
- Stock Analysis
- Category Performance

### Marketing & Campaigns
- Campaign Performance
- Voucher Usage
- Affiliate Tracking
- Cross-selling Analysis

## 🛠️ Development

### Building Assets

```bash
# Build administration
./bin/build-administration.sh

# Build storefront (if needed)
./bin/build-storefront.sh
```

### Generating Test Data

The plugin includes commands for generating test data:

```bash
# Generate demo customers and products
bin/console store:demo-data

# Generate test orders
bin/console nfx:generate-simple-orders --orders=100
```

## 📁 Project Structure

```
NfxMagicAnalytics/
├── src/
│   ├── Bootstrap/          # Plugin initialization
│   ├── Components/         # Business logic
│   │   └── Statistics/     # Individual statistics
│   ├── Controller/         # API endpoints
│   ├── Resources/
│   │   ├── app/
│   │   │   └── administration/  # Vue.js admin UI
│   │   ├── config/             # Service definitions
│   │   └── snippet/            # Translations
│   └── NfxMagicAnalytics.php   # Plugin base class
├── composer.json
└── README.md
```

## 🔧 Configuration

Access plugin configuration in the admin panel under:
**Settings → System → Plugins → nfx MAGIC Analytics → Config**

### Available Settings
- Default date range
- Default sales channels
- Default customer groups
- Chart type preferences
- Display options

## 🌐 API Endpoints

All endpoints require authentication and use POST method:

```
POST /api/nfx/analytics/statistics-list
POST /api/nfx/analytics/statistics
POST /api/nfx/analytics/config
POST /api/nfx/analytics/data
```

## 🤝 Contributing

This is a proprietary plugin. For support or feature requests, please contact nfx:MEDIA.

## 📄 License

Copyright © 2024 nfx:MEDIA. All rights reserved.

This software is proprietary and confidential. Unauthorized copying, modification, distribution, or use of this software, via any medium, is strictly prohibited.

## 🆘 Support

- Documentation: [View Documentation](https://coolbax.gitbook.io/coolbax-docs/handbucher/administration/statistik-professionell)
- Issues: [GitHub Issues](https://github.com/nfxmedia/magic-analytics/issues)
- Contact: support@nfxmedia.de

## 🏆 Credits

Developed by [nfx:MEDIA](https://nfxmedia.de)

Based on the original CbaxModulAnalytics by Coolbax