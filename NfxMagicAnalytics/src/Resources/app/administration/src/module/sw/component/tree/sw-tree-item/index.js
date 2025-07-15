import template from './nfx-analytics-tree-item.html.twig';

const { Component } = Shopware;

Component.extend('nfx-analytics-tree-item','sw-tree-item', {
    template,

    methods: {
        addElement(item) {
            this.$emit('nfx-analytics-addFavorite', item);
        },

        deleteElement(item) {
            this.$emit('nfx-analytics-deleteFavorite', item);
        }
    }



});
