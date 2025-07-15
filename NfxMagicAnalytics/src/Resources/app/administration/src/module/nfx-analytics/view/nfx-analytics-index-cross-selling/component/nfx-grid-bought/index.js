import template from './nfx-grid-bought.html.twig';

const { Component, Mixin } = Shopware;

Component.register('nfx-grid-bought', {
    template,

    mixins: [
        Mixin.getByName('nfx-analytics')
    ],

    props: {
        data: {
            type: Object,
            required: false,
            default: null
        }
    },

    data() {
        return {
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
                property: 'productName',
                dataIndex: 'productName',
                label: this.$tc('nfx-analytics.view.crossSelling.name'),
                allowResize: false,
                align: 'left',
                inlineEdit: false,
                width: '250px'
            }, {
                property: 'sales',
                dataIndex: 'sales',
                label: this.$tc('nfx-analytics.view.crossSelling.sales'),
                allowResize: false,
                align: 'left',
                inlineEdit: false,
                width: '80px'
            }, {
                property: 'crossSellings',
                dataIndex: 'crossSellings',
                label: this.$tc('nfx-analytics.view.crossSelling.crossSellings'),
                allowResize: false,
                align: 'left',
                inlineEdit: false,
                width: '220px'
            }];
        },

        dataTotal() {
            if (!this.data) {
                return 0;
            } else {
                return this.data.length
            }
        },

        gridData() {
            if (!this.data) {
                return null;
            }

            return this.data.slice((this.page - 1) * this.limit, this.limit * this.page);
        }
    }

});
