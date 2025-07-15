import template from './nfx-glass-navigation.html.twig';
import './../../scss/_glass-navigation.scss';

const { Component } = Shopware;

Component.register('nfx-glass-navigation', {
    template,

    props: {
        items: {
            type: Array,
            required: true,
            default: () => []
        },
        activeItem: {
            type: String,
            default: null
        }
    },

    data() {
        return {
            mouseX: 0,
            mouseY: 0,
            magneticCursorX: 0,
            magneticCursorY: 0,
            isMagneticActive: false,
            parallaxOffset: 0,
            particles: Array.from({ length: 20 }, (_, i) => i),
            cardRefs: new Map(),
            animationFrame: null
        };
    },

    computed: {
        navigationItems() {
            return this.items.map(item => ({
                ...item,
                isActive: item.id === this.activeItem
            }));
        }
    },

    mounted() {
        this.initializeGlassEffects();
        this.setupEventListeners();
        this.startParallaxAnimation();
    },

    beforeDestroy() {
        this.cleanup();
    },

    methods: {
        initializeGlassEffects() {
            // Initialize IntersectionObserver for scroll-triggered animations
            this.observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('in-view');
                        }
                    });
                },
                { threshold: 0.1 }
            );

            // Observe all cards
            this.$nextTick(() => {
                const cards = this.$el.querySelectorAll('.nfx-glass-navigation__card');
                cards.forEach((card, index) => {
                    this.observer.observe(card);
                    // Store card references for magnetic effects
                    this.cardRefs.set(index, card);
                });
            });
        },

        setupEventListeners() {
            // Mouse move for 3D effects and magnetic cursor
            this.$el.addEventListener('mousemove', this.handleMouseMove);
            this.$el.addEventListener('mouseleave', this.handleMouseLeave);
            
            // Scroll for parallax
            const content = this.$el.querySelector('.nfx-glass-navigation__content');
            if (content) {
                content.addEventListener('scroll', this.handleScroll);
            }

            // Window resize for responsive adjustments
            window.addEventListener('resize', this.handleResize);
        },

        handleMouseMove(event) {
            const rect = this.$el.getBoundingClientRect();
            this.mouseX = event.clientX - rect.left;
            this.mouseY = event.clientY - rect.top;

            // Update magnetic cursor position
            this.updateMagneticCursor(event);

            // Apply 3D transforms to cards
            this.apply3DEffects(event);
        },

        handleMouseLeave() {
            this.isMagneticActive = false;
            this.reset3DEffects();
        },

        updateMagneticCursor(event) {
            const rect = this.$el.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;

            // Smooth magnetic cursor movement
            this.magneticCursorX += (x - this.magneticCursorX) * 0.15;
            this.magneticCursorY += (y - this.magneticCursorY) * 0.15;

            const cursor = this.$el.querySelector('.nfx-glass-navigation__magnetic-cursor');
            if (cursor) {
                cursor.style.left = `${this.magneticCursorX}px`;
                cursor.style.top = `${this.magneticCursorY}px`;
                
                // Check if cursor is over a card
                const card = event.target.closest('.nfx-glass-navigation__card');
                this.isMagneticActive = !!card;
                cursor.classList.toggle('nfx-glass-navigation__magnetic-cursor--active', this.isMagneticActive);
            }
        },

        apply3DEffects(event) {
            this.cardRefs.forEach((card) => {
                const rect = card.getBoundingClientRect();
                const cardCenterX = rect.left + rect.width / 2;
                const cardCenterY = rect.top + rect.height / 2;
                
                // Calculate distance from mouse to card center
                const deltaX = event.clientX - cardCenterX;
                const deltaY = event.clientY - cardCenterY;
                const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
                
                // Apply effect only if mouse is close enough
                const maxDistance = 200;
                if (distance < maxDistance) {
                    const intensity = 1 - (distance / maxDistance);
                    
                    // Calculate rotation based on mouse position
                    const rotateY = (deltaX / rect.width) * 20 * intensity;
                    const rotateX = -(deltaY / rect.height) * 20 * intensity;
                    
                    // Apply custom properties for CSS
                    card.style.setProperty('--rotate-x', `${rotateX}deg`);
                    card.style.setProperty('--rotate-y', `${rotateY}deg`);
                    
                    // Mouse position for glow effect
                    const mouseXPercent = ((event.clientX - rect.left) / rect.width) * 100;
                    const mouseYPercent = ((event.clientY - rect.top) / rect.height) * 100;
                    card.style.setProperty('--mouse-x', `${mouseXPercent}%`);
                    card.style.setProperty('--mouse-y', `${mouseYPercent}%`);
                    
                    // Magnetic pull effect
                    if (distance < 100 && this.isMagneticActive) {
                        const pullStrength = (1 - distance / 100) * 10;
                        const translateX = (deltaX / distance) * pullStrength;
                        const translateY = (deltaY / distance) * pullStrength;
                        card.style.transform = `
                            translateZ(var(--card-depth))
                            translateX(${translateX}px)
                            translateY(${translateY}px)
                            rotateX(${rotateX}deg)
                            rotateY(${rotateY}deg)
                            scale(1.02)
                        `;
                    }
                } else {
                    this.resetCardEffects(card);
                }
            });
        },

        reset3DEffects() {
            this.cardRefs.forEach((card) => {
                this.resetCardEffects(card);
            });
        },

        resetCardEffects(card) {
            card.style.setProperty('--rotate-x', '0deg');
            card.style.setProperty('--rotate-y', '0deg');
            card.style.setProperty('--mouse-x', '50%');
            card.style.setProperty('--mouse-y', '50%');
            card.style.transform = '';
        },

        handleScroll(event) {
            const scrollTop = event.target.scrollTop;
            this.parallaxOffset = scrollTop * 0.5;
            
            // Apply parallax to sections
            this.applyParallaxEffect(scrollTop);
        },

        applyParallaxEffect(scrollTop) {
            const sections = this.$el.querySelectorAll('.nfx-glass-navigation__section');
            sections.forEach((section, index) => {
                const depth = section.classList.contains('nfx-glass-navigation__section--depth-1') ? 0.8
                    : section.classList.contains('nfx-glass-navigation__section--depth-2') ? 0.6
                    : section.classList.contains('nfx-glass-navigation__section--depth-3') ? 0.4
                    : 1;
                
                const offset = scrollTop * (1 - depth);
                section.style.transform = `translateY(${-offset}px) translateZ(${depth * 30}px)`;
            });
        },

        startParallaxAnimation() {
            const animate = () => {
                // Floating particles animation
                const particles = this.$el.querySelectorAll('.particle');
                particles.forEach((particle, index) => {
                    const time = Date.now() / 1000;
                    const offset = index * 0.5;
                    const x = Math.sin(time * 0.3 + offset) * 10;
                    const y = Math.cos(time * 0.2 + offset) * 10;
                    particle.style.transform = `translate(${x}px, ${y}px)`;
                });
                
                this.animationFrame = requestAnimationFrame(animate);
            };
            
            animate();
        },

        handleResize() {
            // Recalculate positions on resize
            this.reset3DEffects();
        },

        onCardClick(item) {
            // Ripple effect on click
            this.createRipple(event, item);
            
            // Emit navigation event
            this.$emit('navigate', item);
        },

        createRipple(event, item) {
            const card = event.currentTarget;
            const rect = card.getBoundingClientRect();
            const ripple = document.createElement('span');
            const size = Math.max(rect.width, rect.height);
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            card.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        },

        cleanup() {
            // Remove event listeners
            this.$el.removeEventListener('mousemove', this.handleMouseMove);
            this.$el.removeEventListener('mouseleave', this.handleMouseLeave);
            window.removeEventListener('resize', this.handleResize);
            
            const content = this.$el.querySelector('.nfx-glass-navigation__content');
            if (content) {
                content.removeEventListener('scroll', this.handleScroll);
            }
            
            // Cancel animation frame
            if (this.animationFrame) {
                cancelAnimationFrame(this.animationFrame);
            }
            
            // Disconnect observer
            if (this.observer) {
                this.observer.disconnect();
            }
        }
    }
});