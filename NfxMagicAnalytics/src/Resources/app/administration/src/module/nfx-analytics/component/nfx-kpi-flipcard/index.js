import template from './nfx-kpi-flipcard.html.twig';
import './nfx-kpi-flipcard.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

/**
 * NfxKpiFlipcard Component
 * 
 * Animated KPI card with flip effect, liquid fill animations,
 * number counters, and morphing SVG shapes
 * 
 * @component nfx-kpi-flipcard
 * @example
 * <nfx-kpi-flipcard
 *     :value="salesData.total"
 *     :previous-value="salesData.previous"
 *     :title="$tc('nfx-analytics.kpi.totalSales')"
 *     :unit="currency"
 *     :liquid-percentage="75"
 *     theme="success"
 *     :details="detailsData">
 * </nfx-kpi-flipcard>
 */
Component.register('nfx-kpi-flipcard', {
    template,

    inject: ['repositoryFactory'],

    props: {
        value: {
            type: Number,
            required: true,
            default: 0
        },
        previousValue: {
            type: Number,
            default: null
        },
        title: {
            type: String,
            required: true
        },
        icon: {
            type: String,
            default: 'default-chart-line'
        },
        unit: {
            type: String,
            default: ''
        },
        liquidPercentage: {
            type: Number,
            default: 50,
            validator: value => value >= 0 && value <= 100
        },
        theme: {
            type: String,
            default: 'primary',
            validator: value => ['primary', 'success', 'danger', 'warning', 'info'].includes(value)
        },
        size: {
            type: String,
            default: 'medium',
            validator: value => ['small', 'medium', 'large'].includes(value)
        },
        details: {
            type: Array,
            default: () => []
        },
        animated: {
            type: Boolean,
            default: true
        },
        autoFlip: {
            type: Boolean,
            default: false
        },
        flipDuration: {
            type: Number,
            default: 5000
        },
        loading: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            isFlipped: false,
            animatedValue: 0,
            liquidHeight: 0,
            progressOffset: 0,
            bubbles: [],
            particles: [],
            morphPath: '',
            autoFlipInterval: null,
            isAnimating: false,
            digitAnimations: new Map()
        };
    },

    computed: {
        componentClasses() {
            return {
                'nfx-kpi-flipcard': true,
                [`nfx-kpi-flipcard--${this.theme}`]: true,
                [`nfx-kpi-flipcard--${this.size}`]: true,
                'is--flipped': this.isFlipped,
                'is--loading': this.loading
            };
        },

        formattedValue() {
            return this.formatNumber(this.animatedValue);
        },

        trendPercentage() {
            if (!this.previousValue || this.previousValue === 0) {
                return 0;
            }
            return ((this.value - this.previousValue) / this.previousValue * 100).toFixed(1);
        },

        isTrendUp() {
            return this.trendPercentage > 0;
        },

        progressCircumference() {
            const radius = 50;
            return 2 * Math.PI * radius;
        },

        liquidStyle() {
            return {
                '--liquid-height': `${this.liquidHeight}%`
            };
        },

        progressStyle() {
            return {
                '--circumference': this.progressCircumference,
                '--offset': this.progressOffset
            };
        },

        svgGradientId() {
            return `gradient-${this._uid}`;
        },

        liquidGradientId() {
            return `liquid-gradient-${this._uid}`;
        },

        progressGradientId() {
            return `progress-gradient-${this._uid}`;
        }
    },

    watch: {
        value: {
            handler(newValue) {
                if (this.animated) {
                    this.animateNumber(this.animatedValue, newValue);
                } else {
                    this.animatedValue = newValue;
                }
            },
            immediate: true
        },

        liquidPercentage: {
            handler(newValue) {
                this.animateLiquid(newValue);
            },
            immediate: true
        },

        autoFlip(newValue) {
            if (newValue) {
                this.startAutoFlip();
            } else {
                this.stopAutoFlip();
            }
        }
    },

    mounted() {
        this.initializeAnimations();
        if (this.autoFlip) {
            this.startAutoFlip();
        }
        
        // Generate initial particles and bubbles
        this.generateParticles();
        this.generateBubbles();
        
        // Start morph animation
        this.startMorphAnimation();
        
        // Initialize progress animation
        this.animateProgress();
    },

    beforeDestroy() {
        this.stopAutoFlip();
        this.cleanupAnimations();
    },

    methods: {
        initializeAnimations() {
            // GSAP-style animation setup
            this.animationTimeline = {
                liquid: null,
                number: null,
                morph: null
            };
        },

        animateNumber(from, to) {
            const duration = 1500;
            const startTime = performance.now();
            const difference = to - from;
            
            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Cubic easing
                const easeProgress = 1 - Math.pow(1 - progress, 3);
                
                this.animatedValue = from + (difference * easeProgress);
                
                // Animate individual digits
                this.animateDigits();
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    this.animatedValue = to;
                }
            };
            
            requestAnimationFrame(animate);
        },

        animateDigits() {
            const valueString = Math.floor(this.animatedValue).toString();
            const digits = valueString.split('');
            
            digits.forEach((digit, index) => {
                const digitKey = `digit-${index}`;
                const previousDigit = this.digitAnimations.get(digitKey);
                
                if (previousDigit !== digit) {
                    this.digitAnimations.set(digitKey, digit);
                    this.$nextTick(() => {
                        const digitElement = this.$el.querySelector(`.digit:nth-child(${index + 1})`);
                        if (digitElement) {
                            digitElement.classList.add('is--animating');
                            setTimeout(() => {
                                digitElement.classList.remove('is--animating');
                            }, 600);
                        }
                    });
                }
            });
        },

        animateLiquid(percentage) {
            const duration = 1200;
            const startHeight = this.liquidHeight;
            const targetHeight = percentage;
            const startTime = performance.now();
            
            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Spring easing
                const easeProgress = 1 - Math.cos(progress * Math.PI * 0.5);
                
                this.liquidHeight = startHeight + ((targetHeight - startHeight) * easeProgress);
                
                // Add wave effect
                this.updateWaveEffect(progress);
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };
            
            requestAnimationFrame(animate);
        },

        updateWaveEffect(progress) {
            const waveElement = this.$el.querySelector('.nfx-kpi-flipcard__liquid::before');
            if (waveElement) {
                const waveHeight = 5 + (Math.sin(progress * Math.PI * 2) * 3);
                waveElement.style.setProperty('--wave-height', `${waveHeight}px`);
            }
        },

        animateProgress() {
            const percentage = this.liquidPercentage;
            const offset = this.progressCircumference - (percentage / 100 * this.progressCircumference);
            
            setTimeout(() => {
                this.progressOffset = offset;
            }, 100);
        },

        generateBubbles() {
            const bubbleCount = 5;
            this.bubbles = Array.from({ length: bubbleCount }, (_, index) => ({
                id: index,
                size: Math.random() * 15 + 10,
                x: Math.random() * 80 + 10,
                delay: Math.random() * 3,
                duration: Math.random() * 2 + 3
            }));
        },

        generateParticles() {
            const particleCount = 10;
            this.particles = Array.from({ length: particleCount }, (_, index) => ({
                id: index,
                cx: Math.random() * 100,
                cy: Math.random() * 100,
                r: Math.random() * 3 + 1
            }));
        },

        startMorphAnimation() {
            const morphShapes = [
                'M0,100 C50,100 50,0 100,0 L100,100 Z',
                'M0,100 C30,50 70,50 100,100 L100,100 Z',
                'M0,100 C20,80 80,20 100,0 L100,100 Z',
                'M0,100 C40,60 60,40 100,100 L100,100 Z'
            ];
            
            let shapeIndex = 0;
            
            const morph = () => {
                this.morphPath = morphShapes[shapeIndex];
                shapeIndex = (shapeIndex + 1) % morphShapes.length;
            };
            
            morph();
            this.morphInterval = setInterval(morph, 2000);
        },

        flipCard() {
            this.isFlipped = !this.isFlipped;
            this.$emit('flip', this.isFlipped);
            
            // Add haptic feedback simulation
            this.simulateHaptic();
        },

        simulateHaptic() {
            const card = this.$el.querySelector('.nfx-kpi-flipcard__inner');
            if (card) {
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    card.style.transform = '';
                }, 100);
            }
        },

        startAutoFlip() {
            if (this.autoFlipInterval) return;
            
            this.autoFlipInterval = setInterval(() => {
                this.flipCard();
            }, this.flipDuration);
        },

        stopAutoFlip() {
            if (this.autoFlipInterval) {
                clearInterval(this.autoFlipInterval);
                this.autoFlipInterval = null;
            }
        },

        formatNumber(value) {
            if (typeof value !== 'number') return '0';
            
            // Format based on size
            if (value >= 1000000) {
                return (value / 1000000).toFixed(1) + 'M';
            } else if (value >= 1000) {
                return (value / 1000).toFixed(1) + 'K';
            }
            
            return value.toFixed(0);
        },

        cleanupAnimations() {
            if (this.morphInterval) {
                clearInterval(this.morphInterval);
            }
            
            // Cleanup any running animations
            Object.values(this.animationTimeline).forEach(timeline => {
                if (timeline && timeline.kill) {
                    timeline.kill();
                }
            });
        },

        onMouseEnter() {
            this.$emit('mouseenter');
            
            // Speed up liquid animation on hover
            const liquidElement = this.$el.querySelector('.nfx-kpi-flipcard__liquid');
            if (liquidElement) {
                liquidElement.style.setProperty('--animation-speed', '2s');
            }
        },

        onMouseLeave() {
            this.$emit('mouseleave');
            
            // Reset liquid animation speed
            const liquidElement = this.$el.querySelector('.nfx-kpi-flipcard__liquid');
            if (liquidElement) {
                liquidElement.style.setProperty('--animation-speed', '4s');
            }
        },

        getDetailValue(detail) {
            if (typeof detail.value === 'number') {
                return this.formatNumber(detail.value);
            }
            return detail.value;
        }
    }
});