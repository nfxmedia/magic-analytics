// Use ultimate dashboard template with all advanced features
import template from './nfx-analytics-ultimate.html.twig';
import './nfx-analytics-index.scss';

// Import all advanced components
import '../component/nfx-particles-bg';
import '../component/nfx-glass-navigation';
import '../component/nfx-theme-switcher-advanced';
import '../component/nfx-analog-clock';
import '../component/nfx-stock-prediction';
import '../component/nfx-masonry-grid';
import '../component/nfx-kpi-flipcard';
import '../component/nfx-apex-chart';
import '../component/nfx-animated-counter';
import '../component/nfx-progress-ring';

const { Component, Context, Mixin } = Shopware;

Component.register('nfx-analytics-index', {
    template,

    inject: [
        'repositoryFactory',
        'systemConfigApiService'
    ],

    mixins: [
        Mixin.getByName('nfx-analytics')
    ],

    provide() {
        return {
            defaultOptions: this.defaultOptions
        }
    },

	metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    data() {
        return {
            manualLink: 'https://coolbax.gitbook.io/coolbax-docs/handbucher/administration/statistik-professionell',
            modulStarting: true,
            activeStatistic: null,
            activeStatisticName: '',
            activeComponentName: '',
            displayOptions: null,
            filterOptions: null,
            systemCurrency: null,
            isLoading: true,
            isMobileViewport: null,
            splitBreakpoint: 1024,
            category: false,
            parentRoute: null,
            pageColor: null,
            filterSidebarIsOpen: false,
            moreFiltersSideBarIsOpen: false,
            format: '',
            grossOrNet: 'gross',
            chartType: 'pie',
            currentTheme: 'dark-violet',
            dashboard: false,
            customOptions: null,
            salesChannelIds: [],
            customerGroupIds: [],
            dateRange: '30',
            salesChannnelFilterData: {},
            customerGroupFilterdata: {},
            config: {},
            statesFilterData: {},
            
            // Advanced Dashboard Data
            navigationItems: [
                {
                    id: 'overview',
                    title: 'Dashboard Overview',
                    description: 'Real-time analytics overview',
                    icon: 'icons-regular-dashboard',
                    category: 'dashboard'
                },
                {
                    id: 'revenue',
                    title: 'Revenue Analytics',
                    description: 'Sales performance tracking',
                    icon: 'icons-regular-chart-line',
                    category: 'analytics'
                },
                {
                    id: 'customers',
                    title: 'Customer Insights',
                    description: 'Customer behavior analysis',
                    icon: 'icons-regular-users',
                    category: 'analytics'
                },
                {
                    id: 'products',
                    title: 'Product Performance',
                    description: 'Product analytics and trends',
                    icon: 'icons-regular-package',
                    category: 'analytics'
                },
                {
                    id: 'predictions',
                    title: 'Stock Predictions',
                    description: 'AI-powered stock forecasting',
                    icon: 'icons-regular-brain',
                    category: 'ai'
                }
            ],
            
            kpiItems: [
                {
                    title: 'Total Revenue',
                    value: 127456,
                    previousValue: 98234,
                    icon: 'icons-regular-money',
                    unit: '€',
                    percentage: 75,
                    theme: 'success',
                    details: [
                        { label: 'Orders', value: 1247 },
                        { label: 'Avg. Order Value', value: '€102.15' },
                        { label: 'Growth Rate', value: '+29.8%' }
                    ]
                },
                {
                    title: 'Active Customers',
                    value: 8934,
                    previousValue: 7821,
                    icon: 'icons-regular-users',
                    unit: '',
                    percentage: 68,
                    theme: 'primary',
                    details: [
                        { label: 'New Customers', value: 234 },
                        { label: 'Returning', value: 8700 },
                        { label: 'Churn Rate', value: '3.2%' }
                    ]
                },
                {
                    title: 'Conversion Rate',
                    value: 3.42,
                    previousValue: 2.98,
                    icon: 'icons-regular-percentage',
                    unit: '%',
                    percentage: 82,
                    theme: 'warning',
                    details: [
                        { label: 'Visitors', value: 45234 },
                        { label: 'Conversions', value: 1547 },
                        { label: 'Bounce Rate', value: '42.1%' }
                    ]
                },
                {
                    title: 'Avg. Order Value',
                    value: 89.50,
                    previousValue: 76.23,
                    icon: 'icons-regular-shopping-cart',
                    unit: '€',
                    percentage: 67,
                    theme: 'info',
                    details: [
                        { label: 'Items/Order', value: 2.3 },
                        { label: 'Shipping', value: '€4.99' },
                        { label: 'Tax Rate', value: '19%' }
                    ]
                }
            ],
            
            revenueData: {
                series: [{
                    name: 'Revenue',
                    data: Array.from({ length: 30 }, (_, i) => ({
                        x: new Date(Date.now() - (29 - i) * 24 * 60 * 60 * 1000),
                        y: Math.floor(Math.random() * 5000) + 2000
                    }))
                }]
            },
            
            advancedMetrics: [
                {
                    title: 'Page Views',
                    value: 245678,
                    trend: 'up',
                    trendValue: '+12.5%',
                    trendIcon: 'icons-regular-arrow-up',
                    progress: 78,
                    color: 'var(--nfx-primary)',
                    prefix: '',
                    suffix: '',
                    decimals: 0,
                    sparklineData: 'M0,20 L20,15 L40,18 L60,12 L80,8 L100,5'
                },
                {
                    title: 'Session Duration',
                    value: 4.35,
                    trend: 'up',
                    trendValue: '+8.2%',
                    trendIcon: 'icons-regular-arrow-up',
                    progress: 65,
                    color: 'var(--nfx-success)',
                    prefix: '',
                    suffix: 'min',
                    decimals: 2,
                    sparklineData: 'M0,25 L20,20 L40,15 L60,18 L80,12 L100,10'
                },
                {
                    title: 'Cart Abandonment',
                    value: 32.1,
                    trend: 'down',
                    trendValue: '-5.7%',
                    trendIcon: 'icons-regular-arrow-down',
                    progress: 45,
                    color: 'var(--nfx-warning)',
                    prefix: '',
                    suffix: '%',
                    decimals: 1,
                    sparklineData: 'M0,15 L20,18 L40,20 L60,16 L80,14 L100,12'
                }
            ],
            
            activityFeed: [],
            totalDataPoints: 1567890,
            lastUpdated: new Date().toLocaleTimeString(),
            performanceScore: 94,
            
            // Chart options
            chartOptions: {
                chart: {
                    type: 'area',
                    height: 350,
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    },
                    background: 'transparent'
                },
                theme: {
                    mode: 'dark'
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.1
                    }
                },
                stroke: {
                    width: 3,
                    curve: 'smooth'
                },
                colors: ['#8B5CF6'],
                tooltip: {
                    theme: 'dark'
                }
            }
        };
    },

	created() {
            this.loadSystemCurrency();
            this.createdComponent();
	},

    computed: {
        activeFilterNumber() {
            return 1 + (this.displayOptions.salesChannelIds.length > 0 ? 1 : 0) + (this.displayOptions.customerGroupIds.length > 0 ? 1 : 0);
        },

        activeMoreFilterNumber() {
            if (!this.showMoreFilters) return null;

            return ((this.productFilterStatistics.includes(this.activeStatisticName) && this.displayOptions.productSearchIds.length > 0) ? 1 : 0) +
                ((this.affiliateFilterStatistics.includes(this.activeStatisticName) && this.displayOptions.affiliateCodes.length > 0) ? 1 : 0) +
                ((this.notaffiliateFilterStatistics.includes(this.activeStatisticName) && this.displayOptions.notaffiliateCodes.length > 0) ? 1 : 0) +
                ((this.campaignFilterStatistics.includes(this.activeStatisticName) && this.displayOptions.campaignCodes.length > 0) ? 1 : 0) +
                ((this.promotionFilterStatistics.includes(this.activeStatisticName) && this.displayOptions.promotionCodes.length > 0) ? 1 : 0) +
                ((this.manufacturerFilterStatistics.includes(this.activeStatisticName) && this.displayOptions.manufacturerSearchIds.length > 0) ? 1 : 0) +
                ((this.variantParentSwitchStatistics.includes(this.activeStatisticName) && this.displayOptions.showVariantParent) ? 1 : 0);
        },

        showMoreFilters() {
            const statisticsWithMoreFilters = [
                ...this.productFilterStatistics,
                ...this.affiliateFilterStatistics,
                ...this.notaffiliateFilterStatistics,
                ...this.campaignFilterStatistics,
                ...this.manufacturerFilterStatistics,
                ...this.promotionFilterStatistics,
                ...this.variantParentSwitchStatistics
            ];
            return statisticsWithMoreFilters.includes(this.activeStatisticName);
        },

        labelProperty() {
            return ['name', 'productNumber'];
        },

        productRepository() {
            return this.repositoryFactory.create('product');
        },

        startDateHeadline() {
            if (!this.displayOptions || !this.displayOptions.start) {
                return '';
            }
            let options = { year: 'numeric', month: '2-digit', day: '2-digit' };
            let startDate = new Date(this.displayOptions.start);
            return startDate.toLocaleDateString('en-GB', options);
        },

        endDateHeadline() {
            if (!this.displayOptions || !this.displayOptions.end) {
                return '';
            }
            let options = { year: 'numeric', month: '2-digit', day: '2-digit' };
            let endDate = new Date(this.displayOptions.end);
            return endDate.toLocaleDateString('en-GB', options);
        },

        currencyRepository() {
            return this.repositoryFactory.create('currency');
        },

        pageClasses() {
            return {
                'has--category': !!this.category,
                'is--mobile': !!this.isMobileViewport
            };
        }
    },

	methods: {

        async createdComponent() {
            this.isLoading = true;

            this.checkViewport();
            this.registerListener();
            this.setCategory();

            if (this.displayOptions === null) {
                this.displayOptions = this.defaultOptions();
            }

            this.displayOptions.salesChannnelFilterData = await this.getSalesChannelData();
            this.displayOptions.customerGroupFilterdata = await this.getCustomerGroupData();
            this.displayOptions.statesFilterData = await this.getStatesFilterData();

            if (this.$route.meta.parentPath) {
                this.parentRoute = this.$route.meta.parentPath;
            }

            this.pageColor = '#ff68b4';

            this.systemConfigApiService.getValues('NfxMagicAnalytics.config').then((responseConfig) => {
                if (responseConfig && typeof responseConfig === 'object') {
                    for (const [key, value] of Object.entries(responseConfig)) {
                        this.displayOptions.config[key.replace('NfxMagicAnalytics.config.', '')] = value;
                    }
                    if (responseConfig['NfxMagicAnalytics.config.collapseSidebar']) {
                        Shopware.State.commit('adminMenu/collapseSidebar');
                    }
                    if (responseConfig['NfxMagicAnalytics.config.chartType']) {
                        this.chartType = responseConfig['NfxMagicAnalytics.config.chartType'];
                        this.displayOptions.chartType = responseConfig['NfxMagicAnalytics.config.chartType'];
                    }
                    if (responseConfig['NfxMagicAnalytics.config.defaultSaleschannels']) {
                        this.salesChannelIds = responseConfig['NfxMagicAnalytics.config.defaultSaleschannels'];
                        this.displayOptions.salesChannelIds = responseConfig['NfxMagicAnalytics.config.defaultSaleschannels'];
                    }
                    if (responseConfig['NfxMagicAnalytics.config.defaultCustomerGroups']) {
                        this.customerGroupIds = responseConfig['NfxMagicAnalytics.config.defaultCustomerGroups'];
                        this.displayOptions.customerGroupIds = responseConfig['NfxMagicAnalytics.config.defaultCustomerGroups'];
                    }
                    if (responseConfig['NfxMagicAnalytics.config.grossOrNet']) {
                        this.grossOrNet = responseConfig['NfxMagicAnalytics.config.grossOrNet'];
                    }
                    if (responseConfig['NfxMagicAnalytics.config.statisticsDateRange']) {
                        this.dateRange = responseConfig['NfxMagicAnalytics.config.statisticsDateRange'];
                        this.updateDates(this.dateRange);
                    }
                }

                setTimeout(() => {
                    this.$nextTick(() => {
                        this.filterOptions = {...this.displayOptions};
                        this.isLoading = false;
                        this.modulStarting = false;
                    });
                }, 50);

            })
        },

        updateDates(range) {
            const complexRanges = ['currentWeek', 'lastWeek', 'currentMonth', 'lastMonth', 'currentQuarter', 'lastQuarter', 'currentYear', 'lastYear'];

            if (!complexRanges.includes(range)) {
                range = parseInt(range, 10);
            }
            this.displayOptions = this.updateDateRange(range, this.displayOptions);
        },

        registerListener() {
            this.$device.onResize({
                listener: this.checkViewport
            });
        },

        changeStatistic(item) {
            this.isLoading = true;
            if (item.id) {
                this.activeStatistic = item.data;
                this.activeStatisticName = item.data.name;
                this.activeComponentName = item.data.parameter.componentName;
                this.category = true;
            }
            this.$nextTick(() => {
                this.isLoading = false;
            });
        },

        onRefresh() {
            if (this.filterOptions.dateRange === '0') {
                this.filterOptions.end = this.filterOptions.end.replace('00:00:00', '23:59:59');
            } else {
                this.filterOptions = this.updateDateRange(this.filterOptions.dateRange, this.filterOptions);
            }
            this.isLoading = true;
            this.filterSidebarIsOpen = false;
            this.moreFiltersSideBarIsOpen = false;
            this.displayOptions = Object.assign({}, this.filterOptions);
            setTimeout(() => {
                this.$nextTick(() => {
                    this.isLoading = false;
                });
            }, 80);
        },

        loadSystemCurrency() {
            return this.currencyRepository
                .get(Shopware.Context.app.systemCurrencyId, Context.api)
                .then((systemCurrency) => {
                    this.systemCurrency = systemCurrency;
                });
        },

        checkViewport() {
            this.isMobileViewport = this.$device.getViewportWidth() < this.splitBreakpoint;
        },

        onGoBack() {
            this.category = false;
            this.activeStatistic = null;
            this.activeStatisticName = '';
            this.activeComponentName = '';
        },

        closeContent() {
            if (this.filterSidebarIsOpen) {
                this.$refs.filterSideBar.closeContent();
                this.filterSidebarIsOpen = false;
                this.moreFiltersSideBarIsOpen = false;
                return;
            }

            this.$refs.filterSideBar.openContent();
            this.filterSidebarIsOpen = true;
            this.moreFiltersSideBarIsOpen = false;
        },

        closeMoreContent() {
            if (this.moreFiltersSideBarIsOpen) {
                this.$refs.moreFiltersSideBar.closeContent();
                this.moreFiltersSideBarIsOpen = false;
                this.filterSidebarIsOpen = false;
                return;
            }

            this.$refs.moreFiltersSideBar.openContent();
            this.moreFiltersSideBarIsOpen = true;
            this.filterSidebarIsOpen = false;
        },

        defaultOptions() {
            let start = new Date();
            let end = new Date();
            start.setHours(0, 0, 0, 0);
            start.setDate(start.getDate() - 30);
            end.setHours(23, 59, 59, 0);

            let currentSalesChannelIds = this.salesChannelIds;

            let currentCustomerGroupIds = this.customerGroupIds;
            if (this.displayOptions) {
                if (this.displayOptions.salesChannelIds !== undefined) {
                    currentSalesChannelIds = this.displayOptions.salesChannelIds;
                }
                if (this.displayOptions.customerGroupIds !== undefined) {
                    currentCustomerGroupIds = this.displayOptions.customerGroupIds;
                }
            }

            return {
                statesFilterData: this.statesFilterData,
                config: this.config,
                salesChannnelFilterData: this.salesChannnelFilterData,
                customerGroupFilterdata: this.customerGroupFilterdata,
                start: start.toISOString(),
                end: end.toISOString(),
                salesChannelIds: currentSalesChannelIds,
                customerGroupIds: currentCustomerGroupIds,
                productSearchIds: [],
                affiliateCodes: [],
                notaffiliateCodes: [],
                campaignCodes: [],
                promotionCodes: [],
                manufacturerSearchIds: [],
                chartType: this.chartType,
                dashboard: this.dashboard,
                dateRange: this.dateRange,
                showVariantParent: false
            };
        },

        exportCSV() {
            this.format = 'csv';
        },

        downloadCSV() {
            this.format = '';

        },

        setCategory() {
            if (!this.activeStatistic) {
                this.category = false;
            } else {
                this.category = true;
            }
        },

        onFilterChangeShowParents() {
            this.displayOptions.showVariantParent = this.filterOptions.showVariantParent;
        },

        initializeTheme() {
            // Load saved theme from localStorage
            const savedTheme = localStorage.getItem('nfx-analytics-theme');
            if (savedTheme) {
                this.currentTheme = savedTheme;
                this.applyTheme(savedTheme);
            } else {
                this.applyTheme('light-apple');
            }
        },

        applyTheme(themeKey) {
            const body = document.body;
            const themes = ['dark-violet', 'light-apple', 'pastel', 'retro-90s'];
            
            // Remove all theme classes
            themes.forEach(theme => {
                body.classList.remove(`theme-${theme}`);
            });
            
            // Add new theme class
            body.classList.add(`theme-${themeKey}`);
            
            // Update current theme
            this.currentTheme = themeKey;
        },
        
        selectPopularStatistic(type) {
            // This is a placeholder - in real implementation, you would trigger
            // the appropriate statistic based on the type
            this.$nextTick(() => {
                const treeComponent = this.$refs.statisticsTree;
                if (treeComponent) {
                    // Trigger first statistic in the category
                    // This would need to be implemented based on your tree structure
                }
            });
        },
        
        // Advanced Dashboard Methods
        handleKPIReorder(newOrder) {
            this.kpiItems = newOrder;
            // Save to localStorage for persistence
            localStorage.setItem('nfx-kpi-order', JSON.stringify(newOrder));
        },
        
        generateActivityFeed() {
            const activities = [
                { type: 'order', message: 'New order received', value: '€89.50' },
                { type: 'customer', message: 'New customer registration', value: '+1' },
                { type: 'payment', message: 'Payment processed', value: '€156.00' },
                { type: 'shipment', message: 'Order shipped', value: 'Tracking: DHL123' },
                { type: 'return', message: 'Return requested', value: '€45.00' },
                { type: 'review', message: 'New product review', value: '⭐⭐⭐⭐⭐' }
            ];
            
            const users = ['John Doe', 'Jane Smith', 'Mike Johnson', 'Sarah Wilson', 'Tom Brown'];
            const locations = ['Berlin', 'Munich', 'Hamburg', 'Cologne', 'Frankfurt'];
            
            return {
                id: Date.now() + Math.random(),
                ...activities[Math.floor(Math.random() * activities.length)],
                user: users[Math.floor(Math.random() * users.length)],
                location: locations[Math.floor(Math.random() * locations.length)],
                timestamp: new Date(),
                avatar: `https://ui-avatars.com/api/?name=${users[Math.floor(Math.random() * users.length)]}&background=random`
            };
        },
        
        startActivityFeed() {
            // Add initial activities
            for (let i = 0; i < 5; i++) {
                this.activityFeed.push(this.generateActivityFeed());
            }
            
            // Start real-time activity generation
            this.activityInterval = setInterval(() => {
                this.activityFeed.unshift(this.generateActivityFeed());
                // Keep only last 20 activities
                if (this.activityFeed.length > 20) {
                    this.activityFeed.pop();
                }
            }, 3000);
        },
        
        pauseActivity() {
            if (this.activityInterval) {
                clearInterval(this.activityInterval);
                this.activityInterval = null;
            }
        },
        
        clearActivity() {
            this.activityFeed = [];
        },
        
        formatTime(timestamp) {
            const now = new Date();
            const diff = now - timestamp;
            const seconds = Math.floor(diff / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);
            
            if (seconds < 60) return `${seconds}s ago`;
            if (minutes < 60) return `${minutes}m ago`;
            if (hours < 24) return `${hours}h ago`;
            return timestamp.toLocaleDateString();
        },
        
        updateLastUpdated() {
            this.lastUpdated = new Date().toLocaleTimeString();
        },
        
        startRealTimeUpdates() {
            // Update metrics periodically
            setInterval(() => {
                this.updateLastUpdated();
                this.performanceScore = Math.min(100, this.performanceScore + Math.random() * 2 - 1);
                this.totalDataPoints += Math.floor(Math.random() * 10);
                
                // Update KPI values slightly
                this.kpiItems.forEach(item => {
                    const change = Math.random() * 200 - 100;
                    item.value = Math.max(0, item.value + change);
                });
            }, 5000);
        }
    },

    mounted() {
        // Initialize theme system
        this.initializeTheme();
        
        // Initialize analytics wrapper class
        document.body.classList.add('nfx-analytics');
        
        // Apply theme to local element as well
        this.$nextTick(() => {
            const analyticsElement = this.$el.querySelector('.nfx-analytics');
            if (analyticsElement) {
                analyticsElement.classList.add(`theme-${this.currentTheme}`);
            }
        });
        
        // Initialize advanced features
        this.startActivityFeed();
        this.startRealTimeUpdates();
        
        // Load saved KPI order
        const savedOrder = localStorage.getItem('nfx-kpi-order');
        if (savedOrder) {
            try {
                this.kpiItems = JSON.parse(savedOrder);
            } catch (e) {
                console.warn('Failed to load saved KPI order');
            }
        }
    },

    beforeDestroy() {
        // Clean up theme classes
        document.body.classList.remove('nfx-analytics');
        
        // Clean up intervals
        if (this.activityInterval) {
            clearInterval(this.activityInterval);
        }
    }
});
