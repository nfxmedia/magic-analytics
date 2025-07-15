import template from './cbax-analytics-index-conversion-all.html.twig';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-conversion-all', {
    template,

    mixins: [
        Mixin.getByName('cbax-analytics')
    ],

    props: {
        activeStatistic: {
            type: Object,
            required: false
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
            filterName: 'order-date',
            isLoading: false,
            seriesData: null,
            gridData: null,
            total: 0,
            minX: null,
            maxX: null,
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
                    text: this.$tc('cbax-analytics.view.conversionAll.titleChart')
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
                    title: {
                        text: 'Conversion in %'
                    },
                    min:0,
                    forceNiceScale: true,
                    labels:{
                        formatter: (value) => { return parseFloat(value);}
                    }
                }
            };
        },

        chartSeriesData() {
            return this.defaultChartSeriesDataWithDates(this.seriesData, 'date', 'conversionPercent','Conversion');
        },

        getGridColumns() {
            return [{
                property: 'formatedDate',
                dataIndex: 'formatedDate',
                label: this.$tc('cbax-analytics.view.dateColumn'),
                allowResize: false,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'count',
                dataIndex: 'count',
                label: this.$tc('cbax-analytics.view.countColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'visitors',
                dataIndex: 'visitors',
                label: this.$tc('cbax-analytics.view.conversionAll.visitorsColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'conversion',
                dataIndex: 'conversion',
                label: this.$tc('cbax-analytics.view.conversionAll.conversionColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'conversionPercent',
                dataIndex: 'conversionPercent',
                label: this.$tc('cbax-analytics.view.conversionAll.conversionPercentColumn'),
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

            if (this.activeStatistic.name === 'conversion_all' && this.activeStatistic.pathInfo) {
                httpClient.post(this.activeStatistic.pathInfo,
                    { parameters },
                    { headers: { Authorization: `Bearer ${loginService.getToken()}`,} }
                    ).then((response) => {
                    if (parameters.format === 'csv' && response.data !== undefined && response.data['success'] === true && response.data['fileSize']) {
                        this.$emit('cbax-statistics-csv_done');
                        this.csvDownload(this.activeStatistic.name + '.csv', response.data['fileSize']);
                    }
                    if (response.data !== undefined && response.data['success'] === true && response.data['gridData']) {
                        this.gridData = response.data['gridData'];
                        this.total = response.data['gridData'].length;
                        this.seriesData = [...response.data['gridData']];
                        this.seriesData = this.seriesData.filter(item => item.conversion !== 'NA');
                        this.gridData.reverse();
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
        }
    }
});
