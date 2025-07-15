import template from './cbax-analytics-index-sales-by-category.html.twig';
import './cbax-analytics-index-sales-by-category.scss';

const { Component, Context, Mixin } = Shopware;
const { EntityCollection } = Shopware.Data;

Component.register('cbax-analytics-index-sales-by-category', {
    template,

    inject: [
        'repositoryFactory'
    ],

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
            categoryCollection: null
        };
        return {
            isLoading: true,
            seriesData: null,
            gridData: null,
            total: 0,
            page: 1,
            limit: 25,
            chartType: 'pie',
            categoryCollection: null,
            categoryId: null,
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

        categoryRepository() {
            return this.repositoryFactory.create('category');
        },

        chartOptions() {
            return this.pieChartOptions('cbax-analytics.view.salesByCategory.titleChart', this.systemCurrency);
        },

        chartSeriesData() {
            return this.defaultChartSeriesData(this.seriesData, 'name', 'sales', 'Bestellungen', false, true);
        },

        getGridColumns() {
            return [{
                property: 'number',
                dataIndex: 'number',
                label: this.$tc('cbax-analytics.view.productNumberColumn'),
                allowResize: false,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'name',
                dataIndex: 'name',
                label: this.$tc('cbax-analytics.view.productNameColumn'),
                allowResize: false,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sum',
                dataIndex: 'sum',
                label: this.$tc('cbax-analytics.view.salesCountColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sales',
                dataIndex: 'sales',
                label: this.$tc('cbax-analytics.view.sumColumn') + this.$tc('cbax-analytics.view.' + this.grossOrNet),
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

            if (!this.categoryCollection) {
                this.categoryCollection = new EntityCollection(
                    this.categoryRepository.route,
                    this.categoryRepository.entityName,
                    Context.api,
                );
            }

            if (!this.categoryId) {
                this.$nextTick(() => {
                    this.isLoading = false;
                });
            }

            if (this.categoryId) {
                const initContainer = Shopware.Application.getContainer('init');
                const httpClient = initContainer.httpClient;
                const loginService = Shopware.Service('loginService');

                this.chartType = this.displayOptions.chartType ?? 'pie';

                let parameters = this.getBasicParameters(this.displayOptions, this.format, this.activeStatistic.name);
                parameters.categoryId = this.categoryId;
                if (this.format === 'csv') {
                    parameters.labels = this.getGridLabels(this.getGridColumns);
                }

                if (this.activeStatistic.name === 'sales_by_category' && this.activeStatistic.pathInfo) {
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
            }
        },

        onCategoryAdd(item) {
            this.categoryId = item.id;
            this.createdComponent()
        },

        onCategoryRemove() {
            this.categoryId = null;
        }
    }
});
