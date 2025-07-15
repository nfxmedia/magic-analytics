import template from './cbax-analytics-display-options.html.twig';
import './cbax-analytics-display-options.scss';

const { Component, Context, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('cbax-analytics-display-options', {
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
        }
    },

    data() {
        return {
            datePickerDisabled: true,
            isLoading: false,
            statisticDateRanges: {
                value: '30',
                options: {
                    'userdefined': '0',
                    '365Days': '365',
                    '180Days': '180',
                    '90Days': '90',
                    '30Days': '30',
                    '14Days': '14',
                    '7Days': '7',
                    'yesterday': '1',
                    'currentWeek': 'currentWeek',
                    'lastWeek': 'lastWeek',
                    'currentMonth': 'currentMonth',
                    'lastMonth': 'lastMonth',
                    'currentQuarter': 'currentQuarter',
                    'lastQuarter': 'lastQuarter',
                    'currentYear': 'currentYear',
                    'lastYear': 'lastYear'
                }
            }
        }
    },

    computed: {
        datePickerConfig() {
            if (Shopware.State.getters.adminLocaleLanguage === 'de') {
                return {
                    altFormat: 'd.m.Y'
                }
            } else {
                return {
                    altFormat: 'd/m/Y'
                }
            }
        },

        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        customerGroupRepository() {
            return this.repositoryFactory.create('customer_group');
        },

        salesChannelCriteria() {
            const criteria = new Criteria();
            const storefrontSalesChannelTypeId = '8A243080F92E4C719546314B577CF82B';
            const apiSalesChannelTypeId = 'f183ee5650cf4bdb8a774337575067a6'; //headless
            const pickwarePOS = 'd18beabacf894e14b507767f4358eeb0'; //Pickware POS plugin Saleschannel typ
            criteria.addFilter(Criteria.equalsAny('typeId', [storefrontSalesChannelTypeId, apiSalesChannelTypeId, pickwarePOS]));

            return criteria;
        },

        context() {
            return Context.api;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isLoading = true;
            if (Number.isInteger(this.filterOptions.dateRange)) this.filterOptions.dateRange = this.filterOptions.dateRange.toString();
            this.datePickerDisabled = this.filterOptions.dateRange !== '0';

            this.$nextTick(() => {
                this.isLoading = false;
            });
        },

        onDateRangeChange(event) {
            this.isLoading = true;
            this.datePickerDisabled = this.filterOptions.dateRange !== '0';

            this.$nextTick(() => {
                this.isLoading = false;
            });
        }
    }
});
