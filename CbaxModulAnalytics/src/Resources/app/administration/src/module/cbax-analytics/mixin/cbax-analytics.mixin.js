const { Mixin, Context } = Shopware;
const { Criteria } = Shopware.Data;

Mixin.register('cbax-analytics', {

    inject: [
        'repositoryFactory'
    ],

    data() {
        return {
            orderStoreKey: 'grid.filter.cbax.analytics.order',
            productStoreKey: 'grid.filter.cbax.analytics.product',

            manufacturerFilterStatistics: [
                'product_inactive_with_instock',
                'products_inventory',
                'sales_by_products',
                'product_impressions',
                'manufacturer_impressions',
                'sales_by_products_filter',
                'product_by_orders',
                'product_low_instock',
                'product_high_instock',
                'products_profit',
                'product_soon_outstock',
                'unfinished_orders',
                'sales_by_category',
                'category_compare',
                'sales_by_products_pwreturn',
                'pickware_returns'
            ],
            productFilterStatistics: [
                'product_inactive_with_instock',
                'products_inventory',
                'sales_by_products',
                'product_impressions',
                'sales_by_products_filter',
                'product_by_orders',
                'product_low_instock',
                'product_high_instock',
                'products_profit',
                'product_soon_outstock',
                'unfinished_orders',
                'sales_by_category',
                'sales_by_products_pwreturn',
                'pickware_returns'
            ],
            affiliateFilterStatistics: [
                'sales_all',
                'sales_by_customergroups',
                'sales_by_products_filter',
                'product_by_orders',
                'sales_by_billing_country',
                'sales_by_billing_country_invoice',
                'sales_by_country',
                'sales_by_month',
                'sales_by_month_pwreturn',
                'sales_by_quarter',
                'sales_by_quarter_pwreturn',
                'sales_all_invoice',
                'sales_all_pwreturn',
                'sales_by_month_invoice',
                'sales_by_quarter_invoice',
                'sales_by_saleschannel',
                'sales_by_salutation',
                'sales_by_products',
                'sales_by_manufacturer',
                'sales_by_promotion',
                'sales_by_promotion_others',
                'conversion_all',
                'conversion_by_month',
                'sales_by_campaign',
                'sales_by_category',
                'category_compare',
                'sales_by_products_pwreturn',
                'pickware_returns',
                'product_impressions'
            ],
            notaffiliateFilterStatistics: [
                'product_impressions',
                'conversion_by_month',
                'conversion_all'
            ],
            campaignFilterStatistics: [
                'sales_all',
                'sales_by_customergroups',
                'sales_by_products_filter',
                'product_by_orders',
                'sales_by_billing_country',
                'sales_by_billing_country_invoice',
                'sales_by_country',
                'sales_by_month',
                'sales_by_month_pwreturn',
                'sales_by_quarter',
                'sales_by_quarter_pwreturn',
                'sales_all_invoice',
                'sales_all_pwreturn',
                'sales_by_month_invoice',
                'sales_by_quarter_invoice',
                'sales_by_saleschannel',
                'sales_by_salutation',
                'sales_by_products',
                'sales_by_manufacturer',
                'sales_by_promotion',
                'sales_by_promotion_others',
                'conversion_all',
                'conversion_by_month',
                'sales_by_affiliate',
                'sales_by_category',
                'category_compare',
                'sales_by_products_pwreturn',
                'pickware_returns'
            ],
            promotionFilterStatistics: [
                'sales_all',
                'conversion_all',
                'conversion_by_month',
                'sales_by_affiliate',
                'sales_by_billing_country',
                'sales_by_billing_country_invoice',
                'sales_by_country',
                'sales_by_campaign',
                'sales_by_month',
                'sales_by_month_pwreturn',
                'sales_by_quarter',
                'sales_by_quarter_pwreturn',
                'sales_all_invoice',
                'sales_all_pwreturn',
                'sales_by_month_invoice',
                'sales_by_quarter_invoice',
                'sales_by_customergroups',
                'sales_by_saleschannel',
                'sales_by_salutation'
            ],
            variantParentSwitchStatistics: [
                'products_profit',
                'product_high_instock',
                'product_low_instock',
                'products_inventory',
                'sales_by_products',
                'product_impressions',
                'product_by_orders',
                'sales_by_category',
                'sales_by_products_filter',
                'single_product',
                'sales_by_products_pwreturn',
                'pickware_returns'
            ]
        }
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        customerGroupRepository() {
            return this.repositoryFactory.create('customer_group');
        },

        stateMachineRepository() {
            return this.repositoryFactory.create('state_machine');
        },

        userTimeZone() {
            return Shopware?.State?.get('session')?.currentUser?.timeZone ?? 'UTC';
        }
    },

    methods: {
        getStatesFilterData() {
            let key, states = {
                order: {},
                order_delivery: {},
                order_transaction: {}
            };
            const criteria = new Criteria();
            criteria.addAssociation('states');
            return this.stateMachineRepository.search(criteria).then((result) => {
                if (Array.isArray(result) && result.length > 0) {
                    result.forEach((item) => {
                        if (['order_delivery.state', 'order.state', 'order_transaction.state'].includes(item.technicalName)) {
                            key = item.technicalName.replace('.state', '');
                            item.states.forEach((state) => {
                                states[key][state.id] = state.translated.name;
                            });
                        }
                    });
                }
                return states;
            });
        },

        getSalesChannelData() {
            let salesChannnels = {};
            const criteria = new Criteria();
            const storefrontSalesChannelTypeId = '8A243080F92E4C719546314B577CF82B';
            const apiSalesChannelTypeId = 'f183ee5650cf4bdb8a774337575067a6'; //headless
            const pickwarePOS = 'd18beabacf894e14b507767f4358eeb0'; //Pickware POS plugin Saleschannel typ
            criteria.addFilter(Criteria.equalsAny('typeId', [storefrontSalesChannelTypeId, apiSalesChannelTypeId, pickwarePOS]));
            return this.salesChannelRepository.search(criteria).then((result) => {
                if (Array.isArray(result) && result.length > 0) {
                    result.forEach((item) => {
                        salesChannnels[item.id] = item.translated.name;
                    });
                }
                return salesChannnels;
            });
        },

        getCustomerGroupData() {
            let customerGroups = {};
            const criteria = new Criteria();
            return this.customerGroupRepository.search(criteria).then((result) => {
                if (Array.isArray(result) && result.length > 0) {
                    result.forEach((item) => {
                        customerGroups[item.id] = item.translated.name;
                    });
                }
                return customerGroups;
            });
        },

        defaultGridSeriesData(gridData, page, limit) {
            if (!gridData) {
                return null;
            }
            return gridData.slice((page - 1) * limit, limit * page);
        },

        defaultChartSeriesData(seriesData, xColumn, yColumn, title, reverse = false, round = false) {
            if (!seriesData) {
                return null;
            }
            const resultData = seriesData.map((data) => {
                if (round) {
                    return { x: data[xColumn], y: Math.round(data[yColumn]) };
                } else {
                    return { x: data[xColumn], y: data[yColumn] };
                }
            });

            if (reverse) {
                resultData.reverse();
            }
            return [{ name: title, data: resultData }];
        },

        defaultChartSeriesDataWithDates(seriesData, xColumn, yColumn, title, reverse = false) {
            if (!seriesData) {
                return null;
            }
            const resultData = seriesData.map((data) => {
                return { x: this.parseDate(data[xColumn]), y: data[yColumn] };
            });
            if (reverse) {
                resultData.reverse();
            }
            return [{ name: title, data: resultData }];
        },

        pieChartOptions(title, currency = null) {
            let opts = {
                title: {
                    text: this.$tc(title)
                },
                xaxis: {
                },
                yaxis: {
                    labels:{
                        formatter: (value) => parseInt(value, 10)
                    }
                },
                legend: {
                    position: "bottom"
                },
                noData: {
                    text: this.$tc('cbax-analytics.index.noData'),
                    style: {
                        color: '#189eff',
                        fontSize: 20
                    }
                }
            };

            if (currency) {
                opts.yaxis = { labels: { formatter: (value) => Shopware.Filter.getByName('currency')(value, currency.isoCode, 0) } };
            }

            return opts;
        },

        orderLinkTarget(displayOptions) {
            if (displayOptions && displayOptions.config && displayOptions.config.orderLinkTarget) {
                return '_blank';
            } else {
                return null;
            }
        },

        productLinkTarget(displayOptions) {
            if (displayOptions && displayOptions.config && displayOptions.config.productLinkTarget) {
                return '_blank';
            } else {
                return null;
            }
        },

        orderLink(value, filterName, displayOptions, activeStatistic, id = null) {
            let newFilters = this.builtOrderFilters(value, filterName, displayOptions, activeStatistic, id);

            return {
                name: 'cbax.analytics.order',
                query: {
                    limit: 25,
                    page: 1,
                    label: this.$tc(activeStatistic.label) + ': ' + value,
                    sortBy: 'orderDateTime',
                    sortDirection: 'DESC',
                    naturalSorting: false,
                    [this.orderStoreKey]: encodeURI(JSON.stringify(newFilters))
                },
            };
        },

        productLink(value, filterName, displayOptions, activeStatistic, id = null) {
            let newFilters = this.builtOrderFilters(value, filterName, displayOptions, activeStatistic, id);

            return {
                name: 'cbax.analytics.product',
                query: {
                    id: id,
                    statistics: activeStatistic.name,
                    label: this.$tc(activeStatistic.label) + ': ' + value,
                    [this.orderStoreKey]: encodeURI(JSON.stringify(newFilters))
                },
            };
        },

        builtOrderFilters(value, filterName, displayOptions, activeStatistic, id = null) {
            const options = {...displayOptions};
            options.config = {...displayOptions.config};
            let criteria, field, fValue = [], fIds = [];
            let newFilters = {};

            switch (filterName) {
                case 'line-item':
                    field = 'lineItems.productId';
                    fValue.push({
                        id: id,
                        name: value
                    });
                    criteria = new Criteria();
                    criteria.addFilter(Criteria.equalsAny(field, [id]));
                    newFilters[filterName + '-filter'] = {
                        criteria: criteria.filters,
                        value: fValue
                    };
                    break;
                case 'sales-channel':
                    // Filter wird unten gesetzt
                    options.salesChannelIds = [id];
                    break;
                case 'customer-group':
                    // Filter wird unten gesetzt
                    options.customerGroupIds = [id];
                    break;
                case 'status':
                    // Filter wird unten gesetzt
                    options.config.blacklistedOrderStates = [id];
                    break;
                case 'delivery-status':
                    // Filter wird unten gesetzt
                    options.config.blacklistedDeliveryStates = [id];
                    break;
                case 'payment-status':
                    // Filter wird unten gesetzt
                    options.config.blacklistedTransactionStates = [id];
                    break;
                case 'payment-method':
                    field = 'transactions.paymentMethod.id';
                    fValue.push({
                        id: id,
                        name: value
                    });
                    criteria = new Criteria();
                    criteria.addFilter(Criteria.equalsAny(field, [id]));
                    newFilters[filterName + '-filter'] = {
                        criteria: criteria.filters,
                        value: fValue
                    };
                    break;
                case 'shipping-method':
                    field = 'deliveries.shippingMethod.id';
                    fValue.push({
                        id: id,
                        name: value
                    });
                    criteria = new Criteria();
                    criteria.addFilter(Criteria.equalsAny(field, [id]));
                    newFilters[filterName + '-filter'] = {
                        criteria: criteria.filters,
                        value: fValue
                    };
                    break;
                case 'billing-country':
                    field = 'billingAddress.country.id';
                    fValue.push({
                        id: id,
                        name: value
                    });
                    criteria = new Criteria();
                    criteria.addFilter(Criteria.equalsAny(field, [id]));
                    newFilters[filterName + '-filter'] = {
                        criteria: criteria.filters,
                        value: fValue
                    };
                    break;
                case 'shipping-country':
                    field = 'deliveries.shippingOrderAddress.country.id';
                    fValue.push({
                        id: id,
                        name: value
                    });
                    criteria = new Criteria();
                    criteria.addFilter(Criteria.equalsAny(field, [id]));
                    newFilters[filterName + '-filter'] = {
                        criteria: criteria.filters,
                        value: fValue
                    };
                    break;
                case 'manufacturer':
                    field = 'lineItems.product.manufacturer.id';
                    fValue.push({
                        id: id,
                        name: value
                    });
                    criteria = new Criteria();
                    criteria.addFilter(Criteria.equalsAny(field, [id]));
                    newFilters[filterName + '-filter'] = {
                        criteria: criteria.filters,
                        value: fValue
                    };
                    break;
                case 'order-date':
                    // Filter wird unten gesetzt
                    if (value.includes('-')) {
                        //Umsatz nach Tag
                        options.start = value + 'T00:00:00';
                        options.end = value + 'T23:59:59';
                    } else if (value.includes('/')) {
                        //Umsatz nach Monat
                        const valueArray = value.split('/');
                        const m = parseInt(valueArray[0], 10);
                        options.start = valueArray[1] + '-' + valueArray[0] + '-01T00:00:00';
                        const endDate = new Date(options.start);
                        endDate.setMonth(m);
                        endDate.setDate(0);
                        options.end = valueArray[1] + '-' + valueArray[0] + '-' + endDate.getDate() + 'T23:59:59';
                    } else if (value.includes(' Q')) {
                        //Umsatz nach Quartal
                        const valueArray = value.split(' Q');
                        if (parseInt(valueArray[1], 10) === 1) {
                            options.start = valueArray[0] + '-01-01T00:00:00';
                            options.end = valueArray[0] + '-03-31T23:59:59';
                        } else if (parseInt(valueArray[1], 10) === 2) {
                            options.start = valueArray[0] + '-04-01T00:00:00';
                            options.end = valueArray[0] + '-06-30T23:59:59';
                        } else if (parseInt(valueArray[1], 10) === 3) {
                            options.start = valueArray[0] + '-07-01T00:00:00';
                            options.end = valueArray[0] + '-09-30T23:59:59';
                        } else if (parseInt(valueArray[1], 10) === 4) {
                            options.start = valueArray[0] + '-10-01T00:00:00';
                            options.end = valueArray[0] + '-12-31T23:59:59';
                        }
                    }
                    break;
                case 'promotion-code':
                    field= 'lineItems.payload.code';
                    criteria = new Criteria();
                    criteria.addFilter(Criteria.equalsAny(field, [value]));
                    newFilters[filterName + '-filter'] = {
                        criteria: criteria.filters,
                        value: [value]
                    };
                    break;
                default:
                    //field to camel case
                    field = filterName.replace(/-./g, x=>x[1].toUpperCase());
                    if (
                        filterName == 'affiliate-code' && (value == 'Kein Partner Code' || value == 'No affiliate code')
                    ) {
                        criteria = new Criteria();
                        criteria.addFilter(Criteria.equals(field, null));
                        newFilters[filterName + '-filter'] = {
                            criteria: criteria.filters,
                            value: null
                        };

                    } else if (
                        filterName == 'campaign-code' && (value == 'Keine Kampagne' || value == 'No campaign')
                    ) {
                        criteria = new Criteria();
                        criteria.addFilter(Criteria.equals(field, null));
                        newFilters[filterName + '-filter'] = {
                            criteria: criteria.filters,
                            value: null
                        };

                    } else {
                        criteria = new Criteria();
                        criteria.addFilter(Criteria.equalsAny(field, [value]));
                        newFilters[filterName + '-filter'] = {
                            criteria: criteria.filters,
                            value: [value]
                        };
                    }
            }

            //Date Filter
            criteria = new Criteria();
            criteria.addFilter(Criteria.range(
                'orderDateTime',
                {
                    gte: options.start,
                    lte: options.end
                })
            );
            newFilters['order-date-filter'] = {
                criteria: criteria.filters,
                value: {
                    from: options.start,
                    timeframe: 'custom',
                    to: options.end
                }
            };

            //promotion code Filter
            if (filterName !== 'promotion-code' &&
                this.promotionFilterStatistics.includes(activeStatistic.name) &&
                Array.isArray(options.promotionCodes) &&
                options.promotionCodes.length > 0
            ) {
                criteria = new Criteria();
                criteria.addFilter(Criteria.equalsAny('lineItems.payload.code', options.promotionCodes));
                newFilters['promotion-code-filter'] = {
                    criteria: criteria.filters,
                    value: options.promotionCodes
                };
            }

            //Affiliate Filter
            if (filterName !== 'affiliate-code' &&
                this.affiliateFilterStatistics.includes(activeStatistic.name) &&
                Array.isArray(options.affiliateCodes) &&
                options.affiliateCodes.length > 0
            ) {
                criteria = new Criteria();
                criteria.addFilter(Criteria.equalsAny('affiliateCode', options.affiliateCodes));
                newFilters['affiliate-code-filter'] = {
                    criteria: criteria.filters,
                    value: options.affiliateCodes
                };
            }

            //Campaign Filter
            if (filterName !== 'campaign-code' &&
                this.campaignFilterStatistics.includes(activeStatistic.name) &&
                Array.isArray(options.campaignCodes) &&
                options.campaignCodes.length > 0
            ) {
                criteria = new Criteria();
                criteria.addFilter(Criteria.equalsAny('campaignCode', options.campaignCodes));
                newFilters['campaign-code-filter'] = {
                    criteria: criteria.filters,
                    value: options.campaignCodes
                };
            }

            //Saleschannel Filter
            fValue = [];
            if (Array.isArray(options.salesChannelIds) && options.salesChannelIds.length > 0) {
                options.salesChannelIds.forEach((id) => {
                    if (options.salesChannnelFilterData[id] !== undefined) {
                        fValue.push({
                            id: id,
                            name: options.salesChannnelFilterData[id]
                        });
                    }
                });

                criteria = new Criteria();
                criteria.addFilter(Criteria.equalsAny('salesChannelId', options.salesChannelIds));
                newFilters['sales-channel-filter'] = {
                    criteria: criteria.filters,
                    value: fValue
                };
            }

            //Customer Group Filter
            fValue = [];
            if (Array.isArray(options.customerGroupIds) && options.customerGroupIds.length > 0) {
                options.customerGroupIds.forEach((id) => {
                    if (options.customerGroupFilterdata[id] !== undefined) {
                        fValue.push({
                            id: id,
                            name: options.customerGroupFilterdata[id]
                        });
                    }
                });
                criteria = new Criteria();
                criteria.addFilter(Criteria.equalsAny('orderCustomer.customer.group.id', options.customerGroupIds));
                newFilters['customer-group-filter'] = {
                    criteria: criteria.filters,
                    value: fValue,
                };
            }

            //Order States Filter
            fValue = [];
            if (Array.isArray(options.config.blacklistedOrderStates) && options.config.blacklistedOrderStates.length > 0) {
                for (const [key, value] of Object.entries(options.statesFilterData.order)) {
                    if (filterName !== 'status') {
                        if (!options.config.blacklistedOrderStates.includes(key)) {
                            fValue.push({
                                id: key,
                                name: value
                            });
                            fIds.push(key);
                        }
                    } else {
                        if (options.config.blacklistedOrderStates.includes(key)) {
                            fValue.push({
                                id: key,
                                name: value
                            });
                            fIds.push(key);
                        }
                    }
                }
                criteria = new Criteria();
                criteria.addFilter(Criteria.equalsAny('stateMachineState.id', fIds));
                newFilters['status-filter'] = {
                    criteria: criteria.filters,
                    value: fValue,
                };
            }

            //Delivery States Filter
            fValue = [];
            fIds = [];
            if (Array.isArray(options.config.blacklistedDeliveryStates) && options.config.blacklistedDeliveryStates.length > 0) {
                for (const [key, value] of Object.entries(options.statesFilterData.order_delivery)) {
                    if (filterName !== 'delivery-status') {
                        if (!options.config.blacklistedDeliveryStates.includes(key)) {
                            fValue.push({
                                id: key,
                                name: value
                            });
                            fIds.push(key);
                        }
                    } else {
                        if (options.config.blacklistedDeliveryStates.includes(key)) {
                            fValue.push({
                                id: key,
                                name: value
                            });
                            fIds.push(key);
                        }
                    }
                }
                criteria = new Criteria();
                criteria.addFilter(Criteria.equalsAny('deliveries.stateMachineState.id', fIds));
                newFilters['delivery-status-filter'] = {
                    criteria: criteria.filters,
                    value: fValue,
                };
            }

            //Transaction States Filter
            fValue = [];
            fIds = [];
            if (Array.isArray(options.config.blacklistedTransactionStates) && options.config.blacklistedTransactionStates.length > 0) {
                for (const [key, value] of Object.entries(options.statesFilterData.order_transaction)) {
                    if (filterName !== 'payment-status') {
                        if (!options.config.blacklistedTransactionStates.includes(key)) {
                            fValue.push({
                                id: key,
                                name: value
                            });
                            fIds.push(key);
                        }
                    } else {
                        if (options.config.blacklistedTransactionStates.includes(key)) {
                            fValue.push({
                                id: key,
                                name: value
                            });
                            fIds.push(key);
                        }
                    }
                }
                criteria = new Criteria();
                criteria.addFilter(Criteria.equalsAny('transactions.stateMachineState.id', fIds));
                newFilters['payment-status-filter'] = {
                    criteria: criteria.filters,
                    value: fValue,
                };
            }

            return newFilters;
        },

        onChangeField(type) {
            this.isLoading = true;
            this.chartType = type;
            this.$nextTick(() => {
                this.isLoading = false;
            });
        },

        setOthersLabel(data) {
            for (let i = 0; i < data.length; i++) {
                if (data[i].name === 'cbax-analytics.data.others') {
                    data[i].name = this.$tc('cbax-analytics.data.others');
                }
            }
            return data;
        },

        getCsvDownloadUrl(fileName, fileSize) {
            return `${Shopware.Context.api.apiResourcePath}/cbax/analytics/download?fileName=${fileName}&fileSize=${fileSize}`;
        },

        csvDownload(fileName, fileSize) {
            window.location.href = this.getCsvDownloadUrl(fileName, fileSize);
        },

        parseDate(date) {
            const parsedDate = new Date(date.replace(/-/g, '/').replace('T', ' ').replace(/\..*|\+.*/, ''));
            return parsedDate.valueOf();
        },

        onPageChange(opts) {
            this.page = opts.page;
            this.limit = opts.limit;
        },

        getBasicParameters(displayOptions, format, activeStatisticName) {
            let parameters = {};
            parameters.showVariantParent = displayOptions.showVariantParent;
            parameters.start = displayOptions.start;
            parameters.end = displayOptions.end;
            parameters.salesChannelIds = displayOptions.salesChannelIds;
            parameters.customerGroupIds = displayOptions.customerGroupIds;
            parameters.config = displayOptions.config;
            parameters.userTimeZone = (Shopware.State.get('session').currentUser?.timeZone) ?? 'UTC';

            if (this.variantParentSwitchStatistics.includes(activeStatisticName)) {
                parameters.showVariantParent = displayOptions.showVariantParent;
            } else {
                parameters.showVariantParent = false;
            }
            if (this.productFilterStatistics.includes(activeStatisticName)) {
                parameters.productSearchIds = displayOptions.productSearchIds ?? [];
            } else {
                parameters.productSearchIds = [];
            }
            if (this.manufacturerFilterStatistics.includes(activeStatisticName)) {
                parameters.manufacturerSearchIds = displayOptions.manufacturerSearchIds ?? [];
            } else {
                parameters.manufacturerSearchIds = [];
            }
            if (this.affiliateFilterStatistics.includes(activeStatisticName)) {
                parameters.affiliateCodes = displayOptions.affiliateCodes ?? [];
            } else {
                parameters.affiliateCodes = [];
            }
            if (this.notaffiliateFilterStatistics.includes(activeStatisticName)) {
                parameters.notaffiliateCodes = displayOptions.notaffiliateCodes ?? [];
            } else {
                parameters.notaffiliateCodes = [];
            }
            if (this.campaignFilterStatistics.includes(activeStatisticName)) {
                parameters.campaignCodes = displayOptions.campaignCodes ?? [];
            } else {
                parameters.campaignCodes = [];
            }
            if (this.promotionFilterStatistics.includes(activeStatisticName)) {
                parameters.promotionCodes = displayOptions.promotionCodes ?? [];
            } else {
                parameters.promotionCodes = [];
            }
            parameters.adminLocaleLanguage = Shopware.State.getters.adminLocaleLanguage + '-' + Shopware.State.getters.adminLocaleRegion;
            if (format !== undefined) {
                parameters.format = format;
            } else {
                parameters.format = '';
            }

            return parameters;
        },

        getGridLabels(gridColumns) {
            let labels = '';
            gridColumns.forEach((column) => {
                labels += column.label + ';';
            });
            return labels.substring(0, labels.length - 1);
        },

        getMinDate(date) {
            const parsedDate = new Date(date.slice(0,10).replace(/-/g, '/').replace('T', ' ').replace(/\..*|\+.*/, ''));
            return parsedDate.valueOf();
        },

        getMaxDate(date) {
            const parsedDate = new Date(date.slice(0,10).replace(/-/g, '/').replace('T', ' ').replace(/\..*|\+.*/, ''));
            return parsedDate.valueOf();
        },

        updateDateRange(range, configs) {
            const complexRanges = ['currentWeek', 'lastWeek', 'currentMonth', 'lastMonth', 'currentQuarter', 'lastQuarter', 'currentYear', 'lastYear'];
            let start = new Date();
            let end = new Date();

            start.setHours(0, 0, 0, 0);
            end.setHours(0, 0, 0, 0);

            if (!complexRanges.includes(range)) {
                start.setDate(start.getDate() - parseInt(range, 10));

            } else {

                let weekday = start.getDay(),
                    month = start.getMonth(),
                    year = start.getFullYear(),
                    quarter,
                    diffStart, diffEnd;

                switch (range) {

                    case 'currentWeek':
                        diffStart = (weekday === 0) ? 6 : weekday - 1;
                        start.setDate(start.getDate() - diffStart);
                        break;

                    case 'lastWeek':
                        diffStart = (weekday === 0) ? 13 : weekday + 6;
                        diffEnd = diffStart - 6;
                        start.setDate(start.getDate() - diffStart);
                        end.setDate(end.getDate() - diffEnd);
                        break;

                    case 'currentMonth':
                        start.setDate(1);
                        break;

                    case 'lastMonth':
                        start.setMonth(month - 1, 1);
                        end.setMonth(month, 0);
                        break;

                    case 'currentQuarter':
                        quarter = Math.floor((month + 3) / 3);
                        start.setMonth((quarter * 3) - 3, 1);
                        break;

                    case 'lastQuarter':
                        quarter = Math.floor((month + 3) / 3);
                        start.setMonth((quarter * 3) - 6, 1);
                        end.setMonth((quarter * 3) - 3, 0);
                        break;

                    case 'currentYear':
                        start.setMonth(0, 1);
                        break;

                    case 'lastYear':
                        start.setFullYear(year - 1, 0, 1);
                        end.setFullYear(year - 1, 11, 31);
                        break;
                }
            }

            configs.start = start.getFullYear() + '-' + this.monthOf(start) + '-' + this.dayOf(start) + 'T00:00:00';
            configs.end = end.getFullYear() + '-' + this.monthOf(end) + '-' + this.dayOf(end) + 'T23:59:59';
            configs.dateRange = range;

            return configs;
        },

        monthOf(date) {
            const m = date.getMonth() + 1;
            if (m < 10) {
                return '0' + m.toString();
            } else {
                return m.toString();
            }
        },

        dayOf(date) {
            const d = date.getDate();
            if (d < 10) {
                return '0' + d.toString();
            } else {
                return d.toString();
            }
        },

        onColumnSort(column) {
            this.isLoading = true;
            let last = null;
            if (this.sortBy === column.dataIndex) {
                this.sortDirection = this.sortDirection === 'ASC' ? 'DESC' : 'ASC';
            } else {
                this.sortBy = column.dataIndex;
            }
            if (this.gridData.length === 0) {
                this.$nextTick(() => {
                    this.isLoading = false;
                });
                return;
            }
            if (this.gridData.length > 0 && this.gridData[this.gridData.length -1].name === this.$tc('cbax-analytics.data.others')) {
                last = this.gridData.pop();
            }
            if (this.sortDirection === 'ASC') {
                if (this.gridData[0] && this.gridData[0][this.sortBy] && typeof this.gridData[0][this.sortBy] === 'string') {
                    this.gridData.sort((a, b) => {
                        const nameA = a[this.sortBy]?.toUpperCase(); // ignore upper and lowercase
                        const nameB = b[this.sortBy]?.toUpperCase(); // ignore upper and lowercase
                        if (nameA > nameB) {
                            return 1;
                        }
                        if (nameA < nameB) {
                            return -1;
                        }
                        // names must be equal
                        return 0;
                    })
                } else {
                    this.gridData.sort((a, b) => a[this.sortBy] - b[this.sortBy]);
                }
                /*
                if (['sum', 'sales', 'count', 'stock', 'price', 'gprice', 'nprice', 'gross', 'net', 'taxRate', 'tax'].includes(this.sortBy)) {
                    this.gridData.sort((a, b) => a[this.sortBy] - b[this.sortBy]);
                } else {
                    this.gridData.sort((a, b) => {
                        const nameA = a[this.sortBy].toUpperCase(); // ignore upper and lowercase
                        const nameB = b[this.sortBy].toUpperCase(); // ignore upper and lowercase
                        if (nameA > nameB) {
                            return 1;
                        }
                        if (nameA < nameB) {
                            return -1;
                        }
                        // names must be equal
                        return 0;
                    })
                }

                 */

            } else {
                if (this.gridData[0] && this.gridData[0][this.sortBy] && typeof this.gridData[0][this.sortBy] === 'string') {
                    this.gridData.sort((a, b) => {
                        const nameA = a[this.sortBy]?.toUpperCase(); // ignore upper and lowercase
                        const nameB = b[this.sortBy]?.toUpperCase(); // ignore upper and lowercase
                        if (nameA > nameB) {
                            return -1;
                        }
                        if (nameA < nameB) {
                            return 1;
                        }
                        // names must be equal
                        return 0;
                    })
                } else {
                    this.gridData.sort((a, b) => b[this.sortBy] - a[this.sortBy]);
                }
                /*
                if (['sum', 'sales', 'count', 'stock', 'price', 'gprice', 'nprice', 'gross', 'net', 'taxRate', 'tax'].includes(this.sortBy)) {
                    this.gridData.sort((a, b) => b[this.sortBy] - a[this.sortBy]);
                } else {
                    this.gridData.sort((a, b) => {
                        const nameA = a[this.sortBy].toUpperCase(); // ignore upper and lowercase
                        const nameB = b[this.sortBy].toUpperCase(); // ignore upper and lowercase
                        if (nameA > nameB) {
                            return -1;
                        }
                        if (nameA < nameB) {
                            return 1;
                        }
                        // names must be equal
                        return 0;
                    })
                }

                 */

            }
            setTimeout(() => {
                if (last) {
                    this.gridData.push(last);
                }
                this.$nextTick(() => {
                    this.isLoading = false;
                });
            }, 400);
        }
    }
});
