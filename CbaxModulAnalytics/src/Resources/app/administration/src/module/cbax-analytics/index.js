import './mixin/cbax-analytics.mixin';
import './component/cbax-analytics-display-options';
import './component/cbax-analytics-tree';
import './component/cbax-analytics-more-filters';
import './component/cbax-analytics-entity-multi-select';
import './component/cbax-analytics-dashboard-statistics';
import './component/cbax-analytics-dashboard-settings';
import './component/acl';
import './component/cbax-analytics-summery';
import './component/cbax-analytics-ip-select';
import './component/cbax-analytics-order-profit';

import './page/cbax-analytics-order';
import './page/cbax-analytics-index';
import './page/cbax-analytics-product';

import './view/cbax-analytics-index-sales-by-payment-method';
import './view/cbax-analytics-index-sales-by-manufacturer';
import './view/cbax-analytics-index-sales-by-shipping-method';
import './view/cbax-analytics-index-orders-count-all';
import './view/cbax-analytics-index-sales-all';
import './view/cbax-analytics-index-sales-all-invoice';
import './view/cbax-analytics-index-sales-all-pwreturn';
import './view/cbax-analytics-index-sales-by-month';
import './view/cbax-analytics-index-sales-by-month-invoice';
import './view/cbax-analytics-index-sales-by-month-pwreturn';
import './view/cbax-analytics-index-sales-by-quarter';
import './view/cbax-analytics-index-sales-by-quarter-invoice';
import './view/cbax-analytics-index-sales-by-quarter-pwreturn';
import './view/cbax-analytics-index-sales-by-products';
import './view/cbax-analytics-index-sales-by-products-pwreturn';
import './view/cbax-analytics-index-sales-by-country';
import './view/cbax-analytics-index-sales-by-language';
import './view/cbax-analytics-index-count-by-products';
import './view/cbax-analytics-index-sales-by-saleschannel';
import './view/cbax-analytics-index-sales-by-affiliate';
import './view/cbax-analytics-index-sales-by-campaign';
import './view/cbax-analytics-index-sales-by-customergroups';
import './view/cbax-analytics-index-sales-by-weekdays';
import './view/cbax-analytics-index-sales-by-time';
import './view/cbax-analytics-index-orders-by-status';
import './view/cbax-analytics-index-product-low-instock';
import './view/cbax-analytics-index-product-high-instock';
import './view/cbax-analytics-index-sales-by-promotion';
import './view/cbax-analytics-index-sales-by-promotion-others';
import './view/cbax-analytics-index-product-inactive-with-instock';
import './view/cbax-analytics-index-product-by-orders';
import './view/cbax-analytics-index-sales-by-customer';
import './view/cbax-analytics-index-new-customers-by-time';
import './view/cbax-analytics-index-customer-age';
import './view/cbax-analytics-index-product-soon-outstock';
import './view/cbax-analytics-index-orders-by-transaction-status';
import './view/cbax-analytics-index-orders-by-delivery-status';
import './view/cbax-analytics-index-quick-overview';
import './view/cbax-analytics-index-unfinished-orders';
import './view/cbax-analytics-index-unfinished-orders-by-payment';
import './view/cbax-analytics-index-unfinished-orders-by-cart';
import './view/cbax-analytics-index-canceled-orders-by-month';
import './view/cbax-analytics-index-search-terms';
import './view/cbax-analytics-index-search-activity';
import './view/cbax-analytics-index-sales-by-device';
import './view/cbax-analytics-index-sales-by-os';
import './view/cbax-analytics-index-sales-by-browser';
import './view/cbax-analytics-index-products-inventory';
import './view/cbax-analytics-index-products-profit';
import './view/cbax-analytics-index-sales-by-billing-country';
import './view/cbax-analytics-index-sales-by-billing-country-invoice';
import './view/cbax-analytics-index-sales-by-currency';
import './view/cbax-analytics-index-lexicon-impressions';
import './view/cbax-analytics-index-customer-online';
import './view/cbax-analytics-index-conversion-all';
import './view/cbax-analytics-index-conversion-by-month';
import './view/cbax-analytics-index-sales-by-category';
import './view/cbax-analytics-index-sales-by-products-filter';
import './view/cbax-analytics-index-variants-compare';
import './view/cbax-analytics-index-product-impressions';
import './view/cbax-analytics-index-visitors';
import './view/cbax-analytics-index-visitor-impressions';
import './view/cbax-analytics-index-referer';
import './view/cbax-analytics-index-category-impressions';
import './view/cbax-analytics-index-manufacturer-impressions';
import './view/cbax-analytics-index-single-product';
import './view/cbax-analytics-index-cross-selling';
import './view/cbax-analytics-index-customer-by-salutation';
import './view/cbax-analytics-index-sales-by-salutation';
import './view/cbax-analytics-index-sales-by-taxrate';
import './view/cbax-analytics-index-category-compare';
import './view/cbax-analytics-index-sales-by-account-types';
import './view/cbax-analytics-index-pickware-returns';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Module.register('cbax-analytics', {
    type: 'plugin',
    name: 'cbax-analytics.general.name',
    title: 'cbax-analytics.general.title',
    description: 'cbax-analytics.general.description',
    color: '#ff68b4',
    icon: 'regular-flask',
    entity: 'cbax_analytics_config',
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

	routes: {
        index: {
            components: {
                default: 'cbax-analytics-index'
            },
            path: 'index',
			meta: {
                parentPath: 'cbax.analytics.index',
                 privilege: 'cbaxAnalytics.viewer'
            }
        },
        order: {
            components: {
                default: 'cbax-analytics-order'
            },
            path: 'order',
            meta: {
                parentPath: 'cbax.analytics.index',
                privilege: 'cbaxAnalytics.viewer'
            }
        },
        product: {
            components: {
                default: 'cbax-analytics-product'
            },
            path: 'product',
            meta: {
                parentPath: 'cbax.analytics.index',
                privilege: 'cbaxAnalytics.viewer'
            }
        }
    },


    navigation: [{
		id: 'cbax-analytics',
        label: 'cbax-analytics.general.navigationLabel',
        color: '#ff68b4',
		icon: 'default-object-lab-flask',
        path: 'cbax.analytics.index',
		position: 100,
        parent: 'sw-dashboard',
        privilege: 'cbaxAnalytics.viewer'
    }]
});
