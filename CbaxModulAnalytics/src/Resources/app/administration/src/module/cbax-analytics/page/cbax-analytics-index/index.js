import template from './cbax-analytics-index.html.twig';
import './cbax-analytics-index.scss';

const { Component, Context, Mixin } = Shopware;

Component.register('cbax-analytics-index', {
    template,

    inject: [
        'repositoryFactory',
        'systemConfigApiService'
    ],

    mixins: [
        Mixin.getByName('cbax-analytics')
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
            dashboard: false,
            customOptions: null,
            salesChannelIds: [],
            customerGroupIds: [],
            dateRange: '30',
            salesChannnelFilterData: {},
            customerGroupFilterdata: {},
            config: {},
            statesFilterData: {}
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
            let options = { year: 'numeric', month: '2-digit', day: '2-digit' };
            let startDate = new Date(this.displayOptions.start);
            return startDate.toLocaleDateString('en-GB', options);
        },

        endDateHeadline() {
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

            this.systemConfigApiService.getValues('CbaxModulAnalytics.config').then((responseConfig) => {
                if (responseConfig && typeof responseConfig === 'object') {
                    for (const [key, value] of Object.entries(responseConfig)) {
                        this.displayOptions.config[key.replace('CbaxModulAnalytics.config.', '')] = value;
                    }
                    if (responseConfig['CbaxModulAnalytics.config.collapseSidebar']) {
                        Shopware.State.commit('adminMenu/collapseSidebar');
                    }
                    if (responseConfig['CbaxModulAnalytics.config.chartType']) {
                        this.chartType = responseConfig['CbaxModulAnalytics.config.chartType'];
                        this.displayOptions.chartType = responseConfig['CbaxModulAnalytics.config.chartType'];
                    }
                    if (responseConfig['CbaxModulAnalytics.config.defaultSaleschannels']) {
                        this.salesChannelIds = responseConfig['CbaxModulAnalytics.config.defaultSaleschannels'];
                        this.displayOptions.salesChannelIds = responseConfig['CbaxModulAnalytics.config.defaultSaleschannels'];
                    }
                    if (responseConfig['CbaxModulAnalytics.config.defaultCustomerGroups']) {
                        this.customerGroupIds = responseConfig['CbaxModulAnalytics.config.defaultCustomerGroups'];
                        this.displayOptions.customerGroupIds = responseConfig['CbaxModulAnalytics.config.defaultCustomerGroups'];
                    }
                    if (responseConfig['CbaxModulAnalytics.config.grossOrNet']) {
                        this.grossOrNet = responseConfig['CbaxModulAnalytics.config.grossOrNet'];
                    }
                    if (responseConfig['CbaxModulAnalytics.config.statisticsDateRange']) {
                        this.dateRange = responseConfig['CbaxModulAnalytics.config.statisticsDateRange'];
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
        }
    }
});
