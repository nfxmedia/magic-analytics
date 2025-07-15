const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

class StockPredictionService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'nfx-analytics') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'stockPredictionService';
        
        // Cache for predictions to reduce API calls
        this.predictionCache = new Map();
        this.cacheTimeout = 30000; // 30 seconds
        
        // WebSocket connection for real-time updates (if available)
        this.wsConnection = null;
        this.wsReconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
    }

    /**
     * Get stock predictions with AI/ML analysis
     * @param {Object} params - Request parameters
     * @returns {Promise<Object>} Prediction data
     */
    async getPredictions(params = {}) {
        const cacheKey = this.getCacheKey(params);
        const cached = this.getCachedData(cacheKey);
        
        if (cached) {
            return cached;
        }

        const headers = this.getBasicHeaders();
        
        try {
            const response = await this.httpClient.post(
                `${this.getApiBasePath()}/stock-predictions`,
                {
                    productId: params.productId,
                    timeRange: params.timeRange || '7d',
                    includeConfidenceBands: true,
                    includeAlerts: true,
                    modelType: 'ensemble' // Use ensemble of models for better accuracy
                },
                { headers }
            );

            const data = this.processPredictionData(response.data);
            this.setCachedData(cacheKey, data);
            
            return data;
        } catch (error) {
            console.error('Failed to fetch predictions:', error);
            
            // Return mock data for development/demo
            return this.getMockPredictionData(params);
        }
    }

    /**
     * Get real-time updates for stock predictions
     * @param {Object} params - Request parameters
     * @returns {Promise<Object>} Real-time update data
     */
    async getRealTimeUpdate(params = {}) {
        // If WebSocket is available, use it
        if (this.wsConnection && this.wsConnection.readyState === WebSocket.OPEN) {
            return new Promise((resolve) => {
                this.wsConnection.send(JSON.stringify({
                    action: 'getUpdate',
                    productId: params.productId
                }));
                
                this.wsConnection.onmessage = (event) => {
                    resolve(JSON.parse(event.data));
                };
            });
        }

        // Fallback to HTTP polling
        const headers = this.getBasicHeaders();
        
        try {
            const response = await this.httpClient.post(
                `${this.getApiBasePath()}/stock-predictions/realtime`,
                {
                    productId: params.productId,
                    lastUpdate: params.lastUpdate || new Date().toISOString()
                },
                { headers }
            );

            return this.processRealTimeUpdate(response.data);
        } catch (error) {
            console.error('Failed to fetch real-time update:', error);
            
            // Return mock real-time data
            return this.getMockRealTimeUpdate();
        }
    }

    /**
     * Train or retrain the ML model with new data
     * @param {Object} params - Training parameters
     * @returns {Promise<Object>} Training result
     */
    async trainModel(params = {}) {
        const headers = this.getBasicHeaders();
        
        try {
            const response = await this.httpClient.post(
                `${this.getApiBasePath()}/stock-predictions/train`,
                {
                    productId: params.productId,
                    trainingData: params.trainingData,
                    modelType: params.modelType || 'ensemble',
                    hyperparameters: params.hyperparameters || {}
                },
                { headers }
            );

            return response.data;
        } catch (error) {
            console.error('Failed to train model:', error);
            throw error;
        }
    }

    /**
     * Get model performance metrics
     * @param {Object} params - Request parameters
     * @returns {Promise<Object>} Performance metrics
     */
    async getModelMetrics(params = {}) {
        const headers = this.getBasicHeaders();
        
        try {
            const response = await this.httpClient.get(
                `${this.getApiBasePath()}/stock-predictions/metrics`,
                {
                    params: {
                        productId: params.productId,
                        timeRange: params.timeRange || '30d'
                    },
                    headers
                }
            );

            return response.data;
        } catch (error) {
            console.error('Failed to fetch model metrics:', error);
            
            // Return mock metrics
            return this.getMockModelMetrics();
        }
    }

    /**
     * Initialize WebSocket connection for real-time updates
     */
    initializeWebSocket() {
        if (!window.WebSocket) {
            console.warn('WebSocket not supported');
            return;
        }

        const wsUrl = this.getWebSocketUrl();
        
        try {
            this.wsConnection = new WebSocket(wsUrl);
            
            this.wsConnection.onopen = () => {
                console.log('WebSocket connected for stock predictions');
                this.wsReconnectAttempts = 0;
            };
            
            this.wsConnection.onclose = () => {
                console.log('WebSocket disconnected');
                this.handleWebSocketReconnect();
            };
            
            this.wsConnection.onerror = (error) => {
                console.error('WebSocket error:', error);
            };
        } catch (error) {
            console.error('Failed to initialize WebSocket:', error);
        }
    }

    /**
     * Handle WebSocket reconnection with exponential backoff
     */
    handleWebSocketReconnect() {
        if (this.wsReconnectAttempts >= this.maxReconnectAttempts) {
            console.warn('Max WebSocket reconnection attempts reached');
            return;
        }

        const backoffTime = Math.min(1000 * Math.pow(2, this.wsReconnectAttempts), 30000);
        this.wsReconnectAttempts++;

        setTimeout(() => {
            console.log(`Attempting WebSocket reconnection (${this.wsReconnectAttempts}/${this.maxReconnectAttempts})`);
            this.initializeWebSocket();
        }, backoffTime);
    }

    /**
     * Process raw prediction data
     */
    processPredictionData(data) {
        return {
            predictions: data.predictions.map(p => ({
                ...p,
                confidence: Math.round(p.confidence * 100) / 100,
                upperBound: p.predicted + (p.predicted * p.variance),
                lowerBound: Math.max(0, p.predicted - (p.predicted * p.variance))
            })),
            confidence: {
                overall: Math.round(data.confidence.overall * 100) / 100,
                shortTerm: Math.round(data.confidence.shortTerm * 100) / 100,
                longTerm: Math.round(data.confidence.longTerm * 100) / 100,
                accuracy: Math.round(data.accuracy * 100) / 100
            },
            alerts: this.processAlerts(data.alerts || []),
            historical: data.historical || [],
            realTime: data.realTime || []
        };
    }

    /**
     * Process alerts and add severity levels
     */
    processAlerts(alerts) {
        return alerts.map(alert => ({
            ...alert,
            id: alert.id || this.generateId(),
            severity: this.calculateAlertSeverity(alert),
            timestamp: alert.timestamp || new Date().toISOString()
        }));
    }

    /**
     * Calculate alert severity based on conditions
     */
    calculateAlertSeverity(alert) {
        if (alert.type === 'stockout' || alert.urgency === 'immediate') {
            return 'critical';
        }
        if (alert.type === 'low_stock' || alert.urgency === 'high') {
            return 'warning';
        }
        return 'info';
    }

    /**
     * Process real-time update data
     */
    processRealTimeUpdate(data) {
        return {
            timestamp: data.timestamp || new Date().toISOString(),
            actual: data.actual,
            predicted: data.predicted,
            confidence: data.confidence,
            alerts: this.processAlerts(data.alerts || []),
            trend: data.trend || 'stable'
        };
    }

    /**
     * Cache management methods
     */
    getCacheKey(params) {
        return JSON.stringify(params);
    }

    getCachedData(key) {
        const cached = this.predictionCache.get(key);
        if (cached && Date.now() - cached.timestamp < this.cacheTimeout) {
            return cached.data;
        }
        this.predictionCache.delete(key);
        return null;
    }

    setCachedData(key, data) {
        this.predictionCache.set(key, {
            data,
            timestamp: Date.now()
        });
    }

    /**
     * Get WebSocket URL
     */
    getWebSocketUrl() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const host = window.location.host;
        return `${protocol}//${host}/api/nfx-analytics/ws/stock-predictions`;
    }

    /**
     * Generate unique ID
     */
    generateId() {
        return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Mock data methods for development/demo
     */
    getMockPredictionData(params) {
        const now = new Date();
        const predictions = [];
        const historical = [];
        const realTime = [];
        
        // Generate mock predictions
        for (let i = 0; i < 30; i++) {
            const date = new Date(now);
            date.setDate(date.getDate() + i);
            
            const baseStock = 100 + Math.random() * 50;
            const predicted = baseStock + (Math.random() - 0.5) * 20;
            const confidence = 70 + Math.random() * 25;
            
            predictions.push({
                date: date.toISOString(),
                predicted: Math.round(predicted),
                confidence: Math.round(confidence),
                variance: 0.1 + Math.random() * 0.2
            });
        }
        
        // Generate historical data
        for (let i = 30; i > 0; i--) {
            const date = new Date(now);
            date.setDate(date.getDate() - i);
            
            const actual = 100 + Math.random() * 50;
            const predicted = actual + (Math.random() - 0.5) * 10;
            
            historical.push({
                date: date.toISOString(),
                actual: Math.round(actual),
                predicted: Math.round(predicted),
                accuracy: 85 + Math.random() * 10
            });
        }
        
        // Generate real-time data
        for (let i = 0; i < 50; i++) {
            const timestamp = new Date(now.getTime() - (50 - i) * 60000);
            const actual = 100 + Math.random() * 50;
            const predicted = actual + (Math.random() - 0.5) * 10;
            
            realTime.push({
                timestamp: timestamp.toISOString(),
                actual: Math.round(actual),
                predicted: Math.round(predicted),
                confidence: 70 + Math.random() * 25
            });
        }
        
        return this.processPredictionData({
            predictions,
            confidence: {
                overall: 85 + Math.random() * 10,
                shortTerm: 88 + Math.random() * 10,
                longTerm: 82 + Math.random() * 10
            },
            accuracy: 87 + Math.random() * 8,
            alerts: [
                {
                    type: 'low_stock',
                    title: 'Low Stock Warning',
                    message: 'Stock levels predicted to drop below safety threshold in 5 days',
                    urgency: 'high'
                }
            ],
            historical,
            realTime
        });
    }

    getMockRealTimeUpdate() {
        const actual = 100 + Math.random() * 50;
        const predicted = actual + (Math.random() - 0.5) * 10;
        
        return {
            timestamp: new Date().toISOString(),
            actual: Math.round(actual),
            predicted: Math.round(predicted),
            confidence: 70 + Math.random() * 25,
            alerts: Math.random() > 0.8 ? [{
                type: 'anomaly',
                title: 'Unusual Stock Movement Detected',
                message: 'Current stock levels deviate significantly from predictions',
                urgency: 'medium'
            }] : [],
            trend: Math.random() > 0.5 ? 'increasing' : 'decreasing'
        };
    }

    getMockModelMetrics() {
        return {
            mape: 5.2 + Math.random() * 3, // Mean Absolute Percentage Error
            rmse: 8.7 + Math.random() * 4, // Root Mean Square Error
            mae: 6.1 + Math.random() * 3,  // Mean Absolute Error
            r2: 0.92 + Math.random() * 0.06, // R-squared
            trainingLoss: Array.from({ length: 100 }, (_, i) => ({
                epoch: i,
                loss: 1 / (i + 1) + Math.random() * 0.1
            })),
            featureImportance: [
                { feature: 'Historical Sales', importance: 0.35 },
                { feature: 'Seasonality', importance: 0.25 },
                { feature: 'Trend', importance: 0.20 },
                { feature: 'Promotions', importance: 0.15 },
                { feature: 'External Factors', importance: 0.05 }
            ]
        };
    }
}

// Register the service
Application.addServiceProvider('stockPredictionService', (container) => {
    const initContainer = Application.getContainer('init');
    return new StockPredictionService(
        initContainer.httpClient,
        container.loginService
    );
});

export default StockPredictionService;