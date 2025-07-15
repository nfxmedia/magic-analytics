import template from './cbax-analytics-tree-item.html.twig';

const { Component } = Shopware;

Component.extend('cbax-analytics-tree-item','sw-tree-item', {
    template,

    methods: {
        addElement(item) {
            this.$emit('cbax-analytics-addFavorite', item);
        },

        deleteElement(item) {
            this.$emit('cbax-analytics-deleteFavorite', item);
        }
    }



});
