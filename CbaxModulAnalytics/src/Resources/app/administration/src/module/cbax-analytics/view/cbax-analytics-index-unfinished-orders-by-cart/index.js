import template from './cbax-analytics-index-unfinished-orders-by-cart.html.twig';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-unfinished-orders-by-cart', {
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
            required: true,
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
            isLoading: false,
            seriesData: null,
            gridData: null,
            total: 0,
            page: 1,
            limit: 25,
            minX: null,
            maxX: null,
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        chartOptions() {
            return {
                title: {
                    text: this.$tc('cbax-analytics.view.unfinishedOrdersByCart.titleChart')
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

        chartSeriesData() {
            if (!this.seriesData) {
                return null;
            }

            const seriesData = this.seriesData.map((data) => {
                return { x: this.parseDate(data.date), y: data.count };
            });

            return [{ name: 'Bestellungen', data: seriesData }];
        },

        getGridColumns() {
            return [{
                property: 'formatedDate',
                dataIndex: 'formatedDate',
                label: this.$tc('cbax-analytics.view.unfinishedOrdersByCart.dateColumn'),
                allowResize: false,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'count',
                dataIndex: 'count',
                label: this.$tc('cbax-analytics.view.unfinishedOrdersByCart.countColumn'),
                allowResize: false,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'gross',
                dataIndex: 'gross',
                label: this.$tc('cbax-analytics.view.unfinishedOrdersByCart.salesColumn') + ' ' + this.$tc('cbax-analytics.view.gross'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'net',
                dataIndex: 'net',
                label: this.$tc('cbax-analytics.view.unfinishedOrdersByCart.salesColumn') + ' ' + this.$tc('cbax-analytics.view.net'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'avgGross',
                dataIndex: 'avgGross',
                label: this.$tc('cbax-analytics.view.unfinishedOrdersByCart.avgColumn') + ' ' + this.$tc('cbax-analytics.view.gross'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'position',
                dataIndex: 'position',
                label: this.$tc('cbax-analytics.view.unfinishedOrdersByCart.positionColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'itemCount',
                dataIndex: 'itemCount',
                label: this.$tc('cbax-analytics.view.unfinishedOrdersByCart.itemCountColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'avgCount',
                dataIndex: 'avgCount',
                label: this.$tc('cbax-analytics.view.unfinishedOrdersByCart.avgItemCountColumn'),
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

            if (this.activeStatistic.name === 'unfinished_orders_by_cart' && this.activeStatistic.pathInfo) {
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
