// Example usage of the nfx-apex-chart component
// This file demonstrates how to implement different chart types with real-time updates

const { Component } = Shopware;

// Example: Area Chart with Real-time Updates
Component.register('nfx-analytics-revenue-chart', {
    template: `
        <nfx-apex-chart
            chart-type="area"
            :series="areaSeries"
            :chart-options="areaOptions"
            :real-time-enabled="true"
            :update-interval="3000"
            height="400"
        >
            <template #header>
                <h3>Revenue Analytics</h3>
            </template>
            <template #footer>
                <div class="chart-info">
                    <span>Last updated: {{ lastUpdate }}</span>
                </div>
            </template>
        </nfx-apex-chart>
    `,
    
    data() {
        return {
            areaSeries: [{
                name: 'Revenue',
                data: this.generateInitialData(30)
            }],
            areaOptions: {
                title: {
                    text: 'Monthly Revenue Trend',
                    align: 'left'
                },
                colors: ['#189EFF'],
                xaxis: {
                    type: 'datetime',
                    categories: this.generateDateCategories(30)
                },
                yaxis: {
                    title: {
                        text: 'Revenue ($)'
                    },
                    labels: {
                        formatter: (value) => '$' + value.toLocaleString()
                    }
                }
            },
            lastUpdate: new Date().toLocaleString()
        };
    },
    
    methods: {
        generateInitialData(count) {
            const data = [];
            const baseValue = 50000;
            for (let i = 0; i < count; i++) {
                data.push({
                    x: new Date(Date.now() - (count - i) * 24 * 60 * 60 * 1000).getTime(),
                    y: baseValue + Math.random() * 20000 - 10000
                });
            }
            return data;
        },
        
        generateDateCategories(count) {
            const categories = [];
            for (let i = 0; i < count; i++) {
                categories.push(new Date(Date.now() - (count - i) * 24 * 60 * 60 * 1000).toISOString());
            }
            return categories;
        }
    }
});

// Example: Candlestick Chart for Product Price Analysis
Component.register('nfx-analytics-price-chart', {
    template: `
        <nfx-apex-chart
            chart-type="candlestick"
            :series="candlestickSeries"
            :chart-options="candlestickOptions"
            :real-time-enabled="realTimeEnabled"
            height="450"
        />
    `,
    
    data() {
        return {
            realTimeEnabled: false,
            candlestickSeries: [{
                name: 'Price',
                data: this.generateCandlestickData(50)
            }],
            candlestickOptions: {
                title: {
                    text: 'Product Price Fluctuation',
                    align: 'left'
                },
                xaxis: {
                    type: 'datetime'
                },
                yaxis: {
                    tooltip: {
                        enabled: true
                    },
                    labels: {
                        formatter: (value) => '$' + value.toFixed(2)
                    }
                }
            }
        };
    },
    
    methods: {
        generateCandlestickData(count) {
            const data = [];
            let lastClose = 100;
            
            for (let i = 0; i < count; i++) {
                const open = lastClose;
                const close = open + (Math.random() - 0.5) * 10;
                const high = Math.max(open, close) + Math.random() * 5;
                const low = Math.min(open, close) - Math.random() * 5;
                
                data.push({
                    x: new Date(Date.now() - (count - i) * 60 * 60 * 1000).getTime(),
                    y: [open, high, low, close]
                });
                
                lastClose = close;
            }
            
            return data;
        }
    }
});

// Example: Heatmap for Category Performance
Component.register('nfx-analytics-heatmap-chart', {
    template: `
        <nfx-apex-chart
            chart-type="heatmap"
            :series="heatmapSeries"
            :chart-options="heatmapOptions"
            height="350"
        />
    `,
    
    data() {
        return {
            heatmapSeries: this.generateHeatmapData(),
            heatmapOptions: {
                title: {
                    text: 'Sales Performance by Category and Day'
                },
                xaxis: {
                    categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
                },
                plotOptions: {
                    heatmap: {
                        colorScale: {
                            ranges: [{
                                from: 0,
                                to: 25,
                                name: 'Low',
                                color: '#00A100'
                            }, {
                                from: 26,
                                to: 50,
                                name: 'Medium',
                                color: '#128FD9'
                            }, {
                                from: 51,
                                to: 75,
                                name: 'High',
                                color: '#FFB200'
                            }, {
                                from: 76,
                                to: 100,
                                name: 'Extreme',
                                color: '#FF0000'
                            }]
                        }
                    }
                }
            }
        };
    },
    
    methods: {
        generateHeatmapData() {
            const categories = ['Electronics', 'Clothing', 'Food', 'Books', 'Sports'];
            const series = [];
            
            categories.forEach(category => {
                const data = [];
                for (let i = 0; i < 7; i++) {
                    data.push({
                        x: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][i],
                        y: Math.floor(Math.random() * 100)
                    });
                }
                series.push({
                    name: category,
                    data: data
                });
            });
            
            return series;
        }
    }
});

// Example: Radial Bar for Conversion Metrics
Component.register('nfx-analytics-radial-chart', {
    template: `
        <nfx-apex-chart
            chart-type="radialBar"
            :series="radialSeries"
            :chart-options="radialOptions"
            :real-time-enabled="true"
            :update-interval="5000"
            height="400"
        />
    `,
    
    data() {
        return {
            radialSeries: [67, 84, 97, 61],
            radialOptions: {
                title: {
                    text: 'Conversion Rate by Channel',
                    align: 'center'
                },
                labels: ['Direct', 'Social', 'Email', 'Referral'],
                colors: ['#189EFF', '#00B746', '#FFB200', '#7367F0'],
                plotOptions: {
                    radialBar: {
                        dataLabels: {
                            total: {
                                show: true,
                                label: 'Average',
                                formatter: function (w) {
                                    const avg = w.globals.seriesTotals.reduce((a, b) => a + b, 0) / w.globals.series.length;
                                    return avg.toFixed(0) + '%';
                                }
                            }
                        }
                    }
                }
            }
        };
    }
});

// Example: Dashboard with Multiple Charts
Component.register('nfx-analytics-dashboard', {
    template: `
        <div class="nfx-analytics-dashboard">
            <sw-card-view>
                <sw-card class="nfx-analytics-dashboard__card">
                    <nfx-apex-chart
                        chart-type="area"
                        :series="salesSeries"
                        :chart-options="salesOptions"
                        :real-time-enabled="true"
                        :update-interval="2000"
                        height="300"
                    />
                </sw-card>
                
                <sw-card class="nfx-analytics-dashboard__card">
                    <nfx-apex-chart
                        chart-type="radialBar"
                        :series="goalSeries"
                        :chart-options="goalOptions"
                        height="300"
                    />
                </sw-card>
                
                <sw-card class="nfx-analytics-dashboard__card nfx-analytics-dashboard__card--full">
                    <nfx-apex-chart
                        chart-type="heatmap"
                        :series="activitySeries"
                        :chart-options="activityOptions"
                        height="250"
                    />
                </sw-card>
            </sw-card-view>
        </div>
    `,
    
    data() {
        return {
            // Sales trend data
            salesSeries: [{
                name: 'Sales',
                data: this.generateTimeSeriesData(24, 1000, 5000)
            }],
            salesOptions: {
                title: { text: 'Today\'s Sales' },
                colors: ['#189EFF'],
                stroke: { curve: 'smooth', width: 3 }
            },
            
            // Goal completion data
            goalSeries: [75],
            goalOptions: {
                title: { text: 'Monthly Goal' },
                labels: ['Progress'],
                colors: ['#00B746']
            },
            
            // Activity heatmap data
            activitySeries: [{
                name: 'Activity',
                data: this.generateHourlyActivity()
            }],
            activityOptions: {
                title: { text: 'Hourly Activity' },
                xaxis: {
                    categories: Array.from({length: 24}, (_, i) => `${i}:00`)
                }
            }
        };
    },
    
    methods: {
        generateTimeSeriesData(points, min, max) {
            const data = [];
            const now = Date.now();
            for (let i = points; i > 0; i--) {
                data.push({
                    x: now - i * 60 * 60 * 1000,
                    y: Math.floor(Math.random() * (max - min) + min)
                });
            }
            return data;
        },
        
        generateHourlyActivity() {
            const data = [];
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            
            days.forEach(day => {
                for (let hour = 0; hour < 24; hour++) {
                    data.push({
                        x: `${hour}:00`,
                        y: Math.floor(Math.random() * 100)
                    });
                }
            });
            
            return data;
        }
    }
});