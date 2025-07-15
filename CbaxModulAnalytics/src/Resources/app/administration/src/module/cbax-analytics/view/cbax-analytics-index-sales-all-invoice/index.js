import template from './cbax-analytics-index-sales-all-invoice.html.twig';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-sales-all-invoice', {
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
        systemCurrency: {
            type: Object,
            required: false,
            default: {}
        },
        format: {
            type: String,
            required: false,
            default: ''
        }
    },

    data() {
        return {
            //filterName: 'invoice-date',
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
                    text: this.$tc('cbax-analytics.view.salesAllInvoice.titleChart')
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
                yaxis: [
                    {
                        title: {
                            text: this.$tc('cbax-analytics.view.sumColumn')
                        },
                        min:0,
                        forceNiceScale: true,
                        labels:{
                            formatter: (value) => this.currencyFilter(value, this.systemCurrency.isoCode)
                        }
                    }, {
                        opposite: true,
                        title: {
                            text: this.$tc('cbax-analytics.view.countColumnShort')
                        },
                        labels:{
                            formatter: (value) => parseInt(value, 10)
                        }
                    }
                ]
            };
        },

        chartSeriesData() {
            let chartData = this.defaultChartSeriesDataWithDates(this.seriesData, 'date', 'sumNet',this.$tc('cbax-analytics.view.sumColumn'));
            if (Array.isArray(chartData)) {
                chartData.push(this.defaultChartSeriesDataWithDates(this.seriesData, 'date', 'count',this.$tc('cbax-analytics.view.countColumnShort'))[0]);
            }

            return chartData;
        },

        getGridColumns() {
            return [{
                property: 'formatedDate',
                dataIndex: 'formatedDate',
                label: this.$tc('cbax-analytics.view.orderSales.dateColumn'),
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
                property: 'sumGross',
                dataIndex: 'sumGross',
                label: this.$tc('cbax-analytics.view.sumColumn') + this.$tc('cbax-analytics.view.gross'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sumNet',
                dataIndex: 'sumNet',
                label: this.$tc('cbax-analytics.view.sumColumn') + this.$tc('cbax-analytics.view.net'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'quantity',
                dataIndex: 'quantity',
                label: this.$tc('cbax-analytics.view.salesAllInvoice.quantityColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sumNetAverage',
                dataIndex: 'sumNetAverage',
                label: this.$tc('cbax-analytics.view.salesAllInvoice.sumNetAverageColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'quantityAverage',
                dataIndex: 'quantityAverage',
                label: this.$tc('cbax-analytics.view.salesAllInvoice.quantityAverageColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }];
        },

        gridSeriesData() {
            return this.defaultGridSeriesData(this.gridData, this.page, this.limit);
        },

        summerySeriesData() {
            if (!this.gridData || !Array.isArray(this.gridData)) return null;
            if (Array.isArray(this.gridData) && this.gridData.length === 0) return null;

            let summery = {
                formatedDate: '_',
                count: 0,
                sumGross: 0,
                sumNet: 0,
                quantity: 0,
                sumNetAverage: 0,
                quantityAverage: 0,
            };
            this.gridData.forEach((item) => {
                summery.count += item.count;
                summery.sumGross += item.sumGross;
                summery.sumNet += item.sumNet;
                summery.quantity += item.quantity;
            });

            summery.sumNetAverage = Math.round(100*summery.sumNet / summery.count) / 100;
            summery.quantityAverage = Math.round(100*summery.quantity / summery.count) / 100;

            return [summery];
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

            if (this.activeStatistic.name === 'sales_all_invoice' && this.activeStatistic.pathInfo) {
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
