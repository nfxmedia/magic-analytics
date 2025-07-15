import template from './cbax-analytics-entity-multi-select.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.extend('cbax-analytics-entity-multi-select', 'sw-entity-multi-id-select', {
    template,

    inject: { repositoryFactory: 'repositoryFactory'},

    props: {
        repository: {
            type: Object,
            required: false,
            default() {
                return this.repositoryFactory.create(this.entity);
            }
        },

        entity: {
            required: true,
            type: String
        },

        name: {
            required: true,
            type: String
        }
    },

    methods: {
        createdComponent() {
            //this.repository = this.repositoryFactory.create(this.entity);

            if (this.name === 'CbaxModulAnalytics.config.blacklistedOrderStates') {
                this.criteria.addAssociation('stateMachine');
                this.criteria.addFilter(Criteria.equals('stateMachine.technicalName', 'order.state'));
            } else if (this.name === 'CbaxModulAnalytics.config.blacklistedTransactionStates') {
                this.criteria.addAssociation('stateMachine');
                this.criteria.addFilter(Criteria.equals('stateMachine.technicalName', 'order_transaction.state'));
            } else if (this.name === 'CbaxModulAnalytics.config.blacklistedDeliveryStates') {
                this.criteria.addAssociation('stateMachine');
                this.criteria.addFilter(Criteria.equals('stateMachine.technicalName', 'order_delivery.state'));
            } else if (this.name === 'CbaxModulAnalytics.config.defaultSaleschannels' || this.name === 'CbaxModulAnalytics.config.dashboardSaleschannels') {
                const storefrontSalesChannelTypeId = '8A243080F92E4C719546314B577CF82B';
                const apiSalesChannelTypeId = 'f183ee5650cf4bdb8a774337575067a6'; //headless
                const pickwarePOS = 'd18beabacf894e14b507767f4358eeb0'; //Pickware POS plugin Saleschannel typ
                this.criteria.addFilter(Criteria.equalsAny('typeId', [storefrontSalesChannelTypeId, apiSalesChannelTypeId, pickwarePOS]));
            }

            this.$super('createdComponent');
        }
    }
});
