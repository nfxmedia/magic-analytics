import template from './nfx-apex-chart.html.twig';
import './nfx-apex-chart.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('nfx-apex-chart', {
    template,

    inject: ['repositoryFactory', 'context'],

    props: {
        chartType: {
            type: String,
            required: true,
            validator: (value) => ['area', 'candlestick', 'heatmap', 'radialBar'].includes(value)
        },
        chartOptions: {
            type: Object,
            default: () => ({})
        },
        series: {
            type: Array,
            required: true
        },
        height: {
            type: String,
            default: '350'
        },
        realTimeEnabled: {
            type: Boolean,
            default: false
        },
        updateInterval: {
            type: Number,
            default: 2000
        },
        animationEnabled: {
            type: Boolean,
            default: true
        }
    },

    data() {
        return {
            chart: null,
            websocketConnection: null,
            updateTimer: null,
            isLoading: false,
            chartId: `apex-chart-${Math.random().toString(36).substr(2, 9)}`
        };
    },

    computed: {
        mergedOptions() {
            const defaultOptions = this.getDefaultOptions();
            return this.deepMerge(defaultOptions, this.chartOptions);
        }
    },

    watch: {
        series: {
            deep: true,
            handler(newSeries) {
                if (this.chart && !this.realTimeEnabled) {
                    this.updateChart(newSeries);
                }
            }
        },
        chartOptions: {
            deep: true,
            handler() {
                this.renderChart();
            }
        }
    },

    mounted() {
        this.initChart();
        
        if (this.realTimeEnabled) {
            this.initRealTimeUpdates();
        }
    },

    beforeDestroy() {
        this.cleanup();
    },

    methods: {
        async initChart() {
            // Dynamically import ApexCharts
            const ApexCharts = await import('apexcharts');
            
            this.$nextTick(() => {
                const chartElement = document.querySelector(`#${this.chartId}`);
                if (chartElement) {
                    this.chart = new ApexCharts.default(chartElement, {
                        ...this.mergedOptions,
                        series: this.series
                    });
                    this.chart.render();
                }
            });
        },

        getDefaultOptions() {
            const baseOptions = {
                chart: {
                    type: this.chartType,
                    height: this.height,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: true,
                            zoom: true,
                            zoomin: true,
                            zoomout: true,
                            pan: true,
                            reset: true
                        }
                    },
                    animations: {
                        enabled: this.animationEnabled,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: {
                            enabled: true,
                            delay: 150
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 350
                        }
                    },
                    zoom: {
                        enabled: true,
                        type: 'x',
                        autoScaleYaxis: true
                    }
                },
                theme: {
                    mode: 'light',
                    palette: 'palette1'
                },
                tooltip: {
                    enabled: true,
                    shared: true,
                    intersect: false,
                    theme: 'light',
                    x: {
                        show: true,
                        format: 'dd MMM yyyy HH:mm'
                    },
                    y: {
                        formatter: (value) => {
                            if (value === null || value === undefined) return 'N/A';
                            return value.toLocaleString();
                        }
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: '100%'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };

            // Type-specific default options
            switch (this.chartType) {
                case 'area':
                    return {
                        ...baseOptions,
                        dataLabels: {
                            enabled: false
                        },
                        stroke: {
                            curve: 'smooth',
                            width: 2
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.7,
                                opacityTo: 0.3,
                                stops: [0, 90, 100]
                            }
                        },
                        xaxis: {
                            type: 'datetime',
                            labels: {
                                datetimeUTC: false
                            }
                        }
                    };

                case 'candlestick':
                    return {
                        ...baseOptions,
                        plotOptions: {
                            candlestick: {
                                colors: {
                                    upward: '#00B746',
                                    downward: '#EF403C'
                                },
                                wick: {
                                    useFillColor: true
                                }
                            }
                        },
                        xaxis: {
                            type: 'datetime'
                        }
                    };

                case 'heatmap':
                    return {
                        ...baseOptions,
                        dataLabels: {
                            enabled: true,
                            style: {
                                colors: ['#fff']
                            }
                        },
                        plotOptions: {
                            heatmap: {
                                shadeIntensity: 0.5,
                                radius: 0,
                                useFillColorAsStroke: true,
                                colorScale: {
                                    ranges: [{
                                        from: -30,
                                        to: 5,
                                        name: 'low',
                                        color: '#00A100'
                                    }, {
                                        from: 6,
                                        to: 20,
                                        name: 'medium',
                                        color: '#128FD9'
                                    }, {
                                        from: 21,
                                        to: 45,
                                        name: 'high',
                                        color: '#FFB200'
                                    }, {
                                        from: 46,
                                        to: 100,
                                        name: 'extreme',
                                        color: '#FF0000'
                                    }]
                                }
                            }
                        }
                    };

                case 'radialBar':
                    return {
                        ...baseOptions,
                        plotOptions: {
                            radialBar: {
                                offsetY: 0,
                                startAngle: 0,
                                endAngle: 270,
                                hollow: {
                                    margin: 5,
                                    size: '30%',
                                    background: 'transparent',
                                    image: undefined
                                },
                                dataLabels: {
                                    name: {
                                        show: true,
                                        fontSize: '22px'
                                    },
                                    value: {
                                        show: true,
                                        fontSize: '16px',
                                        formatter: (val) => `${parseInt(val)}%`
                                    },
                                    total: {
                                        show: true,
                                        label: 'Total',
                                        formatter: (w) => {
                                            const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                            return `${parseInt(sum / w.globals.series.length)}%`;
                                        }
                                    }
                                },
                                track: {
                                    background: '#f2f2f2',
                                    strokeWidth: '97%',
                                    margin: 5
                                }
                            }
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shade: 'dark',
                                type: 'horizontal',
                                shadeIntensity: 0.5,
                                gradientToColors: ['#ABE5A1'],
                                inverseColors: true,
                                opacityFrom: 1,
                                opacityTo: 1,
                                stops: [0, 100]
                            }
                        },
                        legend: {
                            show: true,
                            floating: true,
                            fontSize: '16px',
                            position: 'left',
                            offsetX: 160,
                            offsetY: 15,
                            labels: {
                                useSeriesColors: true
                            },
                            markers: {
                                size: 0
                            },
                            formatter: function(seriesName, opts) {
                                return seriesName + ":  " + opts.w.globals.series[opts.seriesIndex];
                            },
                            itemMargin: {
                                vertical: 3
                            }
                        }
                    };

                default:
                    return baseOptions;
            }
        },

        initRealTimeUpdates() {
            // Simulate WebSocket connection for real-time data
            this.simulateWebSocket();
            
            // Set up periodic updates
            this.updateTimer = setInterval(() => {
                this.fetchLatestData();
            }, this.updateInterval);
        },

        simulateWebSocket() {
            // Simulate WebSocket connection
            this.websocketConnection = {
                send: (message) => {
                    console.log('WebSocket send:', message);
                },
                close: () => {
                    console.log('WebSocket closed');
                }
            };

            // Simulate incoming messages
            const simulateMessage = () => {
                if (!this.chart || !this.realTimeEnabled) return;

                const newData = this.generateRealtimeData();
                this.handleRealtimeUpdate(newData);

                // Schedule next message
                const delay = 1000 + Math.random() * 2000; // 1-3 seconds
                setTimeout(simulateMessage, delay);
            };

            setTimeout(simulateMessage, 1000);
        },

        generateRealtimeData() {
            const timestamp = new Date().getTime();
            
            switch (this.chartType) {
                case 'area':
                    return {
                        x: timestamp,
                        y: Math.floor(Math.random() * 100) + 50
                    };
                
                case 'candlestick':
                    const open = Math.floor(Math.random() * 100) + 100;
                    const close = open + (Math.random() - 0.5) * 20;
                    const high = Math.max(open, close) + Math.random() * 10;
                    const low = Math.min(open, close) - Math.random() * 10;
                    
                    return {
                        x: timestamp,
                        y: [open, high, low, close]
                    };
                
                case 'heatmap':
                    return {
                        x: `Category ${Math.floor(Math.random() * 5) + 1}`,
                        y: Math.floor(Math.random() * 100)
                    };
                
                case 'radialBar':
                    return Math.floor(Math.random() * 100);
                
                default:
                    return null;
            }
        },

        handleRealtimeUpdate(data) {
            if (!this.chart || !data) return;

            try {
                if (this.chartType === 'radialBar') {
                    // Update radial bar chart
                    this.chart.updateSeries([data]);
                } else if (this.chartType === 'heatmap') {
                    // Update heatmap
                    const currentSeries = [...this.series];
                    if (currentSeries[0] && currentSeries[0].data) {
                        currentSeries[0].data.push(data);
                        // Keep only last 50 data points
                        if (currentSeries[0].data.length > 50) {
                            currentSeries[0].data.shift();
                        }
                        this.chart.updateSeries(currentSeries);
                    }
                } else {
                    // Update area and candlestick charts
                    const currentSeries = [...this.series];
                    if (currentSeries[0] && currentSeries[0].data) {
                        currentSeries[0].data.push(data);
                        // Keep only last 50 data points
                        if (currentSeries[0].data.length > 50) {
                            currentSeries[0].data.shift();
                        }
                        this.chart.updateSeries(currentSeries);
                    }
                }
            } catch (error) {
                console.error('Error updating chart:', error);
            }
        },

        async fetchLatestData() {
            // This would typically fetch from your API
            // For now, we'll use the simulated data
            const newData = this.generateRealtimeData();
            if (newData) {
                this.handleRealtimeUpdate(newData);
            }
        },

        updateChart(newSeries) {
            if (this.chart) {
                this.chart.updateSeries(newSeries);
            }
        },

        renderChart() {
            if (this.chart) {
                this.chart.destroy();
            }
            this.initChart();
        },

        deepMerge(target, source) {
            const output = Object.assign({}, target);
            if (this.isObject(target) && this.isObject(source)) {
                Object.keys(source).forEach(key => {
                    if (this.isObject(source[key])) {
                        if (!(key in target)) {
                            Object.assign(output, { [key]: source[key] });
                        } else {
                            output[key] = this.deepMerge(target[key], source[key]);
                        }
                    } else {
                        Object.assign(output, { [key]: source[key] });
                    }
                });
            }
            return output;
        },

        isObject(item) {
            return item && typeof item === 'object' && !Array.isArray(item);
        },

        cleanup() {
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
            
            if (this.updateTimer) {
                clearInterval(this.updateTimer);
                this.updateTimer = null;
            }
            
            if (this.websocketConnection) {
                this.websocketConnection.close();
                this.websocketConnection = null;
            }
        },

        exportChart(type = 'png') {
            if (this.chart) {
                this.chart.dataURI().then(({ imgURI }) => {
                    const link = document.createElement('a');
                    link.href = imgURI;
                    link.download = `chart-${Date.now()}.${type}`;
                    link.click();
                });
            }
        }
    }
});