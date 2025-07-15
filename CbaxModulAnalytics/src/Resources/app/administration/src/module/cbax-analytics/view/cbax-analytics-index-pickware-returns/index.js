import template from './cbax-analytics-index-pickware-returns.html.twig';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-pickware-returns', {
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
            sortDirection: 'DESC',
            sortBy: 'sum'
        };
    },

    computed: {
        getGridColumns() {
            return [{
                property: 'number',
                dataIndex: 'number',
                label: this.$tc('cbax-analytics.view.salesByProducts.numberColumn'),
                allowResize: false,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'name',
                dataIndex: 'name',
                label: this.$tc('cbax-analytics.view.salesByProducts.nameColumn'),
                allowResize: false,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'sum',
                dataIndex: 'sum',
                label: this.$tc('cbax-analytics.view.salesByProducts.sumColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'returnSum',
                dataIndex: 'returnSum',
                label: this.$tc('cbax-analytics.view.salesByProductsPwreturn.returnSumColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'part',
                dataIndex: 'part',
                label: this.$tc('cbax-analytics.view.pickwareReturns.partColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'manufacturer',
                dataIndex: 'manufacturer',
                label: this.$tc('cbax-analytics.view.manufacturer'),
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
            parameters.sortBy = this.sortBy;
            parameters.sortDirection = this.sortDirection;

            if (this.activeStatistic.name === 'pickware_returns' && this.activeStatistic.pathInfo) {
                httpClient.post(this.activeStatistic.pathInfo,
                    { parameters },
                    { headers: { Authorization: `Bearer ${loginService.getToken()}`,} }
                    ).then((response) => {
                    if (parameters.format === 'csv' && response.data !== undefined && response.data['success'] === true && response.data['fileSize']) {
                        this.$emit('cbax-statistics-csv_done');
                        this.csvDownload(this.activeStatistic.name + '.csv', response.data['fileSize']);
                    }
                    if (response.data !== undefined && response.data['success'] === true && response.data['gridData']) {
                        let responseGridData = this.setOthersLabel(response.data['gridData']);
                        this.total = response.data['gridData'].length;
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
