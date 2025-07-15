import template from './cbax-analytics-dashboard-statistics.html.twig';

const { Mixin, Component, Context } = Shopware;
const { Criteria } = Shopware.Data;
const USER_CONFIG_KEY = 'cbax.analytics.dashboard';

Component.register('cbax-analytics-dashboard-statistics', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('user-settings')
    ],

    provide() {
        return {
            defaultOptions: this.defaultOptions
        }
    },

    props: {
        dateRange: {
            type: Number,
            required: false,
            default: 30
        },

        cbaxAnalyticsConfig: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            activeStatistics: [],
            dashboardUserSettings: null,
            displayOptions: null,
            systemCurrency: {},
            isLoading: true,
            grossOrNet: 'gross',
            chartType: 'pie',
            dashboard: true,
            customOptions: null,
            salesChannelIds: []
        };
    },

    created() {
        this.loadSystemCurrency();
        this.createdComponent();
    },

    computed: {
        currentUser() {
            return Shopware.State.get('session')?.currentUser;
        },

        currencyRepository() {
            return this.repositoryFactory.create('currency');
        },

        groupRepository() {
            return this.repositoryFactory.create('cbax_analytics_groups_config');
        },

        statisticsRepository() {
            return this.repositoryFactory.create('cbax_analytics_config');
        }
    },

    methods: {

        async createdComponent() {
            this.isLoading = true;
            this.displayOptions = this.defaultOptions();

            if (this.cbaxAnalyticsConfig['CbaxModulAnalytics.config.grossOrNet']) {
                this.grossOrNet = this.cbaxAnalyticsConfig['CbaxModulAnalytics.config.grossOrNet'];
            }
            if (this.cbaxAnalyticsConfig['CbaxModulAnalytics.config.chartType']) {
                this.displayOptions.chartType = this.cbaxAnalyticsConfig['CbaxModulAnalytics.config.chartType'];
                this.chartType = this.cbaxAnalyticsConfig['CbaxModulAnalytics.config.chartType'];
            }
            if (this.cbaxAnalyticsConfig['CbaxModulAnalytics.config.dashboardSaleschannels']) {
                this.salesChannelIds = this.cbaxAnalyticsConfig['CbaxModulAnalytics.config.dashboardSaleschannels'];
                this.displayOptions.salesChannelIds = this.cbaxAnalyticsConfig['CbaxModulAnalytics.config.dashboardSaleschannels'];
            }

            const statisticsCriteria = new Criteria();
            statisticsCriteria.addFilter(Criteria.equals('active', 1));
            statisticsCriteria.addFilter(Criteria.multi(
                'OR',
                [
                    Criteria.equals('parameter.dashboard.hasTable', 1),
                    Criteria.equals('parameter.dashboard.hasChart', 1)
                ],
            ),);

            let userSettings;
            try {
                userSettings = await this.getUserSettings(USER_CONFIG_KEY, this.currentUser?.id);
            } catch (e) {

            }

            if (userSettings?.dashboard) {
                this.dashboardUserSettings = JSON.parse(userSettings?.dashboard) ?? [];
            }

            this.statisticsRepository.search(statisticsCriteria, Context.api).then((result) => {
                if (result.length > 0) {
                    result.forEach((stat) => {
                        if (this.dashboardUserSettings && this.dashboardUserSettings[stat.id]) {

                            if (this.dashboardUserSettings[stat.id]?.position) {
                                stat.parameter.dashboard.position = parseInt(this.dashboardUserSettings[stat.id].position, 10);
                            } else stat.parameter.dashboard.position = 1;

                            stat.parameter.dashboard.showChart = this.dashboardUserSettings[stat.id].showChart ?? false;
                            stat.parameter.dashboard.showTable = this.dashboardUserSettings[stat.id].showTable ?? false;

                            this.activeStatistics.push(stat);

                        } else {
                            stat.parameter.dashboard.showChart = false;
                            stat.parameter.dashboard.showTable = false;
                        }

                    });


                    this.$nextTick(() => {
                        this.activeStatistics.sort((a, b) => a.parameter.dashboard.position - b.parameter.dashboard.position);

                        this.$nextTick(() => {
                            this.isLoading = false;
                        });
                    });

                } else {
                    this.isLoading = false;
                }
            });
        },

        loadSystemCurrency() {
            return this.currencyRepository
                .get(Shopware.Context.app.systemCurrencyId, Context.api)
                .then((systemCurrency) => {
                    this.systemCurrency = systemCurrency;
                });
        },

        defaultOptions() {
            let start = new Date();
            let end = new Date();
            start.setHours(0, 0, 0, 0);
            start.setDate(start.getDate() - this.dateRange);
            end.setDate(end.getDate() + 1);
            end.setHours(23, 59, 59, 0);

            return {
                start: start.toISOString(),
                end: end.toISOString(),
                salesChannelIds: this.salesChannelIds,
                chartType: this.chartType,
                dashboard: this.dashboard
            };
        },
    }
});
