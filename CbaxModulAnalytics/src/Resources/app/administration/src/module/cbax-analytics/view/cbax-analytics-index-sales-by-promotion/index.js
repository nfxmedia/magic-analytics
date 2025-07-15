import template from './cbax-analytics-index-sales-by-promotion.html.twig';
import './cbax-analytics-index-sales-by-promotion.scss';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-sales-by-promotion', {
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
            filterName: 'promotion-code',
            isLoading: false,
            seriesData: null,
            gridData: null,
            seriesDataPromotion: null,
            gridDataPromotion: null,
            total: 0,
            page: 1,
            limit: 25,
            chartType: 'pie',
            sortDirection: 'DESC',
            sortBy: 'sum',
            totalPromotion: 0,
            pagePromotion: 1,
            limitPromotion: 25,
            sortDirectionPromotion: 'DESC',
            sortByPromotion: 'sum'
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        chartOptions() {
            return this.pieChartOptions('cbax-analytics.view.salesByPromotion.titleChart', this.systemCurrency);
        },

        chartSeriesData() {
            return this.defaultChartSeriesData(this.seriesData, 'name', 'sum','Bestellungen', false, true);
        },

        getGridColumns() {
            return [{
                property: 'name',
                dataIndex: 'name',
                label: this.$tc('cbax-analytics.view.salesByPromotion.nameColumn'),
                allowResize: false,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'code',
                dataIndex: 'code',
                label: this.$tc('cbax-analytics.view.salesByPromotion.codeColumn'),
                allowResize: false,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'discount',
                dataIndex: 'discount',
                label: this.$tc('cbax-analytics.view.salesByPromotion.discountColumn'),
                allowResize: false,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'count',
                dataIndex: 'count',
                label: this.$tc('cbax-analytics.view.salesByPromotion.countColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sum',
                dataIndex: 'sum',
                label: this.$tc('cbax-analytics.view.salesByPromotion.sumColumn') + this.$tc('cbax-analytics.view.' + this.grossOrNet),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'avg',
                dataIndex: 'avg',
                label: this.$tc('cbax-analytics.view.salesByPromotion.avgColumn') + this.$tc('cbax-analytics.view.' + this.grossOrNet),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }];
        },

        gridSeriesData() {
            return this.defaultGridSeriesData(this.gridData, this.page, this.limit);
        },

        gridSeriesDataPromotion() {
            return this.defaultGridSeriesData(this.gridDataPromotion, this.pagePromotion, this.limitPromotion);
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

            if (this.activeStatistic.name === 'sales_by_promotion' && this.activeStatistic.pathInfo) {
                httpClient.post(this.activeStatistic.pathInfo,
                    { parameters },
                    { headers: { Authorization: `Bearer ${loginService.getToken()}`,} }
                    ).then((response) => {
                    if (parameters.format === 'csv' && response.data !== undefined && response.data['success'] === true && response.data['fileSize']) {
                        this.$emit('cbax-statistics-csv_done');
                        this.csvDownload(this.activeStatistic.name + '.csv', response.data['fileSize']);
                    }
                    if (
                        response.data !== undefined &&
                        response.data['success'] === true &&
                        response.data['seriesDataCode'] &&
                        response.data['gridDataCode'] &&
                        response.data['seriesDataPromotion'] &&
                        response.data['gridDataPromotion']
                    ) {
                        this.total = response.data['gridDataCode'].length;
                        this.seriesData = response.data['seriesDataCode'];
                        this.gridData =  response.data['gridDataCode'];

                        this.totalPromotion = response.data['gridDataPromotion'].length;
                        this.seriesDataPromotion = response.data['seriesDataPromotion'];
                        this.gridDataPromotion =  response.data['gridDataPromotion'];
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

        onPageChangePromotion(opts) {
            this.pagePromotion = opts.page;
            this.limitPromotion = opts.limit;
        },

        onColumnSortPromotion(column) {
            this.isLoading = true;
            let last = null;
            if (this.sortByPromotion === column.dataIndex) {
                this.sortDirectionPromotion = this.sortDirectionPromotion === 'ASC' ? 'DESC' : 'ASC';
            } else {
                this.sortByPromotion = column.dataIndex;
            }
            if (this.gridDataPromotion.length === 0) {
                this.$nextTick(() => {
                    this.isLoading = false;
                });
                return;
            }
            if (this.gridDataPromotion.length > 0 && this.gridDataPromotion[this.gridDataPromotion.length -1].name === this.$tc('cbax-analytics.data.others')) {
                last = this.gridDataPromotion.pop();
            }
            if (this.sortDirectionPromotion === 'ASC') {
                if (this.gridDataPromotion[0] && this.gridDataPromotion[0][this.sortByPromotion] && typeof this.gridDataPromotion[0][this.sortByPromotion] === 'string') {
                    this.gridDataPromotion.sort((a, b) => {
                        const nameA = a[this.sortByPromotion].toUpperCase(); // ignore upper and lowercase
                        const nameB = b[this.sortByPromotion].toUpperCase(); // ignore upper and lowercase
                        if (nameA > nameB) {
                            return 1;
                        }
                        if (nameA < nameB) {
                            return -1;
                        }
                        // names must be equal
                        return 0;
                    })
                } else {
                    this.gridDataPromotion.sort((a, b) => a[this.sortByPromotion] - b[this.sortByPromotion]);
                }

            } else {
                if (this.gridDataPromotion[0] && this.gridDataPromotion[0][this.sortByPromotion] && typeof this.gridDataPromotion[0][this.sortByPromotion] === 'string') {
                    this.gridDataPromotion.sort((a, b) => {
                        const nameA = a[this.sortByPromotion].toUpperCase(); // ignore upper and lowercase
                        const nameB = b[this.sortByPromotion].toUpperCase(); // ignore upper and lowercase
                        if (nameA > nameB) {
                            return -1;
                        }
                        if (nameA < nameB) {
                            return 1;
                        }
                        // names must be equal
                        return 0;
                    })
                } else {
                    this.gridDataPromotion.sort((a, b) => b[this.sortByPromotion] - a[this.sortByPromotion]);
                }

            }
            setTimeout(() => {
                if (last) {
                    this.gridDataPromotion.push(last);
                }
                this.$nextTick(() => {
                    this.isLoading = false;
                });
            }, 400);
        }
    }
});
