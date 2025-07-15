import template from './nfx-analytics-animation-demo.html.twig';
import './nfx-analytics-animation-demo.scss';

const { Component } = Shopware;

Component.register('nfx-analytics-animation-demo', {
    template,

    data() {
        return {
            // Demo values for animated counters
            orderCount: 0,
            revenue: 0,
            customerCount: 0,
            conversionRate: 0,
            
            // Demo values for progress rings
            salesProgress: 0,
            targetProgress: 0,
            customerSatisfaction: 0,
            inventoryLevel: 0,
            
            // Configuration options
            showWaveEffect: true,
            morphingEnabled: true,
            celebrationsEnabled: true
        };
    },

    mounted() {
        // Simulate loading data after component mount
        setTimeout(() => {
            this.loadDemoData();
        }, 500);
    },

    methods: {
        loadDemoData() {
            // Animate counters
            this.orderCount = 1234;
            this.revenue = 45678.90;
            this.customerCount = 5678;
            this.conversionRate = 3.45;
            
            // Animate progress rings with staggered timing
            setTimeout(() => this.salesProgress = 75, 200);
            setTimeout(() => this.targetProgress = 89, 400);
            setTimeout(() => this.customerSatisfaction = 92, 600);
            setTimeout(() => this.inventoryLevel = 65, 800);
        },
        
        resetAnimations() {
            // Reset all values to 0
            this.orderCount = 0;
            this.revenue = 0;
            this.customerCount = 0;
            this.conversionRate = 0;
            this.salesProgress = 0;
            this.targetProgress = 0;
            this.customerSatisfaction = 0;
            this.inventoryLevel = 0;
            
            // Reload demo data after a short delay
            setTimeout(() => {
                this.loadDemoData();
            }, 300);
        },
        
        randomizeValues() {
            // Generate random values for demonstration
            this.orderCount = Math.floor(Math.random() * 5000);
            this.revenue = Math.random() * 100000;
            this.customerCount = Math.floor(Math.random() * 10000);
            this.conversionRate = Math.random() * 10;
            
            this.salesProgress = Math.floor(Math.random() * 100);
            this.targetProgress = Math.floor(Math.random() * 100);
            this.customerSatisfaction = Math.floor(Math.random() * 100);
            this.inventoryLevel = Math.floor(Math.random() * 100);
        }
    }
});