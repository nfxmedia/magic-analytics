import './mixin/nfx-analytics.mixin';
import './component/nfx-analytics-display-options';
import './component/nfx-analytics-tree';
import './component/nfx-analytics-more-filters';
import './component/nfx-analytics-entity-multi-select';
import './component/nfx-analytics-dashboard-statistics';
import './component/nfx-analytics-dashboard-settings';
import './component/acl';
import './component/nfx-analytics-summery';
import './component/nfx-analytics-ip-select';
import './component/nfx-analytics-order-profit';
import './component/nfx-theme-switcher';
import './component/nfx-animated-counter';
import './component/nfx-progress-ring';
import './component/nfx-wave-progress';
import './component/nfx-morphing-number';

import './page/nfx-analytics-order';
import './page/nfx-analytics-index';
import './page/nfx-analytics-product';
import './page/nfx-analytics-animation-demo';

import './view/nfx-analytics-index-sales-by-payment-method';
import './view/nfx-analytics-index-sales-by-manufacturer';
import './view/nfx-analytics-index-sales-by-shipping-method';
import './view/nfx-analytics-index-orders-count-all';
import './view/nfx-analytics-index-sales-all';
import './view/nfx-analytics-index-sales-all-invoice';
import './view/nfx-analytics-index-sales-all-pwreturn';
import './view/nfx-analytics-index-sales-by-month';
import './view/nfx-analytics-index-sales-by-month-invoice';
import './view/nfx-analytics-index-sales-by-month-pwreturn';
import './view/nfx-analytics-index-sales-by-quarter';
import './view/nfx-analytics-index-sales-by-quarter-invoice';
import './view/nfx-analytics-index-sales-by-quarter-pwreturn';
import './view/nfx-analytics-index-sales-by-products';
import './view/nfx-analytics-index-sales-by-products-pwreturn';
import './view/nfx-analytics-index-sales-by-country';
import './view/nfx-analytics-index-sales-by-language';
import './view/nfx-analytics-index-count-by-products';
import './view/nfx-analytics-index-sales-by-saleschannel';
import './view/nfx-analytics-index-sales-by-affiliate';
import './view/nfx-analytics-index-sales-by-campaign';
import './view/nfx-analytics-index-sales-by-customergroups';
import './view/nfx-analytics-index-sales-by-weekdays';
import './view/nfx-analytics-index-sales-by-time';
import './view/nfx-analytics-index-orders-by-status';
import './view/nfx-analytics-index-product-low-instock';
import './view/nfx-analytics-index-product-high-instock';
import './view/nfx-analytics-index-sales-by-promotion';
import './view/nfx-analytics-index-sales-by-promotion-others';
import './view/nfx-analytics-index-product-inactive-with-instock';
import './view/nfx-analytics-index-product-by-orders';
import './view/nfx-analytics-index-sales-by-customer';
import './view/nfx-analytics-index-new-customers-by-time';
import './view/nfx-analytics-index-customer-age';
import './view/nfx-analytics-index-product-soon-outstock';
import './view/nfx-analytics-index-orders-by-transaction-status';
import './view/nfx-analytics-index-orders-by-delivery-status';
import './view/nfx-analytics-index-quick-overview';
import './view/nfx-analytics-index-unfinished-orders';
import './view/nfx-analytics-index-unfinished-orders-by-payment';
import './view/nfx-analytics-index-unfinished-orders-by-cart';
import './view/nfx-analytics-index-canceled-orders-by-month';
import './view/nfx-analytics-index-search-terms';
import './view/nfx-analytics-index-search-activity';
import './view/nfx-analytics-index-sales-by-device';
import './view/nfx-analytics-index-sales-by-os';
import './view/nfx-analytics-index-sales-by-browser';
import './view/nfx-analytics-index-products-inventory';
import './view/nfx-analytics-index-products-profit';
import './view/nfx-analytics-index-sales-by-billing-country';
import './view/nfx-analytics-index-sales-by-billing-country-invoice';
import './view/nfx-analytics-index-sales-by-currency';
import './view/nfx-analytics-index-lexicon-impressions';
import './view/nfx-analytics-index-customer-online';
import './view/nfx-analytics-index-conversion-all';
import './view/nfx-analytics-index-conversion-by-month';
import './view/nfx-analytics-index-sales-by-category';
import './view/nfx-analytics-index-sales-by-products-filter';
import './view/nfx-analytics-index-variants-compare';
import './view/nfx-analytics-index-product-impressions';
import './view/nfx-analytics-index-visitors';
import './view/nfx-analytics-index-visitor-impressions';
import './view/nfx-analytics-index-referer';
import './view/nfx-analytics-index-category-impressions';
import './view/nfx-analytics-index-manufacturer-impressions';
import './view/nfx-analytics-index-single-product';
import './view/nfx-analytics-index-cross-selling';
import './view/nfx-analytics-index-customer-by-salutation';
import './view/nfx-analytics-index-sales-by-salutation';
import './view/nfx-analytics-index-sales-by-taxrate';
import './view/nfx-analytics-index-category-compare';
import './view/nfx-analytics-index-sales-by-account-types';
import './view/nfx-analytics-index-pickware-returns';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Module.register('nfx-analytics', {
    type: 'plugin',
    name: 'nfx-analytics.general.name',
    title: 'nfx-analytics.general.title',
    description: 'nfx-analytics.general.description',
    color: '#ff68b4',
    icon: 'regular-flask',
    entity: 'nfx_analytics_config',
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

	routes: {
        index: {
            components: {
                default: 'nfx-analytics-index'
            },
            path: 'index',
			meta: {
                parentPath: 'nfx.analytics.index',
                 privilege: 'nfxAnalytics.viewer'
            }
        },
        order: {
            components: {
                default: 'nfx-analytics-order'
            },
            path: 'order',
            meta: {
                parentPath: 'nfx.analytics.index',
                privilege: 'nfxAnalytics.viewer'
            }
        },
        product: {
            components: {
                default: 'nfx-analytics-product'
            },
            path: 'product',
            meta: {
                parentPath: 'nfx.analytics.index',
                privilege: 'nfxAnalytics.viewer'
            }
        },
        animationDemo: {
            components: {
                default: 'nfx-analytics-animation-demo'
            },
            path: 'animation-demo',
            meta: {
                parentPath: 'nfx.analytics.index',
                privilege: 'nfxAnalytics.viewer'
            }
        }
    },


    navigation: [{
		id: 'nfx-analytics',
        label: 'nfx-analytics.general.navigationLabel',
        color: '#ff68b4',
		icon: 'default-object-lab-flask',
        path: 'nfx.analytics.index',
		position: 100,
        parent: 'sw-dashboard',
        privilege: 'nfxAnalytics.viewer'
    }]
});
