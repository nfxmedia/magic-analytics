import template from './nfx-analytics-index-sales-by-month-pwreturn.html.twig';

const { Component, Mixin } = Shopware;

Component.register('nfx-analytics-index-sales-by-month-pwreturn', {
    template,

    mixins: [
        Mixin.getByName('nfx-analytics')
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
            required: true,
            default: {}
        },
        format: {
            type: String,
            required: false,
            default: ''
        },
        grossOrNet: {
            type: String,
            required: false,
            default: 'net'
        }
    },

    data() {
        return {
            filterName: 'order-date',
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
                    text: this.$tc('nfx-analytics.view.salesMonthlyPwreturn.titleChart')
                },
                stroke: {
                    curve: 'smooth'
                },
                noData: {
                    text: this.$tc('nfx-analytics.index.noData'),
                    style: {
                        color: '#189eff',
                        fontSize: 20
                    }
                },
                xaxis: {
                    type: 'category'
                },
                yaxis: [
                    {
                        title: {
                            text: this.$tc('nfx-analytics.view.salesAllPwreturn.newGrossColumn')
                        },
                        min:0,
                        forceNiceScale: true,
                        labels:{
                            formatter: (value) => this.currencyFilter(value, this.systemCurrency.isoCode)
                        }
                    }, {
                        opposite: true,
                        title: {
                            text: this.$tc('nfx-analytics.view.salesAllPwreturn.returnColumn')
                        },
                        labels:{
                            formatter: (value) => parseInt(value, 10)
                        }
                    }
                ]
            };
        },

        chartSeriesData() {
            let chartData = this.defaultChartSeriesData(this.seriesData, 'date', 'newGross',this.$tc('nfx-analytics.view.salesAllPwreturn.newGrossColumn'), true);
            if (Array.isArray(chartData)) {
                chartData.push(this.defaultChartSeriesData(this.seriesData, 'date', 'return',this.$tc('nfx-analytics.view.salesAllPwreturn.returnColumn'), true)[0]);
            }

            return chartData;
        },

        getGridColumns() {
            return [{
                property: 'date',
                dataIndex: 'date',
                label: this.$tc('nfx-analytics.view.orderSalesMonthly.dateColumn'),
                allowResize: false,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'count',
                dataIndex: 'count',
                label: this.$tc('nfx-analytics.view.countColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sumGross',
                dataIndex: 'sumGross',
                label: this.$tc('nfx-analytics.view.sumColumn') + this.$tc('nfx-analytics.view.gross'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sumNet',
                dataIndex: 'sumNet',
                label: this.$tc('nfx-analytics.view.sumColumn') + this.$tc('nfx-analytics.view.net'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'quantity',
                dataIndex: 'quantity',
                label: this.$tc('nfx-analytics.view.salesAllInvoice.quantityColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sumNetAverage',
                dataIndex: 'sumNetAverage',
                label: this.$tc('nfx-analytics.view.salesAllInvoice.sumNetAverageColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'quantityAverage',
                dataIndex: 'quantityAverage',
                label: this.$tc('nfx-analytics.view.salesAllInvoice.quantityAverageColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'refund',
                dataIndex: 'refund',
                label: this.$tc('nfx-analytics.view.salesAllPwreturn.refundColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'return',
                dataIndex: 'return',
                label: this.$tc('nfx-analytics.view.salesAllPwreturn.returnColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'newGross',
                dataIndex: 'newGross',
                label: this.$tc('nfx-analytics.view.salesAllPwreturn.newGrossColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'newQuantity',
                dataIndex: 'newQuantity',
                label: this.$tc('nfx-analytics.view.salesAllPwreturn.newQuantityColumn'),
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
                date: '_',
                count: 0,
                sumGross: 0,
                sumNet: 0,
                quantity: 0,
                sumNetAverage: 0,
                quantityAverage: 0,
                refund: 0,
                return: 0,
                newGross: 0,
                newQuantity: 0
            };
            this.gridData.forEach((item) => {
                summery.count += item.count;
                summery.sumGross += item.sumGross;
                summery.sumNet += item.sumNet;
                summery.quantity += item.quantity;
                summery.refund += item.refund;
                summery.return += item.return;
                summery.newGross += item.newGross;
                summery.newQuantity += item.newQuantity;
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

            if (this.activeStatistic.name === 'sales_by_month_pwreturn' && this.activeStatistic.pathInfo) {
                httpClient.post(this.activeStatistic.pathInfo,
                    { parameters },
                    { headers: { Authorization: `Bearer ${loginService.getToken()}`,} }
                    ).then((response) => {
                    if (parameters.format === 'csv' && response.data !== undefined && response.data['success'] === true && response.data['fileSize']) {
                        this.$emit('nfx-statistics-csv_done');
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
                        this.$emit('nfx-statistics-csv_done');
                    }
                });
            }

        }
    }
});
