import template from './cbax-analytics-index-orders-count-all.html.twig';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-orders-count-all', {
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
            minX: null,
            maxX: null,
            page: 1,
            limit: 25,
            overallData: null,
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },
        chartOptionsOrderCount() {
            return {
                title: {
                    text: this.$tc('cbax-analytics.view.orderCountAll.titleChart')
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
                    type: 'datetime',
                    min: this.minX,
                    max: this.maxX,
                    labels: {
                        datetimeUTC: false
                    }
                },
                yaxis: {
                    min:0,
                    forceNiceScale: true,
                    labels:{
                        formatter: (value) => { return parseInt(value, 10);}
                    }
                }
            };
        },

        orderCountSeries() {
            if (!this.seriesData) {
                return null;
            }

            const seriesData1 = this.seriesData.map((data) => {
                return { x: this.parseDate(data.date), y: data.firstTimeCount };
            });

            const seriesData2 = this.seriesData.map((data) => {
                return { x: this.parseDate(data.date), y: data.count };
            });

            return [
                { name: this.$tc('cbax-analytics.view.orderCountAll.firstTimeCountChart'), data: seriesData1 },
                { name: this.$tc('cbax-analytics.view.orderCountAll.countChart'), data: seriesData2 }
            ];
        },

        getGridColumns() {
            return [{
                property: 'formatedDate',
                dataIndex: 'formatedDate',
                label: this.$tc('cbax-analytics.view.orderCountAll.dateColumn'),
                allowResize: false,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'firstTimeCount',
                dataIndex: 'firstTimeCount',
                label: this.$tc('cbax-analytics.view.orderCountAll.firstTimeCountColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'returningCount',
                dataIndex: 'returningCount',
                label: this.$tc('cbax-analytics.view.orderCountAll.returningCountColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'count',
                dataIndex: 'count',
                label: this.$tc('cbax-analytics.view.orderCountAll.countColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }];
        },

        orderCountData() {
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

            if (this.activeStatistic.name === 'orders_count_all' && this.activeStatistic.pathInfo) {
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
                        let gridData = response.data['seriesData'];
                        gridData.reverse();
                        this.gridData = gridData;
                        this.overallData = this.getOverallData(this.gridData);
                    }

                    this.maxX = this.getMaxDate(this.displayOptions.end);
                    this.minX = this.getMinDate(this.displayOptions.start);
                    this.isLoading = false;

                }).catch((err) => {
                    this.isLoading = false;
                    if (parameters.format === 'csv') {
                        this.$emit('cbax-statistics-csv_done');
                    }
                });
            }

        },

        getOverallData(data) {
            if (!data || data.length === 0) {
                return null;
            }

            let overallData = {};
            overallData.count = 0;
            overallData.firstTimeCount = 0;
            overallData.returningCount = 0;
            overallData.firstTimePart = 0;
            overallData.returningPart = 0;

            data.forEach((item) => {
                overallData.count += item.count;
                overallData.firstTimeCount += item.firstTimeCount;
                overallData.returningCount += item.returningCount;
            });

            if (overallData.count > 0) {
                overallData.firstTimePart = 100 * (overallData.firstTimeCount / overallData.count);
                overallData.firstTimePart = overallData.firstTimePart.toFixed(1);
                overallData.returningPart = 100 * (overallData.returningCount / overallData.count);
                overallData.returningPart = overallData.returningPart.toFixed(1);
            }

            return overallData;
        }
    }
});
