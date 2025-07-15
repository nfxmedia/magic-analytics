import template from './cbax-analytics-index-sales-by-products-filter.html.twig';
import './cbax-analytics-index-sales-by-products-filter.scss';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-sales-by-products-filter', {
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
        let errors = {
            productStreamId: null
        };
        return {
            filterName: 'line-item',
            isLoading: false,
            seriesData: null,
            gridData: null,
            total: 0,
            page: 1,
            limit: 25,
            chartType: 'pie',
            productStreamId: '',
            overall: 0,
            overallCount: 0,
            sortDirection: 'DESC',
            sortBy: 'sales',
            errors: errors
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        chartOptions() {
            return this.pieChartOptions('cbax-analytics.view.salesByProductsFilter.titleChart', this.systemCurrency);
        },

        chartSeriesData() {
            return this.defaultChartSeriesData(this.seriesData, 'name', 'sales', 'Bestellungen', false, true);
        },

        getGridColumns() {
            return [{
                property: 'number',
                dataIndex: 'number',
                label: this.$tc('cbax-analytics.view.salesByProductsFilter.numberColumn'),
                allowResize: false,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'name',
                dataIndex: 'name',
                label: this.$tc('cbax-analytics.view.salesByProductsFilter.nameColumn'),
                allowResize: false,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sum',
                dataIndex: 'sum',
                label: this.$tc('cbax-analytics.view.salesByProductsFilter.sumColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sales',
                dataIndex: 'sales',
                label: this.$tc('cbax-analytics.view.salesByProductsFilter.salesColumn') + this.$tc('cbax-analytics.view.' + this.grossOrNet),
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

            if (!this.productStreamId || this.productStreamId === '') {
                this.isLoading = false;
                return;
            }

            const initContainer = Shopware.Application.getContainer('init');
            const httpClient = initContainer.httpClient;
            const loginService = Shopware.Service('loginService');

            this.chartType = this.displayOptions.chartType ?? 'pie';

            let parameters = this.getBasicParameters(this.displayOptions, this.format, this.activeStatistic.name);
            parameters.productStreamId = this.productStreamId;
            if (this.format === 'csv') {
                parameters.labels = this.getGridLabels(this.getGridColumns);
            }
            parameters.sortBy = this.sortBy;
            parameters.sortDirection = this.sortDirection;

            if (this.activeStatistic.name === 'sales_by_products_filter' && this.activeStatistic.pathInfo) {
                httpClient.post(this.activeStatistic.pathInfo,
                    { parameters },
                    { headers: { Authorization: `Bearer ${loginService.getToken()}`,} }
                    ).then((response) => {
                    if (parameters.format === 'csv' && response.data !== undefined && response.data['success'] === true && response.data['fileSize']) {
                        this.$emit('cbax-statistics-csv_done');
                        this.csvDownload(this.activeStatistic.name + '.csv', response.data['fileSize']);
                    }
                    if (response.data !== undefined && response.data['success'] === true && response.data['seriesData'] && response.data['gridData']) {
                        let responseSeriesData = this.setOthersLabel(response.data['seriesData']);
                        let responseGridData = this.setOthersLabel(response.data['gridData']);
                        this.total = response.data['gridData'].length;
                        this.seriesData = responseSeriesData;
                        this.gridData =  responseGridData;
                        this.overall = response.data['overall'];
                        this.overallCount = response.data['overallCount'];
                    }

                    this.isLoading = false;

                }).catch((err) => {
                    this.isLoading = false;
                    if (parameters.format === 'csv') {
                        this.$emit('cbax-statistics-csv_done');
                    }
                });
            }

        },

        onChangeStreamField($event, field) {
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
