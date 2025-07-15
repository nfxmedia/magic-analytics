import template from './cbax-analytics-index-referer.html.twig';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-referer', {
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
            overallData: null
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },
        getGridColumns() {
            return [{
                property: 'date.date',
                dataIndex: 'date.date',
                label: this.$tc('cbax-analytics.view.referer.dateColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'referer',
                dataIndex: 'referer',
                label: this.$tc('cbax-analytics.view.referer.refererColumn'),
                allowResize: false,
                align: 'right',
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'deviceType',
                dataIndex: 'deviceType',
                label: this.$tc('cbax-analytics.view.referer.deviceTypeColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'counted',
                dataIndex: 'counted',
                label: this.$tc('cbax-analytics.view.referer.countedColumn'),
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

            if (this.activeStatistic.name === 'referer' && this.activeStatistic.pathInfo) {
                httpClient.post(this.activeStatistic.pathInfo,
                    { parameters },
                    { headers: { Authorization: `Bearer ${loginService.getToken()}`,} }
                    ).then((response) => {

                    if (parameters.format === 'csv' && response.data !== undefined && response.data['success'] === true && response.data['fileSize']) {
                        this.$emit('cbax-statistics-csv_done');
                        this.csvDownload(this.activeStatistic.name + '.csv', response.data['fileSize']);
                    }

                    if (response.data !== undefined && response.data['success'] === true && response.data['gridData']) {
                        this.gridData =  response.data['gridData'];
                        this.total = response.data['gridData'].length;
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
