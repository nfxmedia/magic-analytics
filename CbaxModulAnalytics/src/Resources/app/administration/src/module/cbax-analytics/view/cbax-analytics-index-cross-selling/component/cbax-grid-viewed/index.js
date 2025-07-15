import template from './cbax-grid-viewed.html.twig';

const { Component, Mixin } = Shopware;

Component.register('cbax-grid-viewed', {
    template,

    mixins: [
        Mixin.getByName('cbax-analytics')
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
                label: this.$tc('cbax-analytics.view.crossSelling.name'),
                allowResize: false,
                align: 'left',
                inlineEdit: false,
                width: '250px'
            }, {
                property: 'viewed',
                dataIndex: 'viewed',
                label: this.$tc('cbax-analytics.view.crossSelling.viewed'),
                allowResize: false,
                align: 'left',
                inlineEdit: false,
                width: '80px'
            }, {
                property: 'crossSellings',
                dataIndex: 'crossSellings',
                label: this.$tc('cbax-analytics.view.crossSelling.crossSellings'),
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
