import template from './cbax-analytics-index-category-compare.html.twig';
import './cbax-analytics-index-category-compare.scss';

const { Component, Context, Mixin } = Shopware;
const { EntityCollection } = Shopware.Data;

Component.register('cbax-analytics-index-category-compare', {
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
            categoryLoading: true,
            seriesData: null,
            gridData: null,
            total: 0,
            page: 1,
            limit: 25,
            chartType: 'pie',
            categoryCollection: null,
            categories: [],
            sortDirection: 'DESC',
            sortBy: 'sum',
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
            return this.pieChartOptions('cbax-analytics.view.categoryCompare.titleChart', this.systemCurrency);
        },

        chartSeriesData() {
            return this.defaultChartSeriesData(this.seriesData, 'name', 'sales', 'Bestellungen', false, true);
        },

        getGridColumns() {
            return [{
                property: 'name',
                dataIndex: 'name',
                label: 'cbax-analytics.view.categoryNameColumn',
                allowResize: false,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sum',
                dataIndex: 'sum',
                label: 'cbax-analytics.view.salesCountColumn',
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
                this.categoryLoading = true;
                this.categoryCollection = new EntityCollection(
                    this.categoryRepository.route,
                    this.categoryRepository.entityName,
                    Context.api,
                );
                this.$nextTick(() => {
                    this.categoryLoading = false;
                });
            }

            if (!this.categories || (Array.isArray(this.categories) && this.categories.length === 0)) {
                this.$nextTick(() => {
                    this.isLoading = false;
                });
            }

            if (this.categories && this.categories.length > 0) {
                const initContainer = Shopware.Application.getContainer('init');
                const httpClient = initContainer.httpClient;
                const loginService = Shopware.Service('loginService');

                this.chartType = this.displayOptions.chartType ?? 'pie';

                let parameters = this.getBasicParameters(this.displayOptions, this.format, this.activeStatistic.name);
                parameters.categories = this.categories;

                if (this.format === 'csv') {
                    parameters.labels = this.getGridLabels(this.getGridColumns);
                }

                if (this.activeStatistic.name === 'category_compare' && this.activeStatistic.pathInfo) {
                    httpClient.post(this.activeStatistic.pathInfo,
                        { parameters },
                        { headers: { Authorization: `Bearer ${loginService.getToken()}`,} }
                    ).then((response) => {
                        if (parameters.format === 'csv' && response.data !== undefined && response.data['success'] === true && response.data['fileSize']) {
                            this.$emit('cbax-statistics-csv_done');
                            this.csvDownload(this.activeStatistic.name + '.csv', response.data['fileSize']);
                        }
                        if (response.data !== undefined && response.data['success'] === true && response.data['seriesData']) {
                            this.total = response.data['seriesData'].length;
                            this.seriesData = response.data['seriesData'];
                            this.gridData =  response.data['seriesData'];

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
            this.categories.push({
                id: item.id,
                name: item.translated?.name
            });
            if (this.categories.length > 1) {
                this.createdComponent();
            }
        },

        onCategoryRemove(item) {
            this.categories = this.categories.filter((cat) => cat.id !== item.id);
            if (this.categories.length > 1) {
                this.createdComponent();
            }
        }
    }
});
