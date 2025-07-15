import template from './cbax-analytics-order-profit.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('cbax-analytics-order-profit', {
    template,

    inject: [
        'repositoryFactory'
    ],

    props: {
        orderId: {
            type: String,
            required: true
        },
        isLoading: {
            type: Boolean,
            required: true
        },
        systemCurrency: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            order: null,
            profitLoading: true,
            profit: null
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        orderRepository() {
            return this.repositoryFactory.create('order');
        },

        orderCriteria() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('id', this.orderId));

            criteria.addAssociation('lineItems');

            criteria.getAssociation('lineItems')
                .addAssociation('product');

            return criteria;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.profitLoading = true;
            let netPositionPrice, purchaseNetAll = 0;

            this.orderRepository.search(this.orderCriteria).then((result) => {
                if (result && Array.isArray(result) && result.length === 1) {
                    this.order = result[0];
                    this.order.lineItems.forEach((item) => {
                        if (
                            item.product &&
                            Array.isArray(item.product.purchasePrices) &&
                            item.product.purchasePrices.length > 0 &&
                            item.product.purchasePrices[0].net
                        ) {
                            purchaseNetAll += item.product.purchasePrices[0].net * item.quantity;
                        }
                    });

                    if (purchaseNetAll === 0) {
                        return;
                    }

                    if (this.order.taxStatus === 'gross') {
                        netPositionPrice = this.order.price.netPrice;

                        if (this.order.shippingTotal > 0) {
                            netPositionPrice = netPositionPrice - this.order.shippingTotal;
                            if (this.order.shippingCosts?.calculatedTaxes && Array.isArray(this.order.shippingCosts.calculatedTaxes)) {
                                this.order.shippingCosts.calculatedTaxes.forEach((ct) => {
                                    if (ct.tax) {
                                        netPositionPrice += ct.tax;
                                    }
                                });
                            }
                        }
                    } else {
                        netPositionPrice = this.order.positionPrice;
                    }

                    if (this.order.currencyFactor > 0) {
                        netPositionPrice = netPositionPrice / this.order.currencyFactor;
                    }
                    this.profit = netPositionPrice - purchaseNetAll;
                }

                this.profitLoading = false;
            });
        },
    }
});
