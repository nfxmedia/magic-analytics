import template from './nfx-theme-switcher-advanced.html.twig';
import './nfx-theme-switcher-advanced.scss';

const { Component } = Shopware;

Component.register('nfx-theme-switcher-advanced', {
    template,

    data() {
        return {
            currentTheme: 'light',
            isTransitioning: false,
            autoSwitchEnabled: false,
            autoSwitchTimes: {
                darkStart: '18:00',
                lightStart: '06:00'
            },
            morphProgress: 0,
            ripplePosition: { x: 0, y: 0 },
            showRipple: false,
            themes: {
                light: {
                    name: 'Light',
                    icon: 'regular-sun',
                    colors: {
                        '--nfx-primary': '#0066ff',
                        '--nfx-primary-rgb': '0, 102, 255',
                        '--nfx-background': '#ffffff',
                        '--nfx-surface': '#f8f9fa',
                        '--nfx-surface-alt': '#e9ecef',
                        '--nfx-text-primary': '#212529',
                        '--nfx-text-secondary': '#6c757d',
                        '--nfx-text-muted': '#adb5bd',
                        '--nfx-border': '#dee2e6',
                        '--nfx-shadow': 'rgba(0, 0, 0, 0.08)',
                        '--nfx-hover': 'rgba(0, 102, 255, 0.1)',
                        '--nfx-success': '#28a745',
                        '--nfx-warning': '#ffc107',
                        '--nfx-danger': '#dc3545',
                        '--nfx-info': '#17a2b8'
                    }
                },
                dark: {
                    name: 'Dark',
                    icon: 'regular-moon',
                    colors: {
                        '--nfx-primary': '#4da6ff',
                        '--nfx-primary-rgb': '77, 166, 255',
                        '--nfx-background': '#0d1117',
                        '--nfx-surface': '#161b22',
                        '--nfx-surface-alt': '#21262d',
                        '--nfx-text-primary': '#f0f6fc',
                        '--nfx-text-secondary': '#8b949e',
                        '--nfx-text-muted': '#484f58',
                        '--nfx-border': '#30363d',
                        '--nfx-shadow': 'rgba(0, 0, 0, 0.3)',
                        '--nfx-hover': 'rgba(77, 166, 255, 0.15)',
                        '--nfx-success': '#3fb950',
                        '--nfx-warning': '#d29922',
                        '--nfx-danger': '#f85149',
                        '--nfx-info': '#58a6ff'
                    }
                },
                auto: {
                    name: 'Auto',
                    icon: 'regular-clock',
                    description: 'Automatically switch based on time'
                }
            }
        };
    },

    created() {
        this.loadThemePreference();
        this.checkAutoSwitch();
        
        // Check auto-switch every minute
        this.autoSwitchInterval = setInterval(() => {
            if (this.autoSwitchEnabled) {
                this.checkAutoSwitch();
            }
        }, 60000);

        // Add global CSS transition variable
        this.addGlobalStyles();
    },

    beforeDestroy() {
        if (this.autoSwitchInterval) {
            clearInterval(this.autoSwitchInterval);
        }
        this.removeGlobalStyles();
    },

    methods: {
        loadThemePreference() {
            const savedTheme = localStorage.getItem('nfx-theme') || 'light';
            const savedAutoSwitch = localStorage.getItem('nfx-auto-switch') === 'true';
            const savedTimes = localStorage.getItem('nfx-auto-switch-times');
            
            this.currentTheme = savedTheme;
            this.autoSwitchEnabled = savedAutoSwitch;
            
            if (savedTimes) {
                try {
                    this.autoSwitchTimes = JSON.parse(savedTimes);
                } catch (e) {
                    console.error('Failed to parse auto-switch times');
                }
            }
            
            this.applyTheme(this.currentTheme, false);
        },

        async switchTheme(theme, event) {
            if (this.isTransitioning || theme === this.currentTheme) return;

            this.isTransitioning = true;
            
            // Get click position for ripple effect
            if (event) {
                const rect = event.currentTarget.getBoundingClientRect();
                this.ripplePosition = {
                    x: event.clientX,
                    y: event.clientY
                };
            } else {
                // Center of screen for auto-switch
                this.ripplePosition = {
                    x: window.innerWidth / 2,
                    y: window.innerHeight / 2
                };
            }

            // Start ripple animation
            this.showRipple = true;
            await this.morphTransition(theme);
            
            this.currentTheme = theme;
            this.applyTheme(theme);
            this.saveThemePreference();
            
            // Progressive reveal animation
            await this.progressiveReveal();
            
            setTimeout(() => {
                this.isTransitioning = false;
                this.showRipple = false;
            }, 300);
        },

        async morphTransition(newTheme) {
            const duration = 800;
            const startTime = performance.now();
            
            return new Promise(resolve => {
                const animate = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    
                    // Easing function for smooth animation
                    const eased = this.easeInOutCubic(progress);
                    this.morphProgress = eased;
                    
                    // Apply morphing effect
                    this.applyMorphEffect(eased, newTheme);
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        resolve();
                    }
                };
                
                requestAnimationFrame(animate);
            });
        },

        applyMorphEffect(progress, newTheme) {
            const root = document.documentElement;
            const oldColors = this.themes[this.currentTheme].colors;
            const newColors = this.themes[newTheme].colors;
            
            // Interpolate between old and new colors
            Object.keys(newColors).forEach(property => {
                if (property.includes('rgb')) return; // Skip RGB values
                
                const oldColor = oldColors[property];
                const newColor = newColors[property];
                
                if (oldColor && newColor && oldColor.startsWith('#') && newColor.startsWith('#')) {
                    const interpolated = this.interpolateColor(oldColor, newColor, progress);
                    root.style.setProperty(property, interpolated);
                }
            });
            
            // Apply morphing distortion effect
            const distortion = Math.sin(progress * Math.PI) * 5;
            root.style.setProperty('--nfx-morph-distortion', `${distortion}px`);
            
            // Apply ripple scale
            const rippleScale = progress * 3;
            root.style.setProperty('--nfx-ripple-scale', rippleScale);
        },

        interpolateColor(color1, color2, progress) {
            // Convert hex to RGB
            const rgb1 = this.hexToRgb(color1);
            const rgb2 = this.hexToRgb(color2);
            
            if (!rgb1 || !rgb2) return color1;
            
            // Interpolate each channel
            const r = Math.round(rgb1.r + (rgb2.r - rgb1.r) * progress);
            const g = Math.round(rgb1.g + (rgb2.g - rgb1.g) * progress);
            const b = Math.round(rgb1.b + (rgb2.b - rgb1.b) * progress);
            
            return `rgb(${r}, ${g}, ${b})`;
        },

        hexToRgb(hex) {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        },

        async progressiveReveal() {
            const elements = document.querySelectorAll('.nfx-analytics-content > *');
            const stagger = 50;
            
            // Hide all elements
            elements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px) scale(0.95)';
            });
            
            // Progressively reveal
            for (let i = 0; i < elements.length; i++) {
                await this.delay(stagger);
                elements[i].style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                elements[i].style.opacity = '1';
                elements[i].style.transform = 'translateY(0) scale(1)';
            }
        },

        applyTheme(theme, animate = true) {
            const root = document.documentElement;
            const themeColors = this.themes[theme].colors;
            
            if (animate) {
                root.style.setProperty('--nfx-theme-transition', 'all 0.3s ease');
            } else {
                root.style.setProperty('--nfx-theme-transition', 'none');
            }
            
            // Apply theme colors
            Object.entries(themeColors).forEach(([property, value]) => {
                root.style.setProperty(property, value);
            });
            
            // Set theme attribute for additional styling
            root.setAttribute('data-nfx-theme', theme);
            
            // Update Shopware admin theme if possible
            this.updateShopwareTheme(theme);
        },

        updateShopwareTheme(theme) {
            // Try to integrate with Shopware's theme system
            const shopwareRoot = document.querySelector('.sw-admin');
            if (shopwareRoot) {
                shopwareRoot.classList.toggle('nfx-dark-theme', theme === 'dark');
                shopwareRoot.classList.toggle('nfx-light-theme', theme === 'light');
            }
        },

        saveThemePreference() {
            localStorage.setItem('nfx-theme', this.currentTheme);
            localStorage.setItem('nfx-auto-switch', this.autoSwitchEnabled);
            localStorage.setItem('nfx-auto-switch-times', JSON.stringify(this.autoSwitchTimes));
        },

        toggleAutoSwitch() {
            this.autoSwitchEnabled = !this.autoSwitchEnabled;
            this.saveThemePreference();
            
            if (this.autoSwitchEnabled) {
                this.checkAutoSwitch();
            }
        },

        checkAutoSwitch() {
            if (!this.autoSwitchEnabled) return;
            
            const now = new Date();
            const currentTime = now.getHours() * 60 + now.getMinutes();
            
            const darkStart = this.timeToMinutes(this.autoSwitchTimes.darkStart);
            const lightStart = this.timeToMinutes(this.autoSwitchTimes.lightStart);
            
            let shouldBeDark = false;
            
            if (darkStart > lightStart) {
                // Dark period crosses midnight
                shouldBeDark = currentTime >= darkStart || currentTime < lightStart;
            } else {
                // Normal case
                shouldBeDark = currentTime >= darkStart && currentTime < lightStart;
            }
            
            const targetTheme = shouldBeDark ? 'dark' : 'light';
            if (this.currentTheme !== targetTheme) {
                this.switchTheme(targetTheme);
            }
        },

        timeToMinutes(timeStr) {
            const [hours, minutes] = timeStr.split(':').map(Number);
            return hours * 60 + minutes;
        },

        addGlobalStyles() {
            const style = document.createElement('style');
            style.id = 'nfx-theme-transitions';
            style.textContent = `
                :root {
                    --nfx-theme-transition: all 0.3s ease;
                    --nfx-morph-distortion: 0px;
                    --nfx-ripple-scale: 0;
                }
                
                * {
                    transition: var(--nfx-theme-transition);
                }
                
                .nfx-theme-ripple {
                    position: fixed;
                    border-radius: 50%;
                    background: var(--nfx-primary);
                    opacity: 0.3;
                    transform: scale(var(--nfx-ripple-scale));
                    pointer-events: none;
                    z-index: 9999;
                }
                
                .nfx-morph-active {
                    filter: blur(var(--nfx-morph-distortion));
                }
            `;
            document.head.appendChild(style);
        },

        removeGlobalStyles() {
            const style = document.getElementById('nfx-theme-transitions');
            if (style) {
                style.remove();
            }
        },

        easeInOutCubic(t) {
            return t < 0.5 
                ? 4 * t * t * t 
                : (t - 1) * (2 * t - 2) * (2 * t - 2) + 1;
        },

        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    }
});