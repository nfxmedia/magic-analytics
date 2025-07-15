# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Shopware 6 plugin called "Cbax Modul Analytics" (Statistics Professional) that provides comprehensive e-commerce analytics. The plugin is at version 4.1.2 and requires Shopware 6.6.x. It offers 80+ different statistics types covering sales, customers, products, orders, marketing, and technical analytics.

## Development Commands

### Docker Environment
```bash
docker-compose up -d  # Start Shopware 6.6.10.5 development environment
```

### Plugin Management
```bash
# From Shopware root directory
./bin/console plugin:refresh                      # Refresh plugin list
./bin/console plugin:install CbaxModulAnalytics   # Install plugin
./bin/console plugin:activate CbaxModulAnalytics  # Activate plugin
./bin/console plugin:update CbaxModulAnalytics    # Update plugin
./bin/console plugin:uninstall CbaxModulAnalytics # Uninstall plugin
```

### Development Workflow
```bash
# Install dependencies (from plugin directory)
cd CbaxModulAnalytics && composer install

# Clear cache after changes (from Shopware root)
./bin/console cache:clear

# Run database migrations
./bin/console database:migrate --all CbaxModulAnalytics

# Build assets if needed
./bin/build-administration.sh
./bin/build-storefront.sh

# Check for linting/code style (if configured)
composer cs-fix    # Fix code style issues
composer phpstan   # Run static analysis
```

### Testing
No test suite is currently configured. When implementing tests:
- Unit tests would go in `/CbaxModulAnalytics/tests/Unit/`
- Integration tests in `/CbaxModulAnalytics/tests/Integration/`
- Use PHPUnit following Shopware standards

## Architecture Overview

### Plugin Structure
```
CbaxModulAnalytics/
├── src/
│   ├── Bootstrap/          # Plugin lifecycle (install, update, uninstall)
│   ├── Components/         # Core business logic
│   │   ├── Statistics/     # 80+ statistics implementations
│   │   ├── Base.php       # Base functionality
│   │   └── Helpers/       # Helper classes (KpiManager, Update, etc.)
│   ├── Controller/         # API controllers
│   │   ├── BackendController.php  # Main admin API
│   │   └── FrontendController.php # Storefront tracking
│   ├── Core/Content/       # Entity definitions for custom tables
│   ├── Extension/          # Extensions to core Shopware entities
│   ├── Migration/          # Database migrations (17 total)
│   ├── Resources/          
│   │   ├── app/
│   │   │   ├── administration/  # Vue.js admin UI
│   │   │   └── storefront/      # Frontend tracking JS
│   │   ├── config/             # services.xml, config.xml, routes.xml
│   │   └── views/              # Email templates
│   ├── ScheduledTask/      # Background analytics tasks
│   └── Subscriber/         # Event listeners
├── composer.json           # PHP dependencies (requires PHP 8.2)
└── CHANGELOG.md           # Detailed version history
```

### Key Architectural Patterns

1. **Statistics System**: All statistics implement `StatisticsInterface`:
   ```php
   public function getStatisticsData(array $parameters, Context $context): array
   ```
   Parameters typically include: `selectedPeriod`, `dateFrom`, `dateTo`, `selectedSalesChannel`

2. **Service Architecture**: Uses Symfony DI with services in `services.xml`
   - Services are tagged for auto-discovery
   - Constructor injection for dependencies

3. **Entity-Repository Pattern**: 
   - Custom entities extend `EntityDefinition`
   - Repositories handle data access
   - Use Criteria API for queries

4. **API Design**: RESTful endpoints using PHP 8 attributes:
   ```php
   #[Route(path: '/api/cbax/analytics/data', name: 'api.cbax.analytics.data', methods: ['POST'])]
   ```

5. **Database Schema**:
   - Custom tables prefixed with `cbax_analytics_`
   - Migrations in `src/Migration/` for schema changes
   - DBAL for complex queries, repositories for simple CRUD

### Main Components

1. **Statistics Categories**:
   - Sales (by time, product, category, manufacturer)
   - Customers (new, returning, demographics)
   - Products (impressions, inventory, profits)
   - Orders (status, cancellations, returns)
   - Marketing (campaigns, vouchers, affiliates)
   - Technical (browser, OS, device statistics)

2. **Data Collection**:
   - Visitor tracking via `FrontendController`
   - Product impressions tracking
   - Search term analysis
   - Cart abandonment tracking

3. **Admin UI Features**:
   - Dashboard with configurable widgets
   - CSV export functionality
   - Chart visualizations (line, bar)
   - Date range filtering
   - KPI management

### Critical Implementation Details

1. **Context Handling**: Always pass `Context` object for multi-tenant/language support
   ```php
   public function myMethod(Context $context): void
   ```

2. **Performance Considerations**:
   - Heavy statistics queries can be intensive
   - Consider implementing caching for frequently accessed data
   - Background tasks via `ScheduledTask` for data aggregation

3. **Security**:
   - All `/api/cbax/analytics/` routes require admin authentication
   - ACL permissions checked via `@required acl` annotations
   - CSRF protection enabled

4. **Multi-language**:
   - Translations in `Resources/config/` for de-DE and en-GB
   - Use `$this->translator->trans()` for user-facing strings

5. **Plugin Configuration**:
   - Config schema in `Resources/config/config.xml`
   - Access via `SystemConfigService`

### API Endpoints

Base path: `/api/cbax/analytics/`

Key endpoints:
- `POST /data` - Fetch statistics data
- `POST /config` - Get/set plugin configuration
- `POST /export` - Export data to CSV

All endpoints:
- Require admin authentication
- Accept JSON payloads
- Return standardized JSON responses

### Development Notes

1. **PHP Version**: Requires PHP 8.2+
2. **Shopware Version**: Strictly 6.6.x (>=6.6.0, <6.7)
3. **Database**: MySQL/MariaDB via Doctrine DBAL
4. **Frontend**: Vue.js for admin UI, vanilla JS for storefront
5. **Recent Features** (v4.x):
   - Pickware ERP Pro integration
   - Enhanced profit calculations
   - Invoice date tracking
   - Performance optimizations