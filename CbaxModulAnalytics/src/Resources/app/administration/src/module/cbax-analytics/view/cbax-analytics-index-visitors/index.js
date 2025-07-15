import template from './cbax-analytics-index-visitors.html.twig';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-visitors', {
    template,

    mixins: [
        Mixin.getByName('cbax-analytics')
    ],
    props: {
        activeStatistic: {
            type: Object,
            required: false,
            default: null
        },
        displayOptions: {
            type: Object,
            required: true
        },
        format: {
            type: String,
            required: false,
            default: ''
        }
    },

    data() {
        return {
            isLoading: false,
            seriesData: null,
            gridData: null,
            total: 0,
            page: 1,
            limit: 25,
            chartType: 'pie',
            overallData: null
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },
        chartOptions() {
            return {
                title: {
                    text: this.$tc('cbax-analytics.view.visitors.titleChart')
                },
                stroke: {
                    curve: 'smooth'
                },
                xaxis: {
                },
                yaxis: {
                    forceNiceScale: true,
                    labels:{
                        formatter: (value) => { return parseInt(value, 10);}
                    }
                },
                legend: {
                    position: "bottom"
                },
                noData: {
                    text: this.$tc('cbax-analytics.index.noData'),
                    style: {
                        color: '#189eff',
                        fontSize: 20
                    }
                }
            };
        },

        chartSeriesData() {
            if (!this.seriesData) {
                return null;
            }

            const seriesData = this.seriesData.map((data) => {
                return { x: data.date, y: data.uniqueVisits };
            });

            const seriesDataDesktop = this.seriesData.map((data) => {
                return { x: data.date, y: data.desktop };
            });

            const seriesDataMobile = this.seriesData.map((data) => {
                return { x: data.date, y: data.mobile };
            });

            const seriesDataTablet = this.seriesData.map((data) => {
                return { x: data.date, y: data.tablet };
            });

            return [
                { name: this.$tc('cbax-analytics.view.visitors.totalVisitors'), data: seriesData },
                { name: this.$tc('cbax-analytics.view.visitors.desktopColumn'), data: seriesDataDesktop },
                { name: this.$tc('cbax-analytics.view.visitors.mobileColumn'), data: seriesDataMobile },
                { name: this.$tc('cbax-analytics.view.visitors.tabletColumn'), data: seriesDataTablet }
            ];
        },

        getGridColumns() {
            return [{
                property: 'date.date',
                dataIndex: 'date.date',
                label: this.$tc('cbax-analytics.view.visitors.dateColumn'),
                allowResize: false,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'desktop',
                dataIndex: 'desktop',
                label: this.$tc('cbax-analytics.view.visitors.desktopColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'mobile',
                dataIndex: 'mobile',
                label: this.$tc('cbax-analytics.view.visitors.mobileColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'tablet',
                dataIndex: 'tablet',
                label: this.$tc('cbax-analytics.view.visitors.tabletColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'uniqueVisits',
                dataIndex: 'uniqueVisits',
                label: this.$tc('cbax-analytics.view.visitors.totalVisitors'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }];
        },

        gridSeriesData() {
            return this.defaultGridSeriesData(this.gridData, this.page, this.limit);
        }
    },

    watch: {
        displayOptions() {
            this.createdComponent();
        },

        format() {
            if (this.format == 'csv') {
                this.createdComponent();
            }
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isLoading = true;

            const initContainer = Shopware.Application.getContainer('init');
            const httpClient = initContainer.httpClient;
            const loginService = Shopware.Service('loginService');

            let parameters = this.getBasicParameters(this.displayOptions, this.format, this.activeStatistic.name);
            if (this.format === 'csv') {
                parameters.labels = this.getGridLabels(this.getGridColumns);
            }

            if (this.activeStatistic.name === 'visitors' && this.activeStatistic.pathInfo) {
                httpClient.post(this.activeStatistic.pathInfo,
                    { parameters },
                    { headers: { Authorization: `Bearer ${loginService.getToken()}`,} }
                    ).then((response) => {

                    if (parameters.format === 'csv' && response.data !== undefined && response.data['success'] === true && response.data['fileSize']) {
                        this.$emit('cbax-statistics-csv_done');
                        this.csvDownload(this.activeStatistic.name + '.csv', response.data['fileSize']);
                    }

                    if (response.data !== undefined && response.data['success'] === true && response.data['seriesData']) {
                        this.seriesData = response.data['seriesData'];
                        this.total = response.data['seriesData'].length;
                        this.gridData = [...response.data['seriesData']];
                        this.gridData.reverse();
                    }

                    this.isLoading = false;

                }).catch((err) => {
                    this.isLoading = false;
                    if (parameters.format === 'csv') {
                        this.$emit('cbax-statistics-csv_done');
                    }
                });
            }
        }
    }
});
