import template from './cbax-analytics-product.html.twig';
import './cbax-analytics-product.scss';

const { Component, Context, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('cbax-analytics-product', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('cbax-analytics')
    ],

    data() {
        return {
            gridData: [],
            orderIds: [],
            manufacturers: [],
            manufacturerIds: [],
            systemCurrency: null,
            isLoading: true,
            activeStatisticLabel: '',
            total: 0,
            page: 1,
            limit: 25,
            sortDirection: 'DESC',
            sortBy: 'sum',
            storeKey: 'grid.filter.cbax.analytics.order',
            filterSidebarIsOpen: false,
            filterOptions: {
                products: [],
                manufacturers: [],
                active: 'all',
                showParents: false
            }
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        filteredGridData() {
            let data = [...this.gridData];
            if (this.filterOptions.products && this.filterOptions.products.length > 0) {
                data = data.filter(prod => this.filterOptions.products.includes(prod.id));
            }
            if (this.filterOptions.manufacturers && this.filterOptions.manufacturers.length > 0) {
                data = data.filter(prod => this.filterOptions.manufacturers.includes(prod.manufacturerId));
            }
            if (this.filterOptions.active && this.filterOptions.active === 'active') {
                data = data.filter(prod => parseInt(prod.active, 10) === 1);
            } else if (this.filterOptions.active && this.filterOptions.active === 'not') {
                data = data.filter(prod => parseInt(prod.active, 10) === 0);
            }
            this.total = data.length;
            return this.defaultGridSeriesData(data, this.page, this.limit);
        },

        activeFilterNumber() {
            return Number(this.filterOptions.products.length > 0) +
                Number(this.filterOptions.manufacturers.length) +
                Number(['active','not'].includes(this.filterOptions.active));
        },

        orderRepository() {
            return this.repositoryFactory.create('order');
        },

        productColumns() {
            return this.getProductColumns();
        },

        orderCriteria() {
            const criteria = new Criteria();

            criteria.addAssociation('addresses');
            criteria.addAssociation('billingAddress');
            criteria.addAssociation('salesChannel');
            criteria.addAssociation('orderCustomer');
            criteria.addAssociation('currency');
            criteria.addAssociation('transactions');
            criteria.addAssociation('deliveries');
            criteria.getAssociation('transactions').addSorting(Criteria.sort('createdAt'));

            return criteria;
        },

        currencyRepository() {
            return this.repositoryFactory.create('currency');
        }
    },

    created() {
        this.loadSystemCurrency();
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            if (this.$route && this.$route.query && this.$route.query.label) {
                this.activeStatisticLabel = this.$route.query.label;
            }
            this.getGridData();
        },

        async getGridData() {
            this.isLoading = true;

            let criteria = await Shopware.Service('filterService')
                .mergeWithStoredFilters(this.storeKey, this.orderCriteria);

            try {
                Context.api.inheritance = true;
                const response = await this.orderRepository.searchIds(criteria, Context.api);

                if (!response?.data) return;

                this.orderIds = response.data;

                const initContainer = Shopware.Application.getContainer('init');
                const httpClient = initContainer.httpClient;
                const loginService = Shopware.Service('loginService');

                let parameters = {
                    showParents: this.filterOptions.showParents,
                    orderIds: this.orderIds,
                    statistics: this.$route.query.statistics,
                    id: this.$route.query.id,
                    userTimeZone: (Shopware.State.get('session').currentUser?.timeZone) ?? 'UTC',
                    adminLocaleLanguage: Shopware.State.getters.adminLocaleLanguage + '-' + Shopware.State.getters.adminLocaleRegion
                };

                httpClient.post('/cbax/analytics/getProductsGridData',
                    { parameters },
                    { headers: { Authorization: `Bearer ${loginService.getToken()}`,} }
                ).then((response) => {
                    if (response.data !== undefined && response.data['success'] === true && response.data['gridData']) {
                        this.total = response.data.gridData.length;
                        this.gridData =  response.data['gridData'];
                        this.gridData.forEach((prod) => {
                            if (!this.manufacturerIds.includes(prod.manufacturerId)) {
                                this.manufacturerIds.push(prod.manufacturerId);
                                this.manufacturers.push({ id: prod.manufacturerId, name: prod.manufacturerName });
                            }
                        });
                    }
                    this.$nextTick(() => {
                        this.isLoading = false;
                    });

                }).catch((err) => {
                    this.isLoading = false;
                });

            } catch {
                this.isLoading = false;
            }
        },

        updateGridData() {
            if (this.orderIds.length === 0) return;
            this.isLoading = true;

            const initContainer = Shopware.Application.getContainer('init');
            const httpClient = initContainer.httpClient;
            const loginService = Shopware.Service('loginService');

            let parameters = {
                showParents: this.filterOptions.showParents,
                orderIds: this.orderIds,
                statistics: this.$route.query.statistics,
                id: this.$route.query.id,
                userTimeZone: (Shopware.State.get('session').currentUser?.timeZone) ?? 'UTC',
                adminLocaleLanguage: Shopware.State.getters.adminLocaleLanguage + '-' + Shopware.State.getters.adminLocaleRegion
            };

            httpClient.post('/cbax/analytics/getProductsGridData',
                { parameters },
                { headers: { Authorization: `Bearer ${loginService.getToken()}`,} }
            ).then((response) => {
                if (response.data !== undefined && response.data['success'] === true && response.data['gridData']) {
                    this.manufacturerIds = [];
                    this.manufacturers = [];
                    this.total = response.data.gridData.length;
                    this.gridData =  response.data['gridData'];
                    this.gridData.forEach((prod) => {
                        if (!this.manufacturerIds.includes(prod.manufacturerId)) {
                            this.manufacturerIds.push(prod.manufacturerId);
                            this.manufacturers.push({ id: prod.manufacturerId, name: prod.manufacturerName });
                        }
                    });
                }
                this.$nextTick(() => {
                    this.isLoading = false;
                });

            }).catch((err) => {
                this.isLoading = false;
            });
        },

        loadSystemCurrency() {
            return this.currencyRepository
                .get(Shopware.Context.app.systemCurrencyId, Context.api)
                .then((systemCurrency) => {
                    this.systemCurrency = systemCurrency;
                });
        },

        getProductColumns() {
            return [{
                property: 'name',
                dataIndex: 'name',
                label: 'sw-product.list.columnName',
                allowResize: true,
                primary: true,
                inlineEdit: false,
            }, {
                property: 'number',
                dataIndex: 'number',
                naturalSorting: true,
                label: 'sw-product.list.columnProductNumber',
                align: 'right',
                allowResize: true,
                inlineEdit: false,
            }, {
                property: 'sum',
                dataIndex: 'sum',
                label: 'sw-product.list.columnSales',
                allowResize: true,
                align: 'right',
                inlineEdit: false,
            }, {
                property: 'gross',
                dataIndex: 'gross',
                label: this.$tc('cbax-analytics.product.sales') + this.$tc('cbax-analytics.view.gross'),
                allowResize: true,
                align: 'right',
                inlineEdit: false,
            }, {
                property: 'net',
                dataIndex: 'net',
                label: this.$tc('cbax-analytics.product.sales') + this.$tc('cbax-analytics.view.net'),
                allowResize: true,
                align: 'right',
                inlineEdit: false,
            }, {
                property: 'gprice',
                dataIndex: 'gprice',
                label: this.$tc('cbax-analytics.product.avPrice') + this.$tc('cbax-analytics.view.gross'),
                allowResize: true,
                align: 'right',
                inlineEdit: false,
            }, {
                property: 'nprice',
                dataIndex: 'nprice',
                label: this.$tc('cbax-analytics.product.avPrice') + this.$tc('cbax-analytics.view.net'),
                allowResize: true,
                align: 'right',
                inlineEdit: false,
            }, {
                property: 'stock',
                dataIndex: 'stock',
                label: 'sw-product.list.columnInStock',
                allowResize: true,
                align: 'right',
                inlineEdit: false,
            }, {
                property: 'active',
                dataIndex: 'active',
                label: 'sw-product.list.columnActive',
                allowResize: true,
                align: 'center',
                inlineEdit: false,
            }, {
                property: 'manufacturerName',
                dataIndex: 'manufacturerName',
                label: 'sw-product.list.columnManufacturer',
                allowResize: true,
                inlineEdit: false,
            }];
        },

        exportCSV() {
            let products = [...this.gridData];
            if (this.filterOptions.products && this.filterOptions.products.length > 0) {
                products = products.filter(prod => this.filterOptions.products.includes(prod.id));
            }
            if (this.filterOptions.manufacturers && this.filterOptions.manufacturers.length > 0) {
                products = products.filter(prod => this.filterOptions.manufacturers.includes(prod.manufacturerId));
            }
            if (this.filterOptions.active && this.filterOptions.active === 'active') {
                products = products.filter(prod => parseInt(prod.active, 10) === 1);
            } else if (this.filterOptions.active && this.filterOptions.active === 'not') {
                products = products.filter(prod => parseInt(prod.active, 10) === 0);
            }
            if (Array.isArray(products) && products.length > 0) {

                let data = [];
                products.forEach((product) => {
                    let item = {};
                    item[this.$tc('sw-product.list.columnName')] = product.name;
                    item[this.$tc('sw-product.list.columnProductNumber')] = product.number;
                    item[this.$tc('sw-product.list.columnSales')] = product.sum;
                    item[this.$tc('cbax-analytics.product.sales') + this.$tc('cbax-analytics.view.gross')] = Shopware.Filter.getByName('currency')(product.gross, this.systemCurrency.isoCode, 2);
                    item[this.$tc('cbax-analytics.product.sales') + this.$tc('cbax-analytics.view.net')] = Shopware.Filter.getByName('currency')(product.net, this.systemCurrency.isoCode, 2);;
                    item[this.$tc('cbax-analytics.product.avPrice') + this.$tc('cbax-analytics.view.gross')] = Shopware.Filter.getByName('currency')(product.gprice, this.systemCurrency.isoCode, 2);
                    item[this.$tc('cbax-analytics.product.avPrice') + this.$tc('cbax-analytics.view.net')] = Shopware.Filter.getByName('currency')(product.nprice, this.systemCurrency.isoCode, 2);
                    item[this.$tc('sw-product.list.columnInStock')] = product.stock;
                    item[this.$tc('sw-product.list.columnActive')] = product.active;
                    item[this.$tc('sw-product.list.columnManufacturer')] = product.manufacturerName;

                    data.push(item);
                });

                const initContainer = Shopware.Application.getContainer('init');
                const httpClient = initContainer.httpClient;
                const loginService = Shopware.Service('loginService');

                if (data.length > 0) {
                    httpClient.post('cbax/analytics/csvExport',
                        {data},
                        {headers: {Authorization: `Bearer ${loginService.getToken()}`,}}
                    ).then((response) => {
                        if (response.data !== undefined && response.data['success'] === true && response.data['fileSize']) {
                            this.csvDownload('productdata.csv', response.data['fileSize']);
                        }

                    }).catch((err) => {
                        console.log(err);
                    });
                }
            }
        },

        changeFilterValue() {
            this.isLoading = true;

            this.$nextTick(() => {
                this.isLoading = false;
            });
        },

        closeContent() {
            if (this.filterSidebarIsOpen) {
                this.$refs.filterSideBar.closeContent();
                this.filterSidebarIsOpen = false;
                return;
            }

            this.$refs.filterSideBar.openContent();
            this.filterSidebarIsOpen = true;
        },

        onChangeShowParents() {
            this.updateGridData();
        }
    }
});
