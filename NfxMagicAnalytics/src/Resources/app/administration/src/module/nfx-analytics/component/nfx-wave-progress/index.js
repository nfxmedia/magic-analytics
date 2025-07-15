import template from './nfx-wave-progress.html.twig';
import './nfx-wave-progress.scss';

const { Component } = Shopware;

Component.register('nfx-wave-progress', {
    template,

    props: {
        value: {
            type: Number,
            required: true,
            validator: (value) => value >= 0 && value <= 100
        },
        width: {
            type: Number,
            default: 200
        },
        height: {
            type: Number,
            default: 120
        },
        waveColor: {
            type: String,
            default: '#4ECDC4'
        },
        backgroundColor: {
            type: String,
            default: '#f0f0f0'
        },
        animationDuration: {
            type: Number,
            default: 2000
        },
        waveAmplitude: {
            type: Number,
            default: 0.15
        },
        waveFrequency: {
            type: Number,
            default: 2
        },
        showValue: {
            type: Boolean,
            default: true
        },
        celebrateOnComplete: {
            type: Boolean,
            default: true
        }
    },

    data() {
        return {
            currentValue: 0,
            waveOffset: 0,
            bubbles: [],
            showCelebration: false
        };
    },

    computed: {
        containerStyle() {
            return {
                width: `${this.width}px`,
                height: `${this.height}px`,
                backgroundColor: this.backgroundColor,
                borderRadius: '12px',
                overflow: 'hidden'
            };
        },

        waveHeight() {
            return this.height - (this.currentValue / 100) * this.height;
        },

        wavePoints() {
            const points = [];
            const steps = 100;
            const waveAmplitude = this.height * this.waveAmplitude;
            
            for (let i = 0; i <= steps; i++) {
                const x = (i / steps) * this.width;
                const y = this.waveHeight + 
                    Math.sin((i / steps) * Math.PI * this.waveFrequency + this.waveOffset) * 
                    waveAmplitude * (this.currentValue / 100);
                points.push(`${x},${y}`);
            }
            
            // Complete the polygon
            points.push(`${this.width},${this.height}`);
            points.push(`0,${this.height}`);
            
            return points.join(' ');
        },

        secondaryWavePoints() {
            const points = [];
            const steps = 100;
            const waveAmplitude = this.height * this.waveAmplitude * 0.6;
            
            for (let i = 0; i <= steps; i++) {
                const x = (i / steps) * this.width;
                const y = this.waveHeight + 
                    Math.sin((i / steps) * Math.PI * this.waveFrequency * 1.5 + this.waveOffset * 1.3) * 
                    waveAmplitude * (this.currentValue / 100);
                points.push(`${x},${y}`);
            }
            
            points.push(`${this.width},${this.height}`);
            points.push(`0,${this.height}`);
            
            return points.join(' ');
        },

        formattedValue() {
            return Math.round(this.currentValue);
        }
    },

    watch: {
        value(newVal) {
            this.animateValue(newVal);
        }
    },

    mounted() {
        this.animateValue(this.value);
        this.startWaveAnimation();
    },

    beforeDestroy() {
        if (this.waveAnimationFrame) {
            cancelAnimationFrame(this.waveAnimationFrame);
        }
    },

    methods: {
        animateValue(targetValue) {
            const startValue = this.currentValue;
            const startTime = performance.now();
            const duration = this.animationDuration;

            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                // Ease out cubic
                const easedProgress = 1 - Math.pow(1 - progress, 3);
                
                this.currentValue = startValue + (targetValue - startValue) * easedProgress;

                // Create bubbles at certain progress points
                if (Math.random() < 0.1 && this.currentValue > 10) {
                    this.createBubble();
                }

                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    this.currentValue = targetValue;
                    if (targetValue === 100 && this.celebrateOnComplete) {
                        this.celebrate();
                    }
                }
            };

            requestAnimationFrame(animate);
        },

        startWaveAnimation() {
            const animate = () => {
                this.waveOffset += 0.03;
                this.updateBubbles();
                this.waveAnimationFrame = requestAnimationFrame(animate);
            };
            
            this.waveAnimationFrame = requestAnimationFrame(animate);
        },

        createBubble() {
            if (this.bubbles.length > 20) return; // Limit bubbles

            const bubble = {
                id: Date.now() + Math.random(),
                x: Math.random() * this.width,
                y: this.height,
                size: 3 + Math.random() * 6,
                speed: 0.5 + Math.random() * 1.5,
                opacity: 0.3 + Math.random() * 0.4,
                oscillation: Math.random() * 0.02
            };

            this.bubbles.push(bubble);
        },

        updateBubbles() {
            this.bubbles = this.bubbles.map(bubble => ({
                ...bubble,
                y: bubble.y - bubble.speed,
                x: bubble.x + Math.sin(bubble.y * bubble.oscillation) * 0.5,
                opacity: bubble.opacity * 0.995
            })).filter(bubble => bubble.y > this.waveHeight - 20 && bubble.opacity > 0.1);
        },

        celebrate() {
            this.showCelebration = true;
            this.createCelebrationBubbles();

            setTimeout(() => {
                this.showCelebration = false;
            }, 3000);
        },

        createCelebrationBubbles() {
            for (let i = 0; i < 30; i++) {
                setTimeout(() => {
                    this.createBubble();
                }, i * 100);
            }
        }
    }
});