import template from './nfx-progress-ring.html.twig';
import './nfx-progress-ring.scss';

const { Component } = Shopware;

Component.register('nfx-progress-ring', {
    template,

    props: {
        value: {
            type: Number,
            required: true,
            validator: (value) => value >= 0 && value <= 100
        },
        size: {
            type: Number,
            default: 120
        },
        strokeWidth: {
            type: Number,
            default: 8
        },
        duration: {
            type: Number,
            default: 1500
        },
        gradientColors: {
            type: Array,
            default: () => ['#4ECDC4', '#45B7D1']
        },
        backgroundColor: {
            type: String,
            default: '#e0e0e0'
        },
        showValue: {
            type: Boolean,
            default: true
        },
        showWave: {
            type: Boolean,
            default: false
        },
        waveHeight: {
            type: Number,
            default: 0.3
        },
        celebrateOnComplete: {
            type: Boolean,
            default: true
        },
        pulseAnimation: {
            type: Boolean,
            default: true
        }
    },

    data() {
        return {
            currentValue: 0,
            waveOffset: 0,
            particles: [],
            showCelebration: false,
            pulseScale: 1,
            gradientId: `gradient-${Math.random().toString(36).substr(2, 9)}`
        };
    },

    computed: {
        radius() {
            return (this.size - this.strokeWidth) / 2;
        },

        circumference() {
            return 2 * Math.PI * this.radius;
        },

        strokeDashoffset() {
            return this.circumference - (this.currentValue / 100) * this.circumference;
        },

        viewBox() {
            return `0 0 ${this.size} ${this.size}`;
        },

        center() {
            return this.size / 2;
        },

        wavePoints() {
            if (!this.showWave) return '';
            
            const points = [];
            const waveAmplitude = this.radius * this.waveHeight;
            const steps = 100;
            
            for (let i = 0; i <= steps; i++) {
                const x = (i / steps) * this.size;
                const y = this.center + Math.sin((i / steps) * Math.PI * 4 + this.waveOffset) * waveAmplitude * (this.currentValue / 100);
                points.push(`${x},${y}`);
            }
            
            // Close the path
            points.push(`${this.size},${this.size}`);
            points.push(`0,${this.size}`);
            
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
        if (this.showWave) {
            this.animateWave();
        }
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
            const duration = this.duration;

            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                // Ease out cubic
                const easedProgress = 1 - Math.pow(1 - progress, 3);
                
                this.currentValue = startValue + (targetValue - startValue) * easedProgress;

                // Trigger pulse at milestones
                if (this.pulseAnimation) {
                    this.checkMilestones(this.currentValue);
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

        animateWave() {
            const animate = () => {
                this.waveOffset += 0.05;
                this.waveAnimationFrame = requestAnimationFrame(animate);
            };
            
            this.waveAnimationFrame = requestAnimationFrame(animate);
        },

        checkMilestones(value) {
            const milestones = [25, 50, 75, 100];
            const currentMilestone = milestones.find(m => 
                Math.abs(value - m) < 1 && value < m + 1
            );

            if (currentMilestone) {
                this.triggerPulse();
            }
        },

        triggerPulse() {
            this.pulseScale = 1.1;
            setTimeout(() => {
                this.pulseScale = 1;
            }, 300);
        },

        celebrate() {
            this.showCelebration = true;
            this.createRingParticles();
            this.triggerPulse();

            setTimeout(() => {
                this.showCelebration = false;
                this.particles = [];
            }, 3000);
        },

        createRingParticles() {
            const particleCount = 40;
            const colors = [...this.gradientColors, '#FFD93D', '#FF6B6B', '#96CEB4'];
            
            for (let i = 0; i < particleCount; i++) {
                const angle = (Math.PI * 2 * i) / particleCount;
                const randomAngle = angle + (Math.random() - 0.5) * 0.5;
                const velocity = 2 + Math.random() * 3;
                const size = 3 + Math.random() * 3;
                
                // Start particles from the ring
                const startX = Math.cos(angle) * this.radius;
                const startY = Math.sin(angle) * this.radius;
                
                this.particles.push({
                    id: Date.now() + i,
                    x: startX,
                    y: startY,
                    vx: Math.cos(randomAngle) * velocity,
                    vy: Math.sin(randomAngle) * velocity,
                    size: size,
                    color: colors[Math.floor(Math.random() * colors.length)],
                    opacity: 1,
                    rotation: Math.random() * 360
                });
            }

            this.animateParticles();
        },

        animateParticles() {
            const animate = () => {
                this.particles = this.particles.map(particle => ({
                    ...particle,
                    x: particle.x + particle.vx,
                    y: particle.y + particle.vy,
                    vx: particle.vx * 0.98, // friction
                    vy: particle.vy * 0.98 + 0.1, // friction + gravity
                    opacity: particle.opacity - 0.015,
                    rotation: particle.rotation + particle.vx * 3
                })).filter(particle => particle.opacity > 0);

                if (this.particles.length > 0) {
                    requestAnimationFrame(animate);
                }
            };

            requestAnimationFrame(animate);
        }
    }
});