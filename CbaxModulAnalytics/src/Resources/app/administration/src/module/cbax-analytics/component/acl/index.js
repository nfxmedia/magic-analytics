Shopware.Service('privileges')
    .addPrivilegeMappingEntry({
        category: 'permissions',
        parent: 'content',
        key: 'cbaxAnalytics',
        roles: {
            viewer: {
                privileges: [
                    Shopware.Service('privileges').getPrivileges('product.viewer'),
                    Shopware.Service('privileges').getPrivileges('order.viewer'),
                    Shopware.Service('privileges').getPrivileges('customer.viewer'),
                    Shopware.Service('privileges').getPrivileges('promotion.viewer'),
                    'system_config:read',
                    'cart:read',
                    'cbax_analytics_category_impressions:read',
                    'cbax_analytics_config:read',
                    'cbax_analytics_groups_config:read',
                    'cbax_analytics_manufacturer_impressions:read',
                    'cbax_analytics_pool:read',
                    'cbax_analytics_product_impressions:read',
                    'cbax_analytics_referer:read',
                    'cbax_analytics_search:read',
                    'cbax_analytics_visitors:read',
                    'cbax_lexicon_entry:read',
                    'cbax_lexicon_entry_translation:read',
                    'cbax_lexicon_sales_channel:read',
                    'cbax_cross_selling_also_bought:read',
                    'cbax_cross_selling_also_viewed:read',
                    'cbax_cross_selling_group:read'
                ],
                dependencies: [],
            },
            editor: {
                privileges: [
                    'cbax_analytics_category_impressions:update',
                    'cbax_analytics_config:update',
                    'cbax_analytics_groups_config:update',
                    'cbax_analytics_manufacturer_impressions:update',
                    'cbax_analytics_pool:update',
                    'cbax_analytics_product_impressions:update',
                    'cbax_analytics_referer:update',
                    'cbax_analytics_search:update',
                    'cbax_analytics_visitors:update'
                ],
                dependencies: ['cbaxAnalytics.viewer'],
            },
        },
    })
    .addPrivilegeMappingEntry({
        category: 'permissions',
        parent: 'content',
        key: 'cbaxAnalyticsDashboard',
        roles: {
            viewer: {
                privileges: [
                    Shopware.Service('privileges').getPrivileges('product.viewer'),
                    Shopware.Service('privileges').getPrivileges('order.viewer'),
                    Shopware.Service('privileges').getPrivileges('customer.viewer'),
                    Shopware.Service('privileges').getPrivileges('promotion.viewer'),
                    'system_config:read',
                    'cart:read',
                    'cbax_analytics_category_impressions:read',
                    'cbax_analytics_config:read',
                    'cbax_analytics_groups_config:read',
                    'cbax_analytics_manufacturer_impressions:read',
                    'cbax_analytics_pool:read',
                    'cbax_analytics_product_impressions:read',
                    'cbax_analytics_referer:read',
                    'cbax_analytics_search:read',
                    'cbax_analytics_visitors:read',
                    'cbax_lexicon_entry:read',
                    'cbax_lexicon_entry_translation:read',
                    'cbax_lexicon_sales_channel:read',
                    'cbax_cross_selling_also_bought:read',
                    'cbax_cross_selling_also_viewed:read',
                    'cbax_cross_selling_group:read'
                ],
                dependencies: [],
            },
            editor: {
                privileges: [
                    'cbax_analytics_category_impressions:update',
                    'cbax_analytics_config:update',
                    'cbax_analytics_groups_config:update',
                    'cbax_analytics_manufacturer_impressions:update',
                    'cbax_analytics_pool:update',
                    'cbax_analytics_product_impressions:update',
                    'cbax_analytics_referer:update',
                    'cbax_analytics_search:update',
                    'cbax_analytics_visitors:update'
                ],
                dependencies: ['cbaxAnalyticsDashboard.viewer'],
            },
        },
    });
