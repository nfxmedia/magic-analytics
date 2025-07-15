import template from './nfx-particles-bg.html.twig';
import './nfx-particles-bg.scss';
import ParticleSystem, { particleThemes } from '../../js/particles-advanced';

const { Component } = Shopware;

Component.register('nfx-particles-bg', {
    template,

    props: {
        theme: {
            type: String,
            required: false,
            default: 'default',
            validator(value) {
                return Object.keys(particleThemes).includes(value) || value === 'custom';
            }
        },
        customConfig: {
            type: Object,
            required: false,
            default: () => ({})
        },
        interactive: {
            type: Boolean,
            required: false,
            default: true
        },
        performanceMode: {
            type: String,
            required: false,
            default: 'balanced',
            validator(value) {
                return ['low', 'balanced', 'high'].includes(value);
            }
        }
    },

    data() {
        return {
            particleSystem: null,
            showConfig: false,
            currentConfig: {},
            availableThemes: Object.keys(particleThemes),
            particleTypes: ['circle', 'triangle', 'square', 'star']
        };
    },

    computed: {
        canvasId() {
            return `particle-canvas-${this._uid}`;
        },

        effectiveConfig() {
            const baseConfig = this.theme === 'custom' 
                ? this.customConfig 
                : particleThemes[this.theme] || particleThemes.default;

            // Apply performance settings
            const performanceOverrides = this.getPerformanceConfig();
            
            // Apply interactivity settings
            const interactiveOverrides = this.interactive ? {} : {
                mouse: { attract: false, repulse: false }
            };

            return {
                ...baseConfig,
                ...performanceOverrides,
                ...interactiveOverrides
            };
        }
    },

    mounted() {
        this.initParticles();
        this.bindKeyboardShortcuts();
    },

    beforeDestroy() {
        if (this.particleSystem) {
            this.particleSystem.destroy();
        }
        this.unbindKeyboardShortcuts();
    },

    methods: {
        initParticles() {
            const canvas = this.$refs.canvas;
            if (!canvas) return;

            this.particleSystem = new ParticleSystem(canvas, this.effectiveConfig);
            this.currentConfig = { ...this.effectiveConfig };
        },

        getPerformanceConfig() {
            switch (this.performanceMode) {
                case 'low':
                    return {
                        particleCount: 50,
                        connections: { enabled: false },
                        performance: { retina: false, adaptiveFps: true }
                    };
                case 'high':
                    return {
                        particleCount: 200,
                        performance: { retina: true, adaptiveFps: false, fps: 60 }
                    };
                case 'balanced':
                default:
                    return {
                        particleCount: 100,
                        performance: { retina: true, adaptiveFps: true, fps: 60 }
                    };
            }
        },

        updateConfig() {
            if (!this.particleSystem) return;
            
            this.particleSystem.setConfig(this.currentConfig);
            this.$emit('config-changed', this.currentConfig);
        },

        changeTheme(themeName) {
            if (!particleThemes[themeName]) return;
            
            this.currentConfig = { ...particleThemes[themeName] };
            this.updateConfig();
            this.$emit('theme-changed', themeName);
        },

        toggleConnections() {
            this.currentConfig.connections.enabled = !this.currentConfig.connections.enabled;
            this.updateConfig();
        },

        toggleMouseMode() {
            const current = this.currentConfig.mouse;
            if (current.repulse) {
                current.repulse = false;
                current.attract = true;
            } else if (current.attract) {
                current.attract = false;
                current.repulse = false;
            } else {
                current.repulse = true;
                current.attract = false;
            }
            this.updateConfig();
        },

        addParticles(count = 10) {
            if (!this.particleSystem) return;
            this.particleSystem.addParticles(count);
        },

        removeParticles(count = 10) {
            if (!this.particleSystem) return;
            this.particleSystem.removeParticles(count);
        },

        toggleParticleType(type) {
            const index = this.currentConfig.activeTypes.indexOf(type);
            if (index > -1) {
                this.currentConfig.activeTypes.splice(index, 1);
            } else {
                this.currentConfig.activeTypes.push(type);
            }
            
            if (this.currentConfig.activeTypes.length === 0) {
                this.currentConfig.activeTypes = ['circle'];
            }
            
            this.updateConfig();
        },

        bindKeyboardShortcuts() {
            this.keyboardHandler = (e) => {
                // Only handle shortcuts when config panel is open
                if (!this.showConfig) return;
                
                switch(e.key) {
                    case 'c':
                        this.toggleConnections();
                        break;
                    case 'm':
                        this.toggleMouseMode();
                        break;
                    case '+':
                        this.addParticles();
                        break;
                    case '-':
                        this.removeParticles();
                        break;
                }
            };
            
            window.addEventListener('keydown', this.keyboardHandler);
        },

        unbindKeyboardShortcuts() {
            window.removeEventListener('keydown', this.keyboardHandler);
        },

        exportConfig() {
            const configString = JSON.stringify(this.currentConfig, null, 2);
            const blob = new Blob([configString], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'particle-config.json';
            a.click();
            URL.revokeObjectURL(url);
        },

        async importConfig(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            try {
                const text = await file.text();
                const config = JSON.parse(text);
                this.currentConfig = config;
                this.updateConfig();
                this.$emit('config-imported', config);
            } catch (error) {
                console.error('Failed to import config:', error);
            }
        }
    },

    watch: {
        theme(newTheme) {
            if (newTheme !== 'custom') {
                this.changeTheme(newTheme);
            }
        },

        customConfig: {
            deep: true,
            handler(newConfig) {
                if (this.theme === 'custom') {
                    this.currentConfig = { ...newConfig };
                    this.updateConfig();
                }
            }
        },

        performanceMode() {
            this.updateConfig();
        }
    }
});