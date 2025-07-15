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
            nfxLocation: 'before',
            nfxActive: false,
            dateRange: 30,
            dateRangeInit: false,
            isNfxLoading: true,
            startLoadingNfxData: false,
            nfxAnalyticsConfig: null,
        }
    },

    methods: {
        initializeOrderData() {
            if (!this.acl.can('nfxAnalyticsDashboard.viewer')) {
                this.isNfxLoading = false;
                this.$super('initializeOrderData');

            } else {
                if (!this.startLoadingNfxData && this.nfxAnalyticsConfig === null) {
                    this.startLoadingNfxData = true;
                    this.systemConfigApiService.getValues('NfxModulAnalytics.config').then((responseConfig) => {
                        this.nfxAnalyticsConfig = responseConfig;

                        if (responseConfig['NfxModulAnalytics.config.dashboardActiv']) {
                            this.nfxActive = responseConfig['NfxModulAnalytics.config.dashboardActiv'];
                        }
                        if (!this.dateRangeInit && responseConfig['NfxModulAnalytics.config.dashboardDateRange']) {
                            this.dateRange = parseInt(responseConfig['NfxModulAnalytics.config.dashboardDateRange'], 10);
                        }
                        if (responseConfig['NfxModulAnalytics.config.dashboardLocation']) {
                            this.nfxLocation = responseConfig['NfxModulAnalytics.config.dashboardLocation'];
                        }

                        this.dateRangeInit = true;
                        this.startLoadingNfxData = false;
                        this.isNfxLoading = false;

                        this.$super('initializeOrderData');
                    })

                } else {
                    this.isNfxLoading = false;
                    this.$super('initializeOrderData');
                }
            }
        },

        resetDashboard(event) {
            this.dateRange = event;
            this.isNfxLoading = true;
            this.$nextTick(() => {
                this.isNfxLoading = false;
            });
        }
    }
});



