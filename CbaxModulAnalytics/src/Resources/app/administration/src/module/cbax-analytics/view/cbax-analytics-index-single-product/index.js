import template from './cbax-analytics-index-single-product.html.twig';
import './cbax-analytics-index-single-product.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('cbax-analytics-index-single-product', {
    template,

    mixins: [
        Mixin.getByName('cbax-analytics')
    ],

    inject: [
        'repositoryFactory'
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
            productId: null
        };
        return {
            isLoading: false,
            seriesData: null,
            gridData: null,
            total: 0,
            page: 1,
            limit: 25,
            chartType: 'pie',
            productId: '',
            productName: '',
            overall: 0,
            overallCount: 0,
            errors: errors,
            compareIds: [],
            compareNames: {},
            seriesCompareData: null
        };
    },

    computed: {
        singleSelectCriteria() {
            let criteria = new Criteria();
            if (this.displayOptions.showVariantParent) {
                criteria.addFilter(Criteria.equals('parentId', null));
            } else {
                criteria.addFilter(Criteria.multi(
                    'OR',
                    [
                        Criteria.equals('childCount', 0),
                        Criteria.equals('childCount', null),
                    ],
                ),);
            }

            return criteria;
        },

        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        labelProperty() {
            return ['name', 'productNumber'];
        },

        chartOptions() {
            return {
                title: {
                    text: this.$tc('cbax-analytics.view.singleProduct.titleChart') + ' - ' + this.productName
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
                    labels: {
                        datetimeUTC: false
                    }
                },
                yaxis: {
                    min:0,
                    forceNiceScale: true,
                }
            };
        },

        chartClickOptions() {
            return {
                title: {
                    text: this.$tc('cbax-analytics.view.singleProduct.titleClicksChart')
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
                    labels: {
                        datetimeUTC: false
                    }
                },
                yaxis: {
                    min:0,
                    forceNiceScale: true,
                }
            };
        },

        chartSeriesData() {
            if (!this.seriesData) {
                return null;
            }

            let seriesData, chartSeries = [];

            seriesData = this.seriesData.map((data) => {
                return { x: this.parseDate(data.date), y: data.count };
            });

            chartSeries.push({ name: this.productName, data: seriesData });

            if (this.seriesCompareData && this.seriesCompareData.length > 0) {
                for (let i = 0; i < this.seriesCompareData.length ; i++) {
                    seriesData = this.seriesCompareData[i].map((data1) => {
                        return { x: this.parseDate(data1.date), y: data1.count };
                    });

                    chartSeries.push({ name: this.compareNames[i], data: seriesData });

                    if (i === this.seriesCompareData.length - 1) {
                        return chartSeries;
                    }
                }
            } else {
                return chartSeries;
            }
        },

        chartClickData() {
            if (!this.seriesData) {
                return null;
            }

            let seriesData, chartSeries = [];

            seriesData = this.seriesData.map((data) => {
                return { x: this.parseDate(data.date), y: data.clicks };
            });

            chartSeries.push({ name: this.productName, data: seriesData });

            if (this.seriesCompareData && this.seriesCompareData.length > 0) {
                for (let i = 0; i < this.seriesCompareData.length ; i++) {
                    seriesData = this.seriesCompareData[i].map((data1) => {
                        return { x: this.parseDate(data1.date), y: data1.clicks };
                    });

                    chartSeries.push({ name: this.compareNames[i], data: seriesData });

                    if (i === this.seriesCompareData.length - 1) {
                        return chartSeries;
                    }
                }
            } else {
                return chartSeries;
            }
        },

        getGridColumns() {
            return [{
                property: 'formatedDate',
                dataIndex: 'formatedDate',
                label: this.$tc('cbax-analytics.view.singleProduct.dateColumn'),
                allowResize: false,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'count',
                dataIndex: 'count',
                label: this.$tc('cbax-analytics.view.singleProduct.countColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'clicks',
                dataIndex: 'clicks',
                label: this.$tc('cbax-analytics.view.singleProduct.clicksColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }];
        },

        gridSeriesData() {
            return this.defaultGridSeriesData(this.gridData, this.page, this.limit);
        },

        productRepository() {
            return this.repositoryFactory.create('product');
        },

        compareIdsDisabled() {
            return !this.productId || this.productId === '';
        }
    },

    watch: {
        displayOptions: {
            handler() {
                this.createdComponent();
            }
        },

        'displayOptions.showVariantParent': {
            handler() {
                this.isLoading = true;
                this.productId = '';
                this.seriesData = null;
                this.gridData = null;
                this.total = 0;
                this.page = 1;
                this.productName = '';
                this.overall = 0;
                this.overallCount = 0;
                this.compareIds = [];
                this.compareNames = {};
                this.seriesCompareData = null;
                this.$nextTick(() => {
                    this.isLoading = false;
                });
            },
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

            if (!this.productId || this.productId === '') {
                this.isLoading = false;

            } else {

                const initContainer = Shopware.Application.getContainer('init');
                const httpClient = initContainer.httpClient;
                const loginService = Shopware.Service('loginService');

                let parameters = this.getBasicParameters(this.displayOptions, this.format, this.activeStatistic.name);
                parameters.productId = this.productId;
                parameters.compareIds = this.compareIds;
                parameters.labels = this.getGridLabels(this.getGridColumns);

                if (this.activeStatistic.name === 'single_product' && this.activeStatistic.pathInfo) {
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
                            this.productName = response.data['productName'];
                            this.total = response.data['gridData'].length;
                            this.gridData = response.data['gridData'];
                            this.seriesCompareData = response.data['seriesCompareData'];
                            this.compareNames = response.data['compareProductNames'];
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
            }
        },

        onChangeProductSelectField(event) {
            this.createdComponent();
        },

        onChangeCompareSelectField(event) {
            this.createdComponent();
        }
    }
});
