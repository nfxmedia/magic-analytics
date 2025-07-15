import template from './nfx-morphing-number.html.twig';
import './nfx-morphing-number.scss';

const { Component } = Shopware;

Component.register('nfx-morphing-number', {
    template,

    props: {
        value: {
            type: Number,
            required: true
        },
        duration: {
            type: Number,
            default: 1500
        },
        digits: {
            type: Number,
            default: 6
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
        morphStyle: {
            type: String,
            default: 'slide',
            validator: (value) => ['slide', 'flip', 'fade'].includes(value)
        }
    },

    data() {
        return {
            currentValue: 0,
            digitElements: [],
            isAnimating: false
        };
    },

    computed: {
        formattedValue() {
            const value = Math.floor(this.currentValue);
            const valueStr = value.toString().padStart(this.digits, '0');
            return this.addSeparators(valueStr);
        },

        digitArray() {
            const value = Math.floor(this.currentValue);
            const valueStr = value.toString().padStart(this.digits, '0');
            return valueStr.split('').map(Number);
        }
    },

    watch: {
        value(newVal) {
            this.animateToValue(newVal);
        }
    },

    mounted() {
        this.animateToValue(this.value);
    },

    methods: {
        animateToValue(targetValue) {
            if (this.isAnimating) return;
            
            this.isAnimating = true;
            const startValue = this.currentValue;
            const startTime = performance.now();
            const duration = this.duration;

            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                // Ease out cubic
                const easedProgress = 1 - Math.pow(1 - progress, 3);
                
                this.currentValue = startValue + (targetValue - startValue) * easedProgress;

                // Trigger digit morphing at specific intervals
                if (this.morphStyle !== 'fade') {
                    this.triggerDigitMorph(progress);
                }

                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    this.currentValue = targetValue;
                    this.isAnimating = false;
                }
            };

            requestAnimationFrame(animate);
        },

        triggerDigitMorph(progress) {
            const milestones = [0.2, 0.4, 0.6, 0.8];
            const threshold = 0.05;
            
            for (const milestone of milestones) {
                if (Math.abs(progress - milestone) < threshold) {
                    this.morphDigits();
                    break;
                }
            }
        },

        morphDigits() {
            const digitElements = this.$refs.digitContainer?.querySelectorAll('.digit');
            if (!digitElements) return;

            digitElements.forEach((element, index) => {
                const delay = index * 50;
                
                setTimeout(() => {
                    element.style.transform = this.getMorphTransform();
                    
                    setTimeout(() => {
                        element.style.transform = 'none';
                    }, 200);
                }, delay);
            });
        },

        getMorphTransform() {
            switch (this.morphStyle) {
                case 'slide':
                    return 'translateY(-20px)';
                case 'flip':
                    return 'rotateX(180deg)';
                case 'fade':
                    return 'scale(0.8)';
                default:
                    return 'translateY(-20px)';
            }
        },

        addSeparators(str) {
            // Add thousand separators
            return str.replace(/\B(?=(\d{3})+(?!\d))/g, this.separator);
        },

        getDigitStyle(digit, index) {
            const baseDelay = index * 0.1;
            
            return {
                '--animation-delay': `${baseDelay}s`,
                '--digit-color': this.getDigitColor(digit),
                transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)'
            };
        },

        getDigitColor(digit) {
            // Color code digits for visual interest
            const colors = [
                '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57',
                '#FF9FF3', '#54A0FF', '#5F27CD', '#00D2D3', '#FF9F43'
            ];
            return colors[digit] || '#333';
        }
    }
});