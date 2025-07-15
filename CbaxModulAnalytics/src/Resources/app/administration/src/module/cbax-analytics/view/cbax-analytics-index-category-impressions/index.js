import template from './cbax-analytics-index-category-impressions.html.twig';
import './cbax-analytics-index-category-impressions.scss';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-category-impressions', {
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
            seriesData: null,
            gridData: null,
            total: 0,
            page: 1,
            limit: 25,
            chartType: 'pie',
            overallData: null,
            sortDirection: 'DESC',
            sortBy: 'sum'
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        chartOptions() {
            return this.pieChartOptions('cbax-analytics.view.categoryImpressions.titleChart');
        },

        chartSeriesData() {
            return this.defaultChartSeriesData(this.seriesData, 'name', 'sum','Impressions');
        },

        getGridColumns() {
            return [{
                property: 'name',
                dataIndex: 'name',
                label: this.$tc('cbax-analytics.view.categoryImpressions.nameColumn'),
                allowResize: false,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sum1',
                dataIndex: 'sum1',
                label: this.$tc('cbax-analytics.view.categoryImpressions.sum1Column'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sum2',
                dataIndex: 'sum2',
                label: this.$tc('cbax-analytics.view.categoryImpressions.sum2Column'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sum',
                dataIndex: 'sum',
                label: this.$tc('cbax-analytics.view.categoryImpressions.sumColumn'),
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

            this.chartType = this.displayOptions.chartType ?? 'pie';

            let parameters = this.getBasicParameters(this.displayOptions, this.format, this.activeStatistic.name);
            if (this.format === 'csv') {
                parameters.labels = this.getGridLabels(this.getGridColumns);
            }

            if (this.activeStatistic.name === 'category_impressions' && this.activeStatistic.pathInfo) {
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
                        this.overallData = response.data['overall'];
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
