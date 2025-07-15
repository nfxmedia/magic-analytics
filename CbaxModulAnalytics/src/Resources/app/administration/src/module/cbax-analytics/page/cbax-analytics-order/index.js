import template from './cbax-analytics-order.html.twig';

const { Component, Context, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('cbax-analytics-order', {
    template,

    inject: [
        'repositoryFactory',
        'systemConfigApiService',
        'acl',
        'filterFactory',
        'stateStyleDataProviderService'
    ],

    mixins: [
        Mixin.getByName('cbax-analytics'),
        Mixin.getByName('listing'),
    ],

    data() {
        return {
            orders: [],
            sortBy: 'orderDateTime',
            sortDirection: 'DESC',
            isLoading: false,
            filterLoading: false,
            availableAffiliateCodes: [],
            availableCampaignCodes: [],
            availablePromotionCodes: [],
            filterCriteria: [],
            activeStatisticLabel: '',
            systemCurrency: null,
            defaultFilters: [
                'affiliate-code-filter',
                'campaign-code-filter',
                'promotion-code-filter',
                'document-filter',
                'order-date-filter',
                'order-value-filter',
                'status-filter',
                'payment-status-filter',
                'delivery-status-filter',
                'payment-method-filter',
                'shipping-method-filter',
                'sales-channel-filter',
                'billing-country-filter',
                'customer-group-filter',
                'shipping-country-filter',
                'tag-filter',
                'line-item-filter',
                'manufacturer-filter'
            ],
            storeKey: 'grid.filter.cbax.analytics.order',
            activeFilterNumber: 0,
            searchConfigEntity: 'order'
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        currencyRepository() {
            return this.repositoryFactory.create('currency');
        },

        orderRepository() {
            return this.repositoryFactory.create('order');
        },

        orderColumns() {
            return this.getOrderColumns();
        },

        orderCriteria() {
            const criteria = new Criteria(this.page, this.limit);

            criteria.setTerm(this.term);

            this.sortBy.split(',').forEach(sortBy => {
                criteria.addSorting(Criteria.sort(sortBy, this.sortDirection));
            });

            this.filterCriteria.forEach(filter => {
                criteria.addFilter(filter);
            });

            criteria.addAssociation('addresses');
            criteria.addAssociation('billingAddress');
            criteria.addAssociation('salesChannel');
            criteria.addAssociation('orderCustomer');
            criteria.addAssociation('currency');
            criteria.addAssociation('documents');
            criteria.addAssociation('transactions');
            criteria.addAssociation('deliveries');
            criteria.addAssociation('stateMachineState');

            criteria
                .getAssociation('transactions')
                .addAssociation('stateMachineState')
                .addSorting(Criteria.sort('createdAt'));

            criteria
                .getAssociation('deliveries')
                .addAssociation('stateMachineState')
                .addAssociation('shippingOrderAddress')
                .addAssociation('shippingMethod')
                .addSorting(Criteria.sort('shippingCosts.unitPrice', 'DESC'));

            return criteria;
        },

        filterSelectCriteria() {
            const criteria = new Criteria(1, 1);

            criteria.addAggregation(Criteria.terms('affiliateCodes', 'affiliateCode', null, null, null));
            criteria.addAggregation(Criteria.terms('campaignCodes', 'campaignCode', null, null, null));
            criteria.addAggregation(Criteria.terms('promotionCodes', 'lineItems.payload.code', null, null, null));


            return criteria;
        },

        listFilterOptions() {
            return {
                'sales-channel-filter': {
                    property: 'salesChannel',
                    label: this.$tc('sw-order.filters.salesChannelFilter.label'),
                    placeholder: this.$tc('sw-order.filters.salesChannelFilter.placeholder'),
                },
                'order-value-filter': {
                    property: 'amountTotal',
                    type: 'number-filter',
                    label: this.$tc('sw-order.filters.orderValueFilter.label'),
                    fromFieldLabel: null,
                    toFieldLabel: null,
                    fromPlaceholder: this.$tc('global.default.from'),
                    toPlaceholder: this.$tc('global.default.to'),
                },
                'payment-status-filter': {
                    property: 'transactions.stateMachineState',
                    criteria: this.getStatusCriteria('order_transaction.state'),
                    label: this.$tc('sw-order.filters.paymentStatusFilter.label'),
                    placeholder: this.$tc('sw-order.filters.paymentStatusFilter.placeholder'),
                },
                'delivery-status-filter': {
                    property: 'deliveries.stateMachineState',
                    criteria: this.getStatusCriteria('order_delivery.state'),
                    label: this.$tc('sw-order.filters.deliveryStatusFilter.label'),
                    placeholder: this.$tc('sw-order.filters.deliveryStatusFilter.placeholder'),
                },
                'status-filter': {
                    property: 'stateMachineState',
                    criteria: this.getStatusCriteria('order.state'),
                    label: this.$tc('sw-order.filters.statusFilter.label'),
                    placeholder: this.$tc('sw-order.filters.statusFilter.placeholder'),
                },
                'order-date-filter': {
                    property: 'orderDateTime',
                    label: this.$tc('sw-order.filters.orderDateFilter.label'),
                    dateType: 'date',
                    fromFieldLabel: null,
                    toFieldLabel: null,
                    showTimeframe: true,
                },
                'tag-filter': {
                    property: 'tags',
                    label: this.$tc('sw-order.filters.tagFilter.label'),
                    placeholder: this.$tc('sw-order.filters.tagFilter.placeholder'),
                },
                'affiliate-code-filter': {
                    property: 'affiliateCode',
                    type: 'multi-select-filter',
                    label: this.$tc('sw-order.filters.affiliateCodeFilter.label'),
                    placeholder: this.$tc('sw-order.filters.affiliateCodeFilter.placeholder'),
                    valueProperty: 'key',
                    labelProperty: 'key',
                    options: this.availableAffiliateCodes,
                },
                'campaign-code-filter': {
                    property: 'campaignCode',
                    type: 'multi-select-filter',
                    label: this.$tc('sw-order.filters.campaignCodeFilter.label'),
                    placeholder: this.$tc('sw-order.filters.campaignCodeFilter.placeholder'),
                    valueProperty: 'key',
                    labelProperty: 'key',
                    options: this.availableCampaignCodes,
                },
                'promotion-code-filter': {
                    property: 'lineItems.payload.code',
                    type: 'multi-select-filter',
                    label: this.$tc('sw-order.filters.promotionCodeFilter.label'),
                    placeholder: this.$tc('sw-order.filters.promotionCodeFilter.placeholder'),
                    valueProperty: 'key',
                    labelProperty: 'key',
                    options: this.availablePromotionCodes,
                },
                'document-filter': {
                    property: 'documents',
                    label: this.$tc('sw-order.filters.documentFilter.label'),
                    placeholder: this.$tc('sw-order.filters.documentFilter.placeholder'),
                    optionHasCriteria: this.$tc('sw-order.filters.documentFilter.textHasCriteria'),
                    optionNoCriteria: this.$tc('sw-order.filters.documentFilter.textNoCriteria'),
                },
                'payment-method-filter': {
                    property: 'transactions.paymentMethod',
                    label: this.$tc('sw-order.filters.paymentMethodFilter.label'),
                    placeholder: this.$tc('sw-order.filters.paymentMethodFilter.placeholder'),
                },
                'shipping-method-filter': {
                    property: 'deliveries.shippingMethod',
                    label: this.$tc('sw-order.filters.shippingMethodFilter.label'),
                    placeholder: this.$tc('sw-order.filters.shippingMethodFilter.placeholder'),
                },
                'billing-country-filter': {
                    property: 'billingAddress.country',
                    label: this.$tc('sw-order.filters.billingCountryFilter.label'),
                    placeholder: this.$tc('sw-order.filters.billingCountryFilter.placeholder'),
                },
                'shipping-country-filter': {
                    property: 'deliveries.shippingOrderAddress.country',
                    label: this.$tc('sw-order.filters.shippingCountryFilter.label'),
                    placeholder: this.$tc('sw-order.filters.shippingCountryFilter.placeholder'),
                },
                'customer-group-filter': {
                    property: 'orderCustomer.customer.group',
                    label: this.$tc('sw-order.filters.customerGroupFilter.label'),
                    placeholder: this.$tc('sw-order.filters.customerGroupFilter.placeholder'),
                },
                'line-item-filter': {
                    property: 'lineItems.product',
                    label: this.$tc('sw-order.filters.productFilter.label'),
                    placeholder: this.$tc('sw-order.filters.productFilter.placeholder'),
                    criteria: this.productCriteria,
                    displayVariants: true
                },
                'manufacturer-filter': {
                    property: 'lineItems.product.manufacturer',
                    label: this.$tc('sw-product.filters.manufacturerFilter.label'),
                    placeholder: this.$tc('sw-product.filters.manufacturerFilter.placeholder')
                }
            };
        },

        listFilters() {
            return this.filterFactory.create('order', this.listFilterOptions);
        },

        productCriteria() {
            const productCriteria = new Criteria(1, 25);
            productCriteria.addAssociation('options.group');
            productCriteria.addAssociation('manufacturer');

            return productCriteria;
        },
    },

    watch: {
        orderCriteria: {
            handler() {
                this.getList();
            },
            deep: true,
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.currencyRepository
                .get(Shopware.Context.app.systemCurrencyId, Context.api)
                .then((systemCurrency) => {
                    this.systemCurrency = systemCurrency;
                });

            if (this.$route && this.$route.query && this.$route.query.label) {
                this.activeStatisticLabel = this.$route.query.label;
            }
            this.loadFilterValues();
        },

        onChangeLanguage() {
            this.getList();
        },

        async getList() {
            this.isLoading = true;
            this.disableRouteParams = true;

            let criteria = await Shopware.Service('filterService')
                .mergeWithStoredFilters(this.storeKey, this.orderCriteria);

            criteria = await this.addQueryScores(this.term, criteria);

            this.activeFilterNumber = criteria.filters.length;

            if (!this.entitySearchable) {
                this.isLoading = false;
                this.total = 0;

                return;
            }

            if (this.freshSearchTerm) {
                criteria.resetSorting();
            }

            try {
                Context.api.inheritance = true;
                const response = await this.orderRepository.search(criteria, Context.api);

                this.total = response.total;
                this.orders = response;
                this.isLoading = false;

            } catch {
                this.isLoading = false;
            }
        },

        getBillingAddress(order) {
            return order.addresses.find((address) => {
                return address.id === order.billingAddressId;
            });
        },

        getOrderColumns() {
            return [{
                property: 'orderNumber',
                label: 'sw-order.list.columnOrderNumber',
                routerLink: 'sw.order.detail',
                allowResize: true,
                primary: true,
            }, {
                property: 'salesChannel.name',
                label: 'sw-order.list.columnSalesChannel',
                allowResize: true,
            }, {
                property: 'orderCustomer.firstName',
                dataIndex: 'orderCustomer.lastName,orderCustomer.firstName',
                label: 'sw-order.list.columnCustomerName',
                allowResize: true,
            }, {
                property: 'billingAddressId',
                dataIndex: 'billingAddress.street',
                label: 'sw-order.list.columnBillingAddress',
                visible: false,
                allowResize: true,
            }, {
                property: 'deliveries.id',
                dataIndex: 'deliveries.shippingOrderAddress.street',
                label: 'sw-order.list.columnDeliveryAddress',
                allowResize: true,
                visible: false
            }, {
                property: 'amountTotal',
                label: this.$tc('sw-order.list.columnAmount') + this.$tc('cbax-analytics.view.gross'),
                align: 'right',
                allowResize: true,
            }, {
                property: 'amountNet',
                label: this.$tc('sw-order.list.columnAmount') + this.$tc('cbax-analytics.view.net'),
                align: 'right',
                allowResize: true,
            }, {
                //Profit
                property: 'id',
                label: this.$tc('cbax-analytics.order.profitColumn') + this.$tc('cbax-analytics.view.net'),
                allowResize: true,
                visible: true,
            }, {
                property: 'stateMachineState.name',
                label: 'sw-order.list.columnState',
                allowResize: true,
            }, {
                property: 'transactions.last().stateMachineState.name',
                dataIndex: 'transactions.stateMachineState.name',
                label: 'sw-order.list.columnTransactionState',
                allowResize: true,
            }, {
                property: 'deliveries[0].stateMachineState.name',
                dataIndex: 'deliveries.stateMachineState.name',
                label: 'sw-order.list.columnDeliveryState',
                allowResize: true,
            }, {
                property: 'orderDateTime',
                label: 'sw-order.list.orderDate',
                allowResize: true,
            }, {
                property: 'affiliateCode',
                inlineEdit: 'string',
                label: 'sw-order.list.columnAffiliateCode',
                allowResize: true,
                visible: false,
            }, {
                property: 'campaignCode',
                inlineEdit: 'string',
                label: 'sw-order.list.columnCampaignCode',
                allowResize: true,
                visible: false,
            }];
        },

        exportCSV() {
            if (Array.isArray(this.orders) && this.orders.length > 0) {

                let data = [];
                this.orders.forEach((order) => {
                    let item = {};
                    item[this.$tc('sw-order.list.columnOrderNumber')] = order.orderNumber;
                    item[this.$tc('sw-order.list.columnSalesChannel')] = order.salesChannel.name;
                    item[this.$tc('sw-order.list.columnCustomerName')] = order.orderCustomer.lastName + ', ' + order.orderCustomer.firstName;
                    item[this.$tc('sw-order.list.columnBillingAddress')] = order.billingAddress.street + ', ' + order.billingAddress.zipcode + ' ' + order.billingAddress.city;
                    item[this.$tc('sw-order.list.columnAmount') + this.$tc('cbax-analytics.view.gross')] = Shopware.Filter.getByName('currency')(order.amountTotal, order.currency.isoCode, 2);
                    item[this.$tc('sw-order.list.columnAmount') + this.$tc('cbax-analytics.view.net')] = Shopware.Filter.getByName('currency')(order.amountNet, order.currency.isoCode, 2);
                    item[this.$tc('sw-order.list.columnState')] = order.stateMachineState.name;
                    item[this.$tc('sw-order.list.columnTransactionState')] = order.transactions.last().stateMachineState.name;
                    item[this.$tc('sw-order.list.columnDeliveryState')] = order.deliveries[0].stateMachineState.name;
                    item[this.$tc('sw-order.list.orderDate')] = order.orderDateTime;
                    item[this.$tc('sw-order.list.columnAffiliateCode')] = order.affiliateCode;
                    item[this.$tc('sw-order.list.columnCampaignCode')] = order.campaignCode;

                    data.push(item);
                });

                const initContainer = Shopware.Application.getContainer('init');
                const httpClient = initContainer.httpClient;
                const loginService = Shopware.Service('loginService');

                if (data.length > 0) {
                    httpClient.post('cbax/analytics/csvExport',
                        {data},
                        {headers: {Authorization: `Bearer ${loginService.getToken()}`,}}
                    ).then((response) => {
                        if (response.data !== undefined && response.data['success'] === true && response.data['fileSize']) {
                            this.csvDownload('orderdata.csv', response.data['fileSize']);
                        }

                    }).catch((err) => {
                        console.log(err);
                    });
                }
            }
        },

        getVariantFromOrderState(order) {
            const style = this.stateStyleDataProviderService.getStyle('order.state', order.stateMachineState.technicalName);

            return style.colorCode;
        },

        getVariantFromPaymentState(order) {
            let technicalName = order.transactions.last().stateMachineState.technicalName;
            // set the payment status to the first transaction that is not cancelled
            for (let i = 0; i < order.transactions.length; i += 1) {
                if (!['cancelled', 'failed'].includes(order.transactions[i].stateMachineState.technicalName)) {
                    technicalName = order.transactions[i].stateMachineState.technicalName;
                    break;
                }
            }

            const style = this.stateStyleDataProviderService.getStyle('order_transaction.state', technicalName);

            return style.colorCode;
        },

        getVariantFromDeliveryState(order) {
            const style = this.stateStyleDataProviderService.getStyle(
                'order_delivery.state',
                order.deliveries[0].stateMachineState.technicalName,
            );

            return style.colorCode;
        },

        loadFilterValues() {
            this.filterLoading = true;

            return this.orderRepository.search(this.filterSelectCriteria).then(({ aggregations }) => {
                const { affiliateCodes, campaignCodes, promotionCodes } = aggregations;

                this.availableAffiliateCodes = affiliateCodes?.buckets.filter(({ key }) => key.length > 0) ?? [];
                this.availableCampaignCodes = campaignCodes?.buckets.filter(({ key }) => key.length > 0) ?? [];
                this.availablePromotionCodes = promotionCodes?.buckets.filter(({ key }) => key.length > 0) ?? [];

                this.filterLoading = false;

                return aggregations;
            }).catch(() => {
                this.filterLoading = false;
            });
        },

        updateCriteria(criteria) {
            this.page = 1;
            if (Array.isArray(criteria) && criteria.length > 0) {
                for (let i = 0; i < criteria.length; i++) {
                    if (typeof criteria[i] === 'object' && criteria[i].field && criteria[i].field === 'orderDateTime') {
                        if (criteria[i].parameters && criteria[i].parameters.lte) {
                            criteria[i].parameters.lte = criteria[i].parameters.lte.replace('00:00:00', '23:59:59');
                        }
                        break;
                    }
                }
            }
            this.$nextTick(() => {
                this.filterCriteria = criteria;
            });
        },

        getStatusCriteria(value) {
            const criteria = new Criteria(1, 25);

            criteria.addFilter(Criteria.equals('stateMachine.technicalName', value));

            return criteria;
        },

        transaction(item) {
            for (let i = 0; i < item.transactions.length; i += 1) {
                if (!['cancelled', 'failed'].includes(item.transactions[i].stateMachineState.technicalName)) {
                    return item.transactions[i];
                }
            }

            return item.transactions.last();
        },

        deliveryTooltip(deliveries) {
            return deliveries
                .map((delivery) => {
                    return `${delivery.shippingOrderAddress.street},
                        ${delivery.shippingOrderAddress.zipcode}
                        ${delivery.shippingOrderAddress.city}`;
                })
                .join('<hr style="margin: 8px 0">');
        }
    }
});
