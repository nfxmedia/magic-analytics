import template from './nfx-theme-switcher.html.twig';
import './nfx-theme-switcher.scss';

Shopware.Component.register('nfx-theme-switcher', {
    template,

    data() {
        return {
            showMenu: false,
            currentTheme: 'light-apple',
            themes: [
                {
                    key: 'light-apple',
                    label: 'Light Apple',
                    icon: '☀️',
                    description: 'Clean and bright design'
                },
                {
                    key: 'dark-violet',
                    label: 'Dark Violet',
                    icon: '🌙',
                    description: 'Dark theme with purple accents'
                },
                {
                    key: 'pastel',
                    label: 'Pastel Colors',
                    icon: '🌸',
                    description: 'Soft and gentle colors'
                },
                {
                    key: 'retro-90s',
                    label: '90s Retro',
                    icon: '🕶️',
                    description: 'Neon and retro vibes'
                }
            ]
        };
    },

    computed: {
        currentThemeData() {
            return this.themes.find(theme => theme.key === this.currentTheme);
        }
    },

    mounted() {
        // Load saved theme from localStorage
        const savedTheme = localStorage.getItem('nfx-analytics-theme');
        if (savedTheme && this.themes.find(t => t.key === savedTheme)) {
            this.currentTheme = savedTheme;
        }
        
        this.applyTheme(this.currentTheme);
        
        // Close menu when clicking outside
        document.addEventListener('click', this.handleOutsideClick);
    },

    beforeDestroy() {
        document.removeEventListener('click', this.handleOutsideClick);
    },

    methods: {
        toggleMenu() {
            this.showMenu = !this.showMenu;
        },

        selectTheme(themeKey) {
            this.currentTheme = themeKey;
            this.applyTheme(themeKey);
            this.showMenu = false;
            
            // Save to localStorage
            localStorage.setItem('nfx-analytics-theme', themeKey);
        },

        applyTheme(themeKey) {
            const body = document.body;
            
            // Remove all theme classes
            this.themes.forEach(theme => {
                body.classList.remove(`theme-${theme.key}`);
            });
            
            // Add new theme class
            body.classList.add(`theme-${themeKey}`);
            
            // Add main analytics class if not present
            if (!body.classList.contains('nfx-analytics')) {
                body.classList.add('nfx-analytics');
            }
        },

        handleOutsideClick(event) {
            if (!this.$el.contains(event.target)) {
                this.showMenu = false;
            }
        }
    }
});