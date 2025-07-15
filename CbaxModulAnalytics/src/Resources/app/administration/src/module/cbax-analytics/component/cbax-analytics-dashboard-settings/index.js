import template from './cbax-analytics-dashboard-settings.html.twig';
import './cbax-analytics-dashboard-settings.scss';

const { Mixin, Component } = Shopware;
const { Criteria } = Shopware.Data;
const USER_CONFIG_KEY = 'cbax.analytics.dashboard';

Component.register('cbax-analytics-dashboard-settings', {
    template,

    inject: [
        'repositoryFactory',
        'acl'
    ],

    mixins: [
        Mixin.getByName('user-settings')
    ],

    props: {
        dateRange: {
            type: Number,
            required: false,
            default: 30
        }
    },

    data() {
        return {
            dashboardUserSettings: {},
            groups: [],
            isLoading: true,
            settingsOpen: false,
            statisticDateRanges: {
                value: this.dateRange,
                options: {
                    '90Days': 90,
                    '30Days': 30,
                    '14Days': 14,
                    '7Days': 7,
                    'yesterday': 1,
                },
            },
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
        currentUser() {
            return Shopware.State.get('session')?.currentUser;
        },

        groupRepository() {
            return this.repositoryFactory.create('cbax_analytics_groups_config');
        },

        statisticsRepository() {
            return this.repositoryFactory.create('cbax_analytics_config');
        },

        groupsCriteria() {
            let criteria = new Criteria();
            criteria.addFilter(Criteria.equals('active', 1));
            criteria.addAssociation('statistics');
            criteria.getAssociation('statistics').addFilter(Criteria.equals('active', 1));
            criteria.addSorting(Criteria.sort('position'));
            criteria.getAssociation('statistics').addFilter(Criteria.multi(
                'OR',
                [
                    Criteria.equals('parameter.dashboard.hasTable', 1),
                    Criteria.equals('parameter.dashboard.hasCHART', 1)
                ],
            ),);

            return criteria;
        },

        statisticsCriteria() {
            let criteria = new Criteria();
            criteria.addFilter(Criteria.equals('active', 1));
            criteria.addFilter(Criteria.equals('groupId', null));

            return criteria;
        },

        dateAgo() {
            const date = new Date();
            const dateRange = this.dateRange ?? 30;

            date.setDate(date.getDate() - dateRange);
            date.setHours(0, 0, 0, 0);

            return date;
        },

        today() {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            return today;
        }
    },

    methods: {

        async createdComponent() {
            this.isLoading = true;

            let userSettings;
            try {
                userSettings = await this.getUserSettings(USER_CONFIG_KEY, this.currentUser?.id);
            } catch (e) {

            }

            if (userSettings?.dashboard) {
                this.dashboardUserSettings = JSON.parse(userSettings?.dashboard);
            }

            this.statisticsRepository.search(this.statisticsCriteria).then((result1) => {

                this.groupRepository.search(this.groupsCriteria).then((result2) => {
                    if (result1.length > 0) {
                        result2.unshift({label: result1.first().label, statistics: result1});
                        this.groups = result2.filter(group => group.statistics !== undefined && group.statistics.length > 0);
                    }

                        this.groups.forEach((group) => {
                            group.statistics.forEach((stat) => {
                                if (this.dashboardUserSettings[stat.id]) {

                                    if (this.dashboardUserSettings[stat.id].position) {
                                        stat.parameter.dashboard.position = parseInt(this.dashboardUserSettings[stat.id].position, 10);
                                    } else stat.parameter.dashboard.position = 0;

                                    stat.parameter.dashboard.showChart = this.dashboardUserSettings[stat.id].showChart ?? false;
                                    stat.parameter.dashboard.showTable = this.dashboardUserSettings[stat.id].showTable ?? false;

                                } else {
                                    stat.parameter.dashboard.showChart = false;
                                    stat.parameter.dashboard.showTable = false;
                                }
                            });
                        });

                    this.$nextTick(() => {
                        this.groups.forEach((group) => {
                            group.statistics.sort((a, b) => parseInt(a.parameter.dashboard.position, 10) - parseInt(b.parameter.dashboard.position, 10));
                        })
                        this.isLoading = false;
                    });
                });
            });
        },

        onSave() {
            this.isLoading = true;

            let dashboardConfig = {}, config;

            this.groups.forEach((group) => {
                group.statistics.forEach((stat) => {
                    config = {};
                    if (stat.parameter.dashboard.showChart || stat.parameter.dashboard.showTable) {

                        config.showChart = stat.parameter.dashboard.showChart ?? false;
                        config.showTable = stat.parameter.dashboard.showTable ?? false;

                        if (stat.parameter.dashboard.position) {
                            config.position = parseInt(stat.parameter.dashboard.position, 10);
                        } else {
                            config.position = 1;
                        }
                        dashboardConfig[stat.id] = config;
                    }
                });

            });
            this.$nextTick(() => {
                try {
                    this.saveUserSettings(
                        USER_CONFIG_KEY,
                        { dashboard: JSON.stringify(dashboardConfig) },
                        this.currentUser?.id
                    ).then(() => {
                        this.createdComponent();
                    })
                } catch (e) {

                }

            })
        },

        onOpenSettings() {
            this.settingsOpen = true;
        },

        onCloseSettings() {
            this.settingsOpen = false;
            this.$nextTick(() => {
                this.$emit('cbax-statistics-settings-modal-closed', parseInt(this.statisticDateRanges.value, 10));
            })
        }
    }
});
