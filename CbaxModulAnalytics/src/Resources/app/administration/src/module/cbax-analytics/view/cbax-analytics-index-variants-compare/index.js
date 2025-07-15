import template from './cbax-analytics-index-variants-compare.html.twig';
import './cbax-analytics-index-variants-compare.scss';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-variants-compare', {
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
            productStreamId: null,
            categoryId: null
        };
        return {
            isLoading: false,
            seriesData: null,
            gridData: null,
            total: 0,
            page: 1,
            limit: 25,
            chartType: 'pie',
            categoryId: '',
            propertyGroupId: '',
            overall: {},
            errors: errors
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        chartOptions() {
            return this.pieChartOptions('cbax-analytics.view.variantsCompare.titleChart', this.systemCurrency);
        },

        chartSeriesData() {
            return this.defaultChartSeriesData(this.seriesData, 'name', 'sales', 'Bestellungen', false, true);
        },

        getGridColumns() {
            return [{
                property: 'name',
                dataIndex: 'name',
                label: this.$tc('cbax-analytics.view.variantsCompare.nameColumn'),
                allowResize: false,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sum',
                dataIndex: 'sum',
                label: this.$tc('cbax-analytics.view.variantsCompare.sumColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '80px'
            }, {
                property: 'sales',
                dataIndex: 'sales',
                label: this.$tc('cbax-analytics.view.variantsCompare.salesColumn') + this.$tc('cbax-analytics.view.' + this.grossOrNet),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '50px'
            }, {
                property: 'count',
                dataIndex: 'count',
                label: this.$tc('cbax-analytics.view.variantsCompare.productCountColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '50px'
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
            Shopware.State.commit('adminMenu/collapseSidebar');

            this.isLoading = true;

            if (!this.propertyGroupId || this.propertyGroupId === '') {
                this.isLoading = false;
                return;
            }

            const initContainer = Shopware.Application.getContainer('init');
            const httpClient = initContainer.httpClient;
            const loginService = Shopware.Service('loginService');

            this.chartType = this.displayOptions.chartType ?? 'pie';

            let parameters = this.getBasicParameters(this.displayOptions, this.format, this.activeStatistic.name);
            parameters.propertyGroupId = this.propertyGroupId;
            parameters.categoryId = this.categoryId;
            if (this.format === 'csv') {
                parameters.labels = this.getGridLabels(this.getGridColumns);
            }

            if (this.activeStatistic.name === 'variants_compare' && this.activeStatistic.pathInfo) {
                httpClient.post(this.activeStatistic.pathInfo,
                    { parameters },
                    { headers: { Authorization: `Bearer ${loginService.getToken()}`,} }
                    ).then((response) => {
                    if (parameters.format === 'csv' && response.data !== undefined && response.data['success'] === true && response.data['fileSize']) {
                        this.$emit('cbax-statistics-csv_done');
                        this.csvDownload(this.activeStatistic.name + '.csv', response.data['fileSize']);
                    }
                    if (response.data !== undefined && response.data['success'] === true && response.data['seriesData'] && response.data['gridData']) {
                        this.total = response.data['gridData'].length;
                        let responseSeriesData = this.setOthersLabel(response.data['seriesData']);
                        let responseGridData = this.setOthersLabel(response.data['gridData']);
                        this.seriesData = responseSeriesData;
                        this.gridData =  responseGridData;
                        this.overall = response.data['overall'];
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

        onChangeGroupField() {
            this.createdComponent();
        },

        onChangeCategoryField() {
            this.createdComponent();
        },

        setOthersLabel(data) {
            for (let i = 0; i < data.length; i++) {
                if (data[i].name == 'cbax-analytics.data.others') {
                    data[i].name = this.$tc('cbax-analytics.data.others');
                }
            }

            return data;
        },
    }
});
