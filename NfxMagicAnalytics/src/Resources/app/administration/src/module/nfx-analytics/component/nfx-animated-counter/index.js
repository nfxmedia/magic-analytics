import template from './nfx-animated-counter.html.twig';
import './nfx-animated-counter.scss';

const { Component } = Shopware;

Component.register('nfx-animated-counter', {
    template,

    props: {
        startValue: {
            type: Number,
            default: 0
        },
        endValue: {
            type: Number,
            required: true
        },
        duration: {
            type: Number,
            default: 2000
        },
        decimals: {
            type: Number,
            default: 0
        },
        separator: {
            type: String,
            default: ','
        },
        prefix: {
            type: String,
            default: ''
        },
        suffix: {
            type: String,
            default: ''
        },
        easingFunction: {
            type: String,
            default: 'easeOutCubic'
        },
        celebrateOnComplete: {
            type: Boolean,
            default: true
        },
        morphAnimation: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            displayValue: this.startValue,
            particles: [],
            showCelebration: false,
            morphScale: 1,
            morphRotation: 0
        };
    },

    computed: {
        formattedValue() {
            const value = this.displayValue.toFixed(this.decimals);
            const parts = value.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.separator);
            return this.prefix + parts.join('.') + this.suffix;
        },

        morphStyle() {
            if (!this.morphAnimation) return {};
            
            return {
                transform: `scale(${this.morphScale}) rotate(${this.morphRotation}deg)`,
                transition: 'transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55)'
            };
        }
    },

    watch: {
        endValue(newVal) {
            this.animateValue(this.displayValue, newVal);
        }
    },

    mounted() {
        this.animateValue(this.startValue, this.endValue);
    },

    methods: {
        animateValue(start, end) {
            const startTime = performance.now();
            const duration = this.duration;

            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                // Apply easing function
                const easedProgress = this.getEasedProgress(progress);
                
                // Calculate current value
                this.displayValue = start + (end - start) * easedProgress;

                // Trigger morph animation at key points
                if (this.morphAnimation) {
                    this.triggerMorph(progress);
                }

                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    this.displayValue = end;
                    if (this.celebrateOnComplete) {
                        this.celebrate();
                    }
                }
            };

            requestAnimationFrame(animate);
        },

        getEasedProgress(progress) {
            const easingFunctions = {
                linear: (t) => t,
                easeOutCubic: (t) => 1 - Math.pow(1 - t, 3),
                easeInOutCubic: (t) => t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2,
                easeOutElastic: (t) => {
                    const c4 = (2 * Math.PI) / 3;
                    return t === 0 ? 0 : t === 1 ? 1 : Math.pow(2, -10 * t) * Math.sin((t * 10 - 0.75) * c4) + 1;
                },
                easeOutBounce: (t) => {
                    const n1 = 7.5625;
                    const d1 = 2.75;
                    if (t < 1 / d1) {
                        return n1 * t * t;
                    } else if (t < 2 / d1) {
                        return n1 * (t -= 1.5 / d1) * t + 0.75;
                    } else if (t < 2.5 / d1) {
                        return n1 * (t -= 2.25 / d1) * t + 0.9375;
                    } else {
                        return n1 * (t -= 2.625 / d1) * t + 0.984375;
                    }
                }
            };

            return easingFunctions[this.easingFunction]?.(progress) || easingFunctions.easeOutCubic(progress);
        },

        triggerMorph(progress) {
            // Create pulsing effect at 25%, 50%, and 75% progress
            const milestones = [0.25, 0.5, 0.75];
            const threshold = 0.02;
            
            for (const milestone of milestones) {
                if (Math.abs(progress - milestone) < threshold) {
                    this.morphScale = 1.2;
                    this.morphRotation = Math.random() * 10 - 5;
                    
                    setTimeout(() => {
                        this.morphScale = 1;
                        this.morphRotation = 0;
                    }, 300);
                    break;
                }
            }
        },

        celebrate() {
            this.showCelebration = true;
            this.createParticles();
            
            // Pulse animation
            this.morphScale = 1.3;
            setTimeout(() => {
                this.morphScale = 1;
            }, 400);

            // Clear celebration after animation
            setTimeout(() => {
                this.showCelebration = false;
                this.particles = [];
            }, 3000);
        },

        createParticles() {
            const particleCount = 30;
            const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57', '#FF9FF3'];
            
            for (let i = 0; i < particleCount; i++) {
                const angle = (Math.PI * 2 * i) / particleCount;
                const velocity = 3 + Math.random() * 3;
                const size = 4 + Math.random() * 4;
                
                this.particles.push({
                    id: Date.now() + i,
                    x: 0,
                    y: 0,
                    vx: Math.cos(angle) * velocity,
                    vy: Math.sin(angle) * velocity,
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
                    vy: particle.vy + 0.2, // gravity
                    opacity: particle.opacity - 0.02,
                    rotation: particle.rotation + particle.vx * 2
                })).filter(particle => particle.opacity > 0);

                if (this.particles.length > 0) {
                    requestAnimationFrame(animate);
                }
            };

            requestAnimationFrame(animate);
        }
    }
});