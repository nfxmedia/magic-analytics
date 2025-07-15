import template from './cbax-analytics-index-products-profit.html.twig';
import './cbax-analytics-index-products-profit.scss';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-products-profit', {
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
        },
        grossOrNet: {
            type: String,
            required: false,
            default: 'net'
        }
    },

    data() {
        return {
            filterName: 'line-item',
            hideNoSales: false,
            isLoading: true,
            gridData: null,
            total: 0,
            page: 1,
            limit: 25,
            overall: 0.00,
            sortDirection: 'DESC',
            sortBy: 'profit'
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        getGridColumns() {
            return [{
                property: 'number',
                dataIndex: 'number',
                label: this.$tc('cbax-analytics.view.productsProfit.numberColumn'),
                allowResize: true,
                primary: true,
                inlineEdit: false,
                width: '90px'
            }, {
                property: 'name',
                dataIndex: 'name',
                label: this.$tc('cbax-analytics.view.productsProfit.nameColumn'),
                allowResize: true,
                inlineEdit: false,
                width: '120px'
            }, {
                property: 'profit',
                dataIndex: 'profit',
                label: this.$tc('cbax-analytics.view.productsProfit.profitColumn') + this.$tc('cbax-analytics.view.' + this.grossOrNet),
                allowResize: true,
                align: 'right',
                inlineEdit: false,
                width: '50px'
            }, {
                property: 'markUp',
                dataIndex: 'markUp',
                label: this.$tc('cbax-analytics.view.productsProfit.markUp'),
                allowResize: true,
                align: 'right',
                inlineEdit: false,
                width: '30px'
            }, {
                property: 'sum',
                dataIndex: 'sum',
                label: this.$tc('cbax-analytics.view.productsProfit.sumColumn'),
                allowResize: true,
                align: 'right',
                inlineEdit: false,
                width: '30px'
            }, {
                property: 'pprice',
                dataIndex: 'pprice',
                label: this.$tc('cbax-analytics.view.productsProfit.ppriceColumn') + this.$tc('cbax-analytics.view.' + this.grossOrNet),
                allowResize: true,
                align: 'right',
                inlineEdit: false,
                width: '45px'
            }, {
                property: 'cprice',
                dataIndex: 'cprice',
                label: this.$tc('cbax-analytics.view.productsProfit.cpriceColumn') + this.$tc('cbax-analytics.view.' + this.grossOrNet),
                allowResize: true,
                align: 'right',
                inlineEdit: false,
                width: '45px'
            }];
        },

        gridSeriesData() {
            let data;
            if (!this.gridData) return null;
            if (this.gridData && this.hideNoSales) {
                data = this.gridData.filter((entry) => entry.sum > 0);
                this.total = data.length;
                if (this.page > Math.ceil(this.total / this.limit)) this.page = 1;
                return this.defaultGridSeriesData(data, this.page, this.limit);
            } else {
                this.total = this.gridData.length;
                return this.defaultGridSeriesData(this.gridData, this.page, this.limit);
            }
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
            this.page = 1;

            const initContainer = Shopware.Application.getContainer('init');
            const httpClient = initContainer.httpClient;
            const loginService = Shopware.Service('loginService');

            let parameters = this.getBasicParameters(this.displayOptions, this.format, this.activeStatistic.name);
            if (this.format === 'csv') {
                parameters.labels = this.getGridLabels(this.getGridColumns);
            }
            parameters.sortBy = this.sortBy;
            parameters.sortDirection = this.sortDirection;

            if (this.activeStatistic.name === 'products_profit' && this.activeStatistic.pathInfo) {
                httpClient.post(this.activeStatistic.pathInfo,
                    { parameters },
                    { headers: { Authorization: `Bearer ${loginService.getToken()}`,} }
                    ).then((response) => {
                    if (parameters.format === 'csv' && response.data !== undefined && response.data['success'] === true && response.data['fileSize']) {
                        this.$emit('cbax-statistics-csv_done');
                        this.csvDownload(this.activeStatistic.name + '.csv', response.data['fileSize']);
                    }
                    if (response.data !== undefined && response.data['success'] === true && response.data['gridData']) {
                        let responseGridData = this.setOthersLabel(response.data['gridData']);
                        this.total = response.data['gridData'].length;
                        this.overall = response.data['overall'];
                        this.gridData =  responseGridData;
                    }

                    this.$nextTick(() => {
                        this.isLoading = false;
                    });

                }).catch((err) => {
                    this.isLoading = false;
                    if (parameters.format === 'csv') {
                        this.$emit('cbax-statistics-csv_done');
                    }
                });
            }

        },

        onChangeField() {
            this.createdComponent();
        },

        onColumnSort(column) {
            if (this.sortBy === column.dataIndex) {
                this.sortDirection = this.sortDirection === 'ASC' ? 'DESC' : 'ASC';
            } else {
                this.sortBy = column.dataIndex;
            }

            if (this.gridData.length > 0) {
                this.createdComponent();
            }
        }
    }
});
