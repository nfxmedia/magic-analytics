import template from './nfx-analytics-ip-select.html.twig';

const { Component } = Shopware;

Component.extend('nfx-analytics-ip-select', 'sw-multi-tag-ip-select', {
    template,

    inject: [
        'knownIpsService'
    ],

    props: {
        value: {
            type: Array,
            required: false,
            default() {
                return [];
            }
        }
    },

    created() {
        this.knownIpsService.getKnownIps().then(ips => {
            this._.props.knownIps = ips;
        });
    },
});
