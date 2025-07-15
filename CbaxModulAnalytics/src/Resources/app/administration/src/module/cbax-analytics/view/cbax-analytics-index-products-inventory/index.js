import template from './cbax-analytics-index-products-inventory.html.twig';
import './cbax-analytics-index-products-inventory.scss';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-products-inventory', {
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
        },
        grossOrNet: {
            type: String,
            required: false,
            default: 'net'
        }
    },

    data() {
        return {
            isLoading: false,
            gridData: null,
            total: 0,
            page: 1,
            limit: 25,
            overall: 0.00,
            sortDirection: 'DESC',
            sortBy: 'worth'
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
                label: this.$tc('cbax-analytics.view.productsInventory.numberColumn'),
                allowResize: true,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'name',
                dataIndex: 'name',
                label: this.$tc('cbax-analytics.view.productsInventory.nameColumn'),
                allowResize: true,
                inlineEdit: false,
                width: '120px'
            }, {
                property: 'sum',
                dataIndex: 'sum',
                label: this.$tc('cbax-analytics.view.productsInventory.sumColumn'),
                allowResize: true,
                align: 'right',
                inlineEdit: false,
                width: '40px'
            }, {
                property: 'pprice',
                dataIndex: 'pprice',
                label: this.$tc('cbax-analytics.view.productsInventory.ppriceColumn') + this.$tc('cbax-analytics.view.' + this.grossOrNet),
                allowResize: true,
                align: 'right',
                inlineEdit: false,
                width: '65px'
            }, {
                property: 'worth',
                dataIndex: 'worth',
                label: this.$tc('cbax-analytics.view.productsInventory.worthColumn') + this.$tc('cbax-analytics.view.' + this.grossOrNet),
                allowResize: true,
                align: 'right',
                inlineEdit: false,
                width: '65px'
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

            if (this.activeStatistic.name === 'products_inventory' && this.activeStatistic.pathInfo) {
                httpClient.post(this.activeStatistic.pathInfo,
                    { parameters },
                    { headers: { Authorization: `Bearer ${loginService.getToken()}`,} }
                    ).then((response) => {
                    if (parameters.format === 'csv' && response.data !== undefined && response.data['success'] === true && response.data['fileSize']) {
                        this.$emit('cbax-statistics-csv_done');
                        this.csvDownload(this.activeStatistic.name + '.csv', response.data['fileSize']);
                    }
                    if (response.data !== undefined && response.data['success'] === true && response.data['gridData']) {
                        //let responseSeriesData = this.setOthersLabel(response.data['seriesData']);
                        let responseGridData = this.setOthersLabel(response.data['gridData']);
                        this.total = response.data['gridData'].length;
                        this.overall = response.data['overall'];
                        //this.seriesData = responseSeriesData;
                        this.gridData =  responseGridData;
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
