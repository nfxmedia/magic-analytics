import template from './cbax-analytics-summery.html.twig';
import './cbax-analytics-summery.scss';

const { Component } = Shopware;

Component.register('cbax-analytics-summery', {
    template,

    props: {
        data: {
            type: Array,
            required: true,
            default: []
        },
        columns: {
            type: Object,
            required: true
        },
        headline: {
            type: String,
            default: ''
        }
    },

    computed: {
        gridData() {
            return this.data;
        },

        alertTitle() {
            return this.headline + (this.headline.length > 0 ? ' - ' : '') + this.$tc('cbax-analytics.index.sums');
        },

        gridColums() {
            let columns = [];
            for (const [key, value] of Object.entries(this.columns)) {
                columns.push({
                    property: key,
                    dataIndex: key,
                    label: value,
                    allowResize: false,
                    inlineEdit: false,
                    width: '100px'
                });
            }
            if (columns.length > 0) {
                columns[0].primary = true;
            }
            return columns;
        }
    }
});
