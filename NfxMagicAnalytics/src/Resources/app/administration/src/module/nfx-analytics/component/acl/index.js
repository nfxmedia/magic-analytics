Shopware.Service('privileges')
    .addPrivilegeMappingEntry({
        category: 'permissions',
        parent: 'content',
        key: 'nfxAnalytics',
        roles: {
            viewer: {
                privileges: [
                    Shopware.Service('privileges').getPrivileges('product.viewer'),
                    Shopware.Service('privileges').getPrivileges('order.viewer'),
                    Shopware.Service('privileges').getPrivileges('customer.viewer'),
                    Shopware.Service('privileges').getPrivileges('promotion.viewer'),
                    'system_config:read',
                    'cart:read',
                    'nfx_analytics_category_impressions:read',
                    'nfx_analytics_config:read',
                    'nfx_analytics_groups_config:read',
                    'nfx_analytics_manufacturer_impressions:read',
                    'nfx_analytics_pool:read',
                    'nfx_analytics_product_impressions:read',
                    'nfx_analytics_referer:read',
                    'nfx_analytics_search:read',
                    'nfx_analytics_visitors:read',
                    'nfx_lexicon_entry:read',
                    'nfx_lexicon_entry_translation:read',
                    'nfx_lexicon_sales_channel:read',
                    'nfx_cross_selling_also_bought:read',
                    'nfx_cross_selling_also_viewed:read',
                    'nfx_cross_selling_group:read'
                ],
                dependencies: [],
            },
            editor: {
                privileges: [
                    'nfx_analytics_category_impressions:update',
                    'nfx_analytics_config:update',
                    'nfx_analytics_groups_config:update',
                    'nfx_analytics_manufacturer_impressions:update',
                    'nfx_analytics_pool:update',
                    'nfx_analytics_product_impressions:update',
                    'nfx_analytics_referer:update',
                    'nfx_analytics_search:update',
                    'nfx_analytics_visitors:update'
                ],
                dependencies: ['nfxAnalytics.viewer'],
            },
        },
    })
    .addPrivilegeMappingEntry({
        category: 'permissions',
        parent: 'content',
        key: 'nfxAnalyticsDashboard',
        roles: {
            viewer: {
                privileges: [
                    Shopware.Service('privileges').getPrivileges('product.viewer'),
                    Shopware.Service('privileges').getPrivileges('order.viewer'),
                    Shopware.Service('privileges').getPrivileges('customer.viewer'),
                    Shopware.Service('privileges').getPrivileges('promotion.viewer'),
                    'system_config:read',
                    'cart:read',
                    'nfx_analytics_category_impressions:read',
                    'nfx_analytics_config:read',
                    'nfx_analytics_groups_config:read',
                    'nfx_analytics_manufacturer_impressions:read',
                    'nfx_analytics_pool:read',
                    'nfx_analytics_product_impressions:read',
                    'nfx_analytics_referer:read',
                    'nfx_analytics_search:read',
                    'nfx_analytics_visitors:read',
                    'nfx_lexicon_entry:read',
                    'nfx_lexicon_entry_translation:read',
                    'nfx_lexicon_sales_channel:read',
                    'nfx_cross_selling_also_bought:read',
                    'nfx_cross_selling_also_viewed:read',
                    'nfx_cross_selling_group:read'
                ],
                dependencies: [],
            },
            editor: {
                privileges: [
                    'nfx_analytics_category_impressions:update',
                    'nfx_analytics_config:update',
                    'nfx_analytics_groups_config:update',
                    'nfx_analytics_manufacturer_impressions:update',
                    'nfx_analytics_pool:update',
                    'nfx_analytics_product_impressions:update',
                    'nfx_analytics_referer:update',
                    'nfx_analytics_search:update',
                    'nfx_analytics_visitors:update'
                ],
                dependencies: ['nfxAnalyticsDashboard.viewer'],
            },
        },
    });
