import template from './nfx-analytics-index-cross-selling.html.twig';
import './nfx-analytics-index-cross-selling.scss';
import './component/nfx-grid-viewed';
import './component/nfx-grid-bought';

const { Component, Mixin } = Shopware;

Component.register('nfx-analytics-index-cross-selling', {
    template,

    mixins: [
        Mixin.getByName('nfx-analytics')
    ],
    props: {
        activeStatistic: {
            type: Object,
            required: false,
            default: null
        },
        displayOptions: {
            type: Object,
            required: true
        },
        format: {
            type: String,
            required: false,
            default: ''
        }
    },

    data() {
        let errors = {
            productId: null
        };
        return {
            isLoading: false,
            alsoViewed: null,
            alsoBought: null,
            productId: '',
            productName: '',
            errors: errors,
            noPlugin: false
        };
    },

    computed: {
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },

        labelProperty() {
            return ['name', 'productNumber'];
        }
    },

    watch: {
        displayOptions() {
            this.createdComponent();
        },

        format() {
            if (this.format == 'csv') {
                this.createdComponent();
            }
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isLoading = true;

            if (!this.productId || this.productId === '') {
                this.isLoading = false;
                return;
            }

            const initContainer = Shopware.Application.getContainer('init');
            const httpClient = initContainer.httpClient;
            const loginService = Shopware.Service('loginService');

            let parameters = this.getBasicParameters(this.displayOptions, this.format, this.activeStatistic.name);
            parameters.productId = this.productId;

            if (this.activeStatistic.name === 'cross_selling' && this.activeStatistic.pathInfo) {
                httpClient.post(this.activeStatistic.pathInfo,
                    { parameters },
                    { headers: { Authorization: `Bearer ${loginService.getToken()}`,} }
                ).then((response) => {
                    if (parameters.format === 'csv') {
                        this.$emit('nfx-statistics-csv_done');
                    }
                    if (response.data !== undefined && response.data['success'] === true) {
                        this.alsoViewed = response.data['alsoViewed'];
                        this.alsoBought = response.data['alsoBought'];
                        this.productName = response.data['productName'];
                    } else if (response.data !== undefined && response.data['success'] === false) {
                        this.alsoViewed = [];
                        this.alsoBought =  [];
                        this.productName = '';
                        this.noPlugin = true;
                    } else {
                        this.alsoViewed = [];
                        this.alsoBought =  [];
                        this.productName = '';
                    }

                    this.$nextTick(() => {
                        this.isLoading = false;
                    });

                }).catch((err) => {
                    this.isLoading = false;
                });
            }

        },

        onChangeProductSelectField(event) {
            this.createdComponent();
        }
    }
});
