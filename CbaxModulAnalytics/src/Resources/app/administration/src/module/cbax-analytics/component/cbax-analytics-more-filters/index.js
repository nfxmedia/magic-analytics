import template from './cbax-analytics-more-filters.html.twig';
import './cbax-analytics-more-filters.scss';

const { Component, Context, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('cbax-analytics-more-filters', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('cbax-analytics')
    ],

    props: {
        filterOptions: {
		    type: Object,
            required: true
        },
        activeMoreFilterNumber: {
            type: Number,
            required: true
        },
        activeStatisticName: {
            type: String,
            required: true
        }
    },

    data() {
        return {
            isLoading: true,
            productFilter: {
                valueProperty:['name', 'productNumber'],
                labelProperty: ['name', 'productNumber']
            },
            affiliateFilter: {
                options: [],
                valueProperty: 'key',
                labelProperty: 'key'
            },
            notaffiliateFilter: {
                options: [],
                valueProperty: 'key',
                labelProperty: 'key'
            },
            campaignFilter: {
                options: [],
                valueProperty: 'key',
                labelProperty: 'key'
            },
            promotionFilter: {
                options: [],
                valueProperty: 'key',
                labelProperty: 'key'
            }
        }
    },

    computed: {
        productRepository() {
            return this.repositoryFactory.create('product');
        },

        orderRepository() {
            return this.repositoryFactory.create('order');
        },

        manufacturerRepository() {
            return this.repositoryFactory.create('product_manufacturer');
        },

        context() {
            return Context.api;
        },

        productFilterCriteria() {
            const criteria = new Criteria(1, 25);

            if (this.variantParentSwitchStatistics.includes(this.activeStatisticName) && this.filterOptions.showVariantParent) {
                criteria.addFilter(Criteria.equals('parentId', null));
            }

            return criteria;
        },

        filterSelectCriteria() {
            const criteria = new Criteria(1, 1);

            criteria.addAggregation(Criteria.terms('affiliateCodes', 'affiliateCode', null, null, null));
            criteria.addAggregation(Criteria.terms('campaignCodes', 'campaignCode', null, null, null));
            criteria.addAggregation(Criteria.terms('promotionCodes', 'lineItems.payload.code', null, null, null));

            return criteria;
        },

        showProductFilter() {
            return this.productFilterStatistics.includes(this.activeStatisticName);
        },

        showManufacturerFilter() {
            return this.manufacturerFilterStatistics.includes(this.activeStatisticName);
        },

        showAffiliateFilter() {
            return this.affiliateFilterStatistics.includes(this.activeStatisticName);
        },

        showNotaffiliateFilter() {
            return this.notaffiliateFilterStatistics.includes(this.activeStatisticName);
        },

        showCampaignFilter() {
            return this.campaignFilterStatistics.includes(this.activeStatisticName);
        },

        showPromotionFilter() {
            return this.promotionFilterStatistics.includes(this.activeStatisticName);
        },

        showVariantParentSwitch() {
            return this.variantParentSwitchStatistics.includes(this.activeStatisticName);
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isLoading = true;
            if (this.filterOptions.productSearchIds === undefined) {
                this.filterOptions.productSearchIds = [];
            }
            if (this.filterOptions.affiliateCodes === undefined) {
                this.filterOptions.affiliateCodes = [];
            }
            if (this.filterOptions.notaffiliateCodes === undefined) {
                this.filterOptions.notaffiliateCodes = [];
            }
            if (this.filterOptions.campaignCodes === undefined) {
                this.filterOptions.campaignCodes = [];
            }
            if (this.filterOptions.promotionCodes === undefined) {
                this.filterOptions.promotionCodes = [];
            }
            if (this.filterOptions.manufacturerSearchIds === undefined) {
                this.filterOptions.manufacturerSearchIds = [];
            }

            this.loadFilterValues();
        },

        loadFilterValues() {
            return this.orderRepository.search(this.filterSelectCriteria).then(({ aggregations }) => {
                const { affiliateCodes, campaignCodes, promotionCodes } = aggregations;

                this.affiliateFilter.options = affiliateCodes?.buckets.filter(({ key }) => key.length > 0) ?? [];
                this.notaffiliateFilter.options = affiliateCodes?.buckets.filter(({ key }) => key.length > 0) ?? [];
                this.campaignFilter.options = campaignCodes?.buckets.filter(({ key }) => key.length > 0) ?? [];
                this.promotionFilter.options = promotionCodes?.buckets.filter(({ key }) => key.length > 0) ?? [];

                this.$nextTick(() => {
                    this.isLoading = false;
                });
                return aggregations;
            }).catch(() => {
                this.isLoading = false;
            });
        },

        changeMultiSelectValue(event) {
            this.isLoading = true;
            this.$nextTick(() => {
                this.isLoading = false;
            });
        },

        resetAll() {
            this.filterOptions.showVariantParent = 0;
            this.filterOptions.productSearchIds = [];
            this.filterOptions.affiliateCodes = [];
            this.filterOptions.notaffiliateCodes = [];
            this.filterOptions.campaignCodes = [];
            this.filterOptions.manufacturerSearchIds = [];
            this.filterOptions.promotionCodes = [];
        },

        onChangeShowParents() {
            this.isLoading = true;
            this.$emit('filter-changeShowParents');
            this.filterOptions.productSearchIds = [];
            this.$nextTick(() => {
                this.isLoading = false;
            });
        }
    }
});
