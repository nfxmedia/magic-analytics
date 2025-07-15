import template from './cbax-analytics-index-new-customers-by-time.html.twig';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-new-customers-by-time', {
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
            limit: 25
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        chartOptions() {
            return {
                title: {
                    text: this.$tc('cbax-analytics.view.newCustomersByTime.titleChart')
                },
                stroke: {
                    curve: 'smooth'
                },
                noData: {
                    text: this.$tc('cbax-analytics.index.noData'),
                    style: {
                        color: '#189eff',
                        fontSize: 20
                    }
                },
                xaxis: {
                    type: 'category'
                },
                yaxis: {
                    min:0,
                    forceNiceScale: true,
                    labels:{
                        formatter: (value) => { return parseInt(value, 10); }
                    }
                }
            };
        },

        chartSeriesData() {
            if (!this.seriesData) {
                return null;
            }

            const seriesData1 = this.seriesData.map((data) => {
                return { x: data.date, y: data.sum };
            });
            seriesData1.reverse();

            const seriesData2 = this.seriesData.map((data) => {
                return { x: data.date, y: data.paying };
            });
            seriesData2.reverse();

            return [
                { name: this.$tc('cbax-analytics.view.newCustomersByTime.sumChart'), data: seriesData1 },
                { name: this.$tc('cbax-analytics.view.newCustomersByTime.payingChart'), data: seriesData2 }
            ];
        },

        getGridColumns() {
            return [{
                property: 'date',
                dataIndex: 'date',
                label: this.$tc('cbax-analytics.view.newCustomersByTime.dateColumn'),
                allowResize: false,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sum',
                dataIndex: 'sum',
                label: this.$tc('cbax-analytics.view.newCustomersByTime.sumColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'paying',
                dataIndex: 'paying',
                label: this.$tc('cbax-analytics.view.newCustomersByTime.payingColumn'),
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

            if (this.activeStatistic.name === 'new_customers_by_time' && this.activeStatistic.pathInfo) {
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
                        this.gridData = response.data['seriesData'];
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
