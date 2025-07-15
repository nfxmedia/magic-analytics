import template from './cbax-analytics-index-product-soon-outstock.html.twig';
import './cbax-analytics-index-product-soon-outstock.scss';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-product-soon-outstock', {
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
            gridData: null,
            total: 0,
            page: 1,
            limit: 25,
            sortDirection: 'ASC',
            sortBy: 'sum'
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
                label: this.$tc('cbax-analytics.view.productSoonOutstock.numberColumn'),
                allowResize: false,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'name',
                dataIndex: 'name',
                label: this.$tc('cbax-analytics.view.productSoonOutstock.nameColumn'),
                allowResize: false,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sum',
                dataIndex: 'sum',
                label: this.$tc('cbax-analytics.view.productSoonOutstock.sumColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'stock',
                dataIndex: 'stock',
                label: this.$tc('cbax-analytics.view.productSoonOutstock.stockColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sold',
                dataIndex: 'sold',
                label: this.$tc('cbax-analytics.view.productSoonOutstock.soldColumn'),
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

            if (this.activeStatistic.name === 'product_soon_outstock' && this.activeStatistic.pathInfo) {
                httpClient.post(this.activeStatistic.pathInfo,
                    { parameters },
                    { headers: { Authorization: `Bearer ${loginService.getToken()}`,} }
                    ).then((response) => {
                    if (parameters.format === 'csv' && response.data !== undefined && response.data['success'] === true && response.data['fileSize']) {
                        this.$emit('cbax-statistics-csv_done');
                        this.csvDownload(this.activeStatistic.name + '.csv', response.data['fileSize']);
                    }
                    if (response.data !== undefined && response.data['success'] === true && response.data['gridData']) {
                        this.total = response.data['gridData'].length;
                        this.gridData =  response.data['gridData'];
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
