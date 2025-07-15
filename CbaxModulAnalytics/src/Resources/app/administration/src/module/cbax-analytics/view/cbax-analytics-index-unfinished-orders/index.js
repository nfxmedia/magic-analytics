import template from './cbax-analytics-index-unfinished-orders.html.twig';
import './cbax-analytics-index-unfinished-orders.scss';

const { Component, Mixin } = Shopware;

Component.register('cbax-analytics-index-unfinished-orders', {
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
        }
    },

    data() {
        return {
            lineItemsModalOpen: false,
            isLoading: false,
            gridData: null,
            total: 0,
            page: 1,
            limit: 25,
            modalItems:[]
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        getModalGridColumns() {
            return [{
                property: 'label',
                dataIndex: 'label',
                label: this.$tc('cbax-analytics.view.unfinishedOrders.modal.labelColumn'),
                allowResize: false,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'productNumber',
                dataIndex: 'productNumber',
                label: this.$tc('cbax-analytics.product.productNumber'),
                allowResize: false,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'unitPrice',
                dataIndex: 'unitPrice',
                label: this.$tc('cbax-analytics.view.unfinishedOrders.modal.unitPriceColumn'),
                allowResize: false,
                inlineEdit: false,
                align: 'right',
                width: '100px'
            }, {
                property: 'quantity',
                dataIndex: 'quantity',
                label: this.$tc('cbax-analytics.view.unfinishedOrders.modal.quantityColumn'),
                allowResize: false,
                inlineEdit: false,
                align: 'right',
                width: '100px'
            }, {
                property: 'totalPrice',
                dataIndex: 'totalPrice',
                label: this.$tc('cbax-analytics.view.unfinishedOrders.modal.totalPriceColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'type',
                dataIndex: 'type',
                label: this.$tc('cbax-analytics.view.unfinishedOrders.modal.typeColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }];
        },

        getGridColumns() {
            return [{
                property: 'date',
                dataIndex: 'date',
                label: this.$tc('cbax-analytics.view.unfinishedOrders.dateColumn'),
                allowResize: false,
                primary: true,
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'gross',
                dataIndex: 'gross',
                label: this.$tc('cbax-analytics.view.unfinishedOrders.salesColumn') + ' ' + this.$tc('cbax-analytics.view.gross'),
                allowResize: false,
                inlineEdit: false,
                align: 'right',
                width: '100px'
            }, {
                property: 'net',
                dataIndex: 'net',
                label: this.$tc('cbax-analytics.view.unfinishedOrders.salesColumn') + ' ' + this.$tc('cbax-analytics.view.net'),
                allowResize: false,
                inlineEdit: false,
                align: 'right',
                width: '100px'
            }, {
                property: 'position',
                dataIndex: 'position',
                label: this.$tc('cbax-analytics.view.unfinishedOrders.positionColumn'),
                allowResize: false,
                inlineEdit: false,
                align: 'right',
                width: '100px'
            }, {
                property: 'itemCount',
                dataIndex: 'itemCount',
                label: this.$tc('cbax-analytics.view.unfinishedOrders.itemCountColumn'),
                allowResize: false,
                inlineEdit: false,
                align: 'right',
                width: '100px'
            }, {
                property: 'name',
                dataIndex: 'name',
                label: this.$tc('cbax-analytics.view.unfinishedOrders.nameColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'email',
                dataIndex: 'email',
                label: this.$tc('cbax-analytics.view.unfinishedOrders.emailColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'phone',
                dataIndex: 'phone',
                label: this.$tc('cbax-analytics.view.unfinishedOrders.phoneColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'payment',
                dataIndex: 'payment',
                label: this.$tc('cbax-analytics.view.unfinishedOrders.paymentColumn'),
                allowResize: false,
                align: 'right',
                inlineEdit: false,
                width: '100px'
            }, {
                property: 'salesChannel',
                dataIndex: 'salesChannel',
                label: this.$tc('cbax-analytics.view.unfinishedOrders.salesChannelColumn'),
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

            if (this.activeStatistic.name === 'unfinished_orders' && this.activeStatistic.pathInfo) {
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

        },

        openLineItemsModal(event, item) {
            if (item.lineItems && item.lineItems.length > 0) {
                this.isLoading = true;
                this.modalItems = item.lineItems;
                this.lineItemsModalOpen = true;
                this.isLoading = false;
            }
        },

        onCloseLineItemsModal(event) {
            this.lineItemsModalOpen = false;
            this.modalItems = [];
        }
    }
});
