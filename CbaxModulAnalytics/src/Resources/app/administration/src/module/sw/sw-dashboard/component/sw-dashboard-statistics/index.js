import template from './sw-dashboard-statistics.html.twig';

const { Component } = Shopware;

Component.override('sw-dashboard-statistics', {
    template,

    inject: [
        'systemConfigApiService',
        'acl'
    ],

    data() {
        return {
            cbaxLocation: 'before',
            cbaxActive: false,
            dateRange: 30,
            dateRangeInit: false,
            isCbaxLoading: true,
            startLoadingCbaxData: false,
            cbaxAnalyticsConfig: null,
        }
    },

    methods: {
        initializeOrderData() {
            if (!this.acl.can('cbaxAnalyticsDashboard.viewer')) {
                this.isCbaxLoading = false;
                this.$super('initializeOrderData');

            } else {
                if (!this.startLoadingCbaxData && this.cbaxAnalyticsConfig === null) {
                    this.startLoadingCbaxData = true;
                    this.systemConfigApiService.getValues('CbaxModulAnalytics.config').then((responseConfig) => {
                        this.cbaxAnalyticsConfig = responseConfig;

                        if (responseConfig['CbaxModulAnalytics.config.dashboardActiv']) {
                            this.cbaxActive = responseConfig['CbaxModulAnalytics.config.dashboardActiv'];
                        }
                        if (!this.dateRangeInit && responseConfig['CbaxModulAnalytics.config.dashboardDateRange']) {
                            this.dateRange = parseInt(responseConfig['CbaxModulAnalytics.config.dashboardDateRange'], 10);
                        }
                        if (responseConfig['CbaxModulAnalytics.config.dashboardLocation']) {
                            this.cbaxLocation = responseConfig['CbaxModulAnalytics.config.dashboardLocation'];
                        }

                        this.dateRangeInit = true;
                        this.startLoadingCbaxData = false;
                        this.isCbaxLoading = false;

                        this.$super('initializeOrderData');
                    })

                } else {
                    this.isCbaxLoading = false;
                    this.$super('initializeOrderData');
                }
            }
        },

        resetDashboard(event) {
            this.dateRange = event;
            this.isCbaxLoading = true;
            this.$nextTick(() => {
                this.isCbaxLoading = false;
            });
        }
    }
});



