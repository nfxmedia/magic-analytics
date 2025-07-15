import template from './cbax-analytics-index-quick-overview.html.twig';
import './cbax-analytics-index-quick-overview.scss';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-quick-overview', {
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
        }
    },

    data() {
        return {
            summeryData: [],
            summeryColumnNames: {},
            isLoading: false,
            gridData: null,
            total: 0,
            page: 1,
            limit: 25
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        getGridColumns() {
            return [{
                property: 'formatedDate',
                dataIndex: 'formatedDate',
                label: this.$tc('cbax-analytics.view.dateColumn'),
                allowResize: true,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'count',
                dataIndex: 'count',
                label: this.$tc('cbax-analytics.view.quickOverview.orderCountColumn'),
                allowResize: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'firstTimeCount',
                dataIndex: 'firstTimeCount',
                label: this.$tc('cbax-analytics.view.quickOverview.firstTimeCountColumn'),
                allowResize: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sales',
                dataIndex: 'sales',
                label: this.$tc('cbax-analytics.view.quickOverview.salesColumn'),
                allowResize: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'avg',
                dataIndex: 'avg',
                label: this.$tc('cbax-analytics.view.quickOverview.avgSalesColumn'),
                allowResize: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'netto',
                dataIndex: 'netto',
                label: this.$tc('cbax-analytics.view.quickOverview.nettoSalesColumn'),
                allowResize: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'new',
                dataIndex: 'new',
                label: this.$tc('cbax-analytics.view.quickOverview.newCustomersColumn'),
                allowResize: true,
                inlineEdit: false,
                align: 'right',
                width: '100px'
            }, {
                property: 'paying',
                dataIndex: 'paying',
                label: this.$tc('cbax-analytics.view.quickOverview.newPayingCustomersColumn'),
                allowResize: true,
                inlineEdit: false,
                align: 'right',
                width: '100px'
            }, {
                property: 'visitors',
                dataIndex: 'visitors',
                label: this.$tc('cbax-analytics.view.quickOverview.visitorsColumn'),
                allowResize: true,
                inlineEdit: false,
                align: 'right',
                width: '100px'
            }, {
                property: 'impressions',
                dataIndex: 'impressions',
                label: this.$tc('cbax-analytics.view.quickOverview.impressionsColumn'),
                allowResize: true,
                inlineEdit: false,
                align: 'right',
                width: '100px'
            }];
        },

        gridSeriesData() {
            return this.defaultGridSeriesData(this.gridData, this.page, this.limit);
        },
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

            this.summeryColumnNames = {
                count: this.$tc('cbax-analytics.view.quickOverview.orderCountColumn'),
                firstTimeCount: this.$tc('cbax-analytics.view.quickOverview.firstTimeCountColumn'),
                sales: this.$tc('cbax-analytics.view.quickOverview.salesColumn'),
                avgSales: this.$tc('cbax-analytics.view.quickOverview.avgSalesColumn'),
                netto: this.$tc('cbax-analytics.view.quickOverview.nettoSalesColumn'),
                new: this.$tc('cbax-analytics.view.quickOverview.newCustomersColumn'),
                paying: this.$tc('cbax-analytics.view.quickOverview.newPayingCustomersColumn'),
                visitors: this.$tc('cbax-analytics.view.quickOverview.visitorsColumn'),
                impressions: this.$tc('cbax-analytics.view.quickOverview.impressionsColumn')
            };

            const initContainer = Shopware.Application.getContainer('init');
            const httpClient = initContainer.httpClient;
            const loginService = Shopware.Service('loginService');

            let parameters = this.getBasicParameters(this.displayOptions, this.format, this.activeStatistic.name);
            if (this.format === 'csv') {
                parameters.labels = this.getGridLabels(this.getGridColumns);
            }

            if (this.activeStatistic.name === 'quick_overview' && this.activeStatistic.pathInfo) {
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
                        this.getOverallData(this.gridData);
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

        getOverallData(data) {
            this.summeryData = [];
            if (!data || data.length === 0) {
                return;
            }

            let overallData = {};
            
            overallData.count = 0;
            overallData.firstTimeCount = 0;
            overallData.sales = 0;
            overallData.netto = 0;
            overallData.new = 0;
            overallData.paying = 0;
            overallData.visitors = 0;
            overallData.impressions = 0;

            data.forEach((item) => {
                overallData.count += item.count;
                overallData.firstTimeCount += item.firstTimeCount;
                overallData.sales += item.sales;
                overallData.netto += item.netto;
                overallData.new += item.new;
                overallData.paying += item.paying;
                overallData.visitors += item.visitors;
                overallData.impressions += item.impressions;
            });

            overallData.avgSales = overallData.count === 0 ? 0 : overallData.sales/overallData.count;
            overallData.avgSales = this.currencyFilter(Math.round(overallData.avgSales*100)/100, this.systemCurrency.shortName, 2);
            overallData.sales = this.currencyFilter(Math.round(overallData.sales*100)/100, this.systemCurrency.shortName, 2);
            overallData.netto = this.currencyFilter(Math.round(overallData.netto*100)/100, this.systemCurrency.shortName, 2);

            this.summeryData.push(overallData);
        }
    }
});
