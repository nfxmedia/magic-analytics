import template from './nfx-stock-prediction.html.twig';
import './nfx-stock-prediction.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('nfx-stock-prediction', {
    template,

    inject: ['stockPredictionService'],

    data() {
        return {
            isLoading: false,
            predictions: [],
            selectedProduct: null,
            timeRange: '7d',
            updateInterval: null,
            chartData: null,
            confidenceMetrics: {
                overall: 0,
                shortTerm: 0,
                longTerm: 0,
                accuracy: 0
            },
            alerts: [],
            animationFrame: null,
            chartInstance: null,
            realTimeData: [],
            historicalData: [],
            predictionBands: {
                upper: [],
                lower: [],
                mean: []
            }
        };
    },

    computed: {
        timeRangeOptions() {
            return [
                { value: '7d', label: this.$tc('nfx-analytics.stockPrediction.timeRange.week') },
                { value: '30d', label: this.$tc('nfx-analytics.stockPrediction.timeRange.month') },
                { value: '90d', label: this.$tc('nfx-analytics.stockPrediction.timeRange.quarter') },
                { value: '365d', label: this.$tc('nfx-analytics.stockPrediction.timeRange.year') }
            ];
        },

        criticalAlerts() {
            return this.alerts.filter(alert => alert.severity === 'critical');
        },

        warningAlerts() {
            return this.alerts.filter(alert => alert.severity === 'warning');
        },

        confidenceColor() {
            const confidence = this.confidenceMetrics.overall;
            if (confidence >= 80) return '#4caf50';
            if (confidence >= 60) return '#ff9800';
            return '#f44336';
        },

        accuracyTrend() {
            if (!this.historicalData.length) return 'stable';
            const recent = this.historicalData.slice(-10);
            const trend = recent.reduce((acc, val, idx) => {
                if (idx === 0) return acc;
                return acc + (val.accuracy - recent[idx - 1].accuracy);
            }, 0);
            return trend > 0.1 ? 'improving' : trend < -0.1 ? 'declining' : 'stable';
        }
    },

    created() {
        this.createdComponent();
    },

    destroyed() {
        this.destroyedComponent();
    },

    methods: {
        createdComponent() {
            this.loadPredictions();
            this.startRealTimeUpdates();
            this.initializeChart();
        },

        destroyedComponent() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
            }
            if (this.animationFrame) {
                cancelAnimationFrame(this.animationFrame);
            }
            if (this.chartInstance) {
                this.chartInstance.destroy();
            }
        },

        async loadPredictions() {
            this.isLoading = true;

            try {
                const response = await this.stockPredictionService.getPredictions({
                    timeRange: this.timeRange,
                    productId: this.selectedProduct?.id
                });

                this.predictions = response.predictions;
                this.confidenceMetrics = response.confidence;
                this.alerts = response.alerts;
                this.historicalData = response.historical;
                this.realTimeData = response.realTime;
                
                this.updateChartData();
                this.calculatePredictionBands();
                this.animateMetrics();
                
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('nfx-analytics.stockPrediction.error.title'),
                    message: error.message
                });
            } finally {
                this.isLoading = false;
            }
        },

        startRealTimeUpdates() {
            this.updateInterval = setInterval(() => {
                this.fetchRealTimeUpdate();
            }, 5000); // Update every 5 seconds
        },

        async fetchRealTimeUpdate() {
            try {
                const update = await this.stockPredictionService.getRealTimeUpdate({
                    productId: this.selectedProduct?.id
                });

                this.mergeRealTimeData(update);
                this.updatePredictionConfidence(update.confidence);
                this.checkForNewAlerts(update.alerts);
                this.smoothChartTransition();
                
            } catch (error) {
                console.error('Real-time update failed:', error);
            }
        },

        mergeRealTimeData(update) {
            // Add new data point with smooth transition
            const newPoint = {
                timestamp: update.timestamp,
                actual: update.actual,
                predicted: update.predicted,
                confidence: update.confidence
            };

            this.realTimeData.push(newPoint);
            
            // Keep only recent data points
            if (this.realTimeData.length > 100) {
                this.realTimeData.shift();
            }

            // Update prediction bands
            this.updatePredictionBands(newPoint);
        },

        updatePredictionBands(newPoint) {
            const confidenceInterval = newPoint.confidence / 100;
            const variance = Math.abs(newPoint.predicted * (1 - confidenceInterval));
            
            this.predictionBands.upper.push({
                x: newPoint.timestamp,
                y: newPoint.predicted + variance
            });
            
            this.predictionBands.mean.push({
                x: newPoint.timestamp,
                y: newPoint.predicted
            });
            
            this.predictionBands.lower.push({
                x: newPoint.timestamp,
                y: Math.max(0, newPoint.predicted - variance)
            });

            // Maintain array size
            ['upper', 'mean', 'lower'].forEach(band => {
                if (this.predictionBands[band].length > 100) {
                    this.predictionBands[band].shift();
                }
            });
        },

        initializeChart() {
            const canvas = this.$refs.predictionChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            
            this.chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    datasets: [
                        {
                            label: this.$tc('nfx-analytics.stockPrediction.actual'),
                            data: [],
                            borderColor: '#2196f3',
                            backgroundColor: 'rgba(33, 150, 243, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: this.$tc('nfx-analytics.stockPrediction.predicted'),
                            data: [],
                            borderColor: '#4caf50',
                            backgroundColor: 'rgba(76, 175, 80, 0.1)',
                            borderWidth: 3,
                            borderDash: [5, 5],
                            tension: 0.4,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: this.$tc('nfx-analytics.stockPrediction.upperBound'),
                            data: [],
                            borderColor: 'rgba(255, 152, 0, 0.3)',
                            backgroundColor: 'rgba(255, 152, 0, 0.05)',
                            borderWidth: 1,
                            fill: '+1',
                            pointRadius: 0,
                            tension: 0.4
                        },
                        {
                            label: this.$tc('nfx-analytics.stockPrediction.lowerBound'),
                            data: [],
                            borderColor: 'rgba(255, 152, 0, 0.3)',
                            backgroundColor: 'rgba(255, 152, 0, 0.05)',
                            borderWidth: 1,
                            fill: '-1',
                            pointRadius: 0,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                afterLabel: (context) => {
                                    if (context.datasetIndex === 1) {
                                        const confidence = this.getConfidenceAtPoint(context.parsed.x);
                                        return `Confidence: ${confidence}%`;
                                    }
                                    return '';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'hour',
                                displayFormats: {
                                    hour: 'HH:mm'
                                }
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            title: {
                                display: true,
                                text: this.$tc('nfx-analytics.stockPrediction.stockLevel')
                            }
                        }
                    },
                    animation: {
                        duration: 750,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        },

        updateChartData() {
            if (!this.chartInstance) return;

            const actualData = this.realTimeData.map(point => ({
                x: point.timestamp,
                y: point.actual
            }));

            const predictedData = this.realTimeData.map(point => ({
                x: point.timestamp,
                y: point.predicted
            }));

            this.chartInstance.data.datasets[0].data = actualData;
            this.chartInstance.data.datasets[1].data = predictedData;
            this.chartInstance.data.datasets[2].data = this.predictionBands.upper;
            this.chartInstance.data.datasets[3].data = this.predictionBands.lower;

            this.chartInstance.update('none'); // No animation for smooth real-time updates
        },

        smoothChartTransition() {
            if (!this.chartInstance) return;

            // Use requestAnimationFrame for smooth transitions
            this.animationFrame = requestAnimationFrame(() => {
                this.updateChartData();
            });
        },

        animateMetrics() {
            const duration = 1000;
            const startTime = performance.now();
            const startValues = {
                overall: this.confidenceMetrics.overall || 0,
                shortTerm: this.confidenceMetrics.shortTerm || 0,
                longTerm: this.confidenceMetrics.longTerm || 0,
                accuracy: this.confidenceMetrics.accuracy || 0
            };

            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeProgress = this.easeInOutQuart(progress);

                Object.keys(startValues).forEach(key => {
                    const start = startValues[key];
                    const end = this.confidenceMetrics[key];
                    const current = start + (end - start) * easeProgress;
                    
                    // Update circular progress indicators
                    if (this.$refs[`${key}Progress`]) {
                        this.updateCircularProgress(this.$refs[`${key}Progress`], current);
                    }
                });

                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };

            requestAnimationFrame(animate);
        },

        updateCircularProgress(element, value) {
            const circle = element.querySelector('.progress-ring__circle');
            const radius = circle.r.baseVal.value;
            const circumference = radius * 2 * Math.PI;
            const offset = circumference - (value / 100) * circumference;
            
            circle.style.strokeDasharray = `${circumference} ${circumference}`;
            circle.style.strokeDashoffset = offset;
            
            // Update text
            const text = element.querySelector('.progress-text');
            if (text) {
                text.textContent = `${Math.round(value)}%`;
            }
        },

        easeInOutQuart(t) {
            return t < 0.5 ? 8 * t * t * t * t : 1 - 8 * (--t) * t * t * t;
        },

        updatePredictionConfidence(newConfidence) {
            // Smooth transition for confidence updates
            const oldConfidence = this.confidenceMetrics.overall;
            const steps = 20;
            const stepValue = (newConfidence.overall - oldConfidence) / steps;
            let currentStep = 0;

            const updateStep = () => {
                if (currentStep < steps) {
                    this.confidenceMetrics.overall = oldConfidence + (stepValue * currentStep);
                    currentStep++;
                    requestAnimationFrame(updateStep);
                } else {
                    this.confidenceMetrics = newConfidence;
                }
            };

            requestAnimationFrame(updateStep);
        },

        checkForNewAlerts(newAlerts) {
            const existingIds = this.alerts.map(a => a.id);
            const brandNewAlerts = newAlerts.filter(a => !existingIds.includes(a.id));

            brandNewAlerts.forEach(alert => {
                this.alerts.unshift(alert);
                this.showAlertNotification(alert);
            });

            // Keep only recent alerts
            this.alerts = this.alerts.slice(0, 10);
        },

        showAlertNotification(alert) {
            const notificationType = alert.severity === 'critical' ? 'error' : 'warning';
            
            this[`createNotification${notificationType.charAt(0).toUpperCase() + notificationType.slice(1)}`]({
                title: alert.title,
                message: alert.message,
                autoClose: true
            });
        },

        onProductSelected(product) {
            this.selectedProduct = product;
            this.loadPredictions();
        },

        onTimeRangeChange() {
            this.loadPredictions();
        },

        calculatePredictionBands() {
            // Calculate confidence bands based on historical accuracy
            const accuracy = this.confidenceMetrics.accuracy / 100;
            const stdDev = this.calculateStandardDeviation();
            
            this.realTimeData.forEach((point, index) => {
                const variance = stdDev * (2 - accuracy); // Adjust variance based on accuracy
                
                this.predictionBands.upper[index] = {
                    x: point.timestamp,
                    y: point.predicted + variance
                };
                
                this.predictionBands.mean[index] = {
                    x: point.timestamp,
                    y: point.predicted
                };
                
                this.predictionBands.lower[index] = {
                    x: point.timestamp,
                    y: Math.max(0, point.predicted - variance)
                };
            });
        },

        calculateStandardDeviation() {
            if (!this.historicalData.length) return 0;
            
            const values = this.historicalData.map(d => d.actual);
            const mean = values.reduce((a, b) => a + b, 0) / values.length;
            const variance = values.reduce((a, b) => a + Math.pow(b - mean, 2), 0) / values.length;
            
            return Math.sqrt(variance);
        },

        getConfidenceAtPoint(timestamp) {
            const point = this.realTimeData.find(p => p.timestamp === timestamp);
            return point ? point.confidence : this.confidenceMetrics.overall;
        },

        exportPredictions() {
            const data = {
                product: this.selectedProduct,
                timeRange: this.timeRange,
                predictions: this.predictions,
                confidence: this.confidenceMetrics,
                generated: new Date().toISOString()
            };

            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `stock-predictions-${Date.now()}.json`;
            a.click();
            URL.revokeObjectURL(url);
        },

        dismissAlert(alertId) {
            const index = this.alerts.findIndex(a => a.id === alertId);
            if (index !== -1) {
                this.alerts.splice(index, 1);
            }
        }
    }
});