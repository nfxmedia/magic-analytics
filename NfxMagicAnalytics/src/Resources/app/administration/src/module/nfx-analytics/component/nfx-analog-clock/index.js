import template from './nfx-analog-clock.html.twig';
import './nfx-analog-clock.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('nfx-analog-clock', {
    template,

    inject: ['systemConfigApiService'],

    data() {
        return {
            currentTime: new Date(),
            selectedTimezone: 'local',
            showTimezoneSelector: false,
            clickCount: 0,
            lastClickTime: 0,
            isExpanded: false,
            digitalFormat: '24h',
            showWorldClocks: false,
            timezones: [
                { id: 'local', name: 'Local Time', offset: 0 },
                { id: 'utc', name: 'UTC', offset: 0 },
                { id: 'est', name: 'New York (EST)', offset: -5 },
                { id: 'pst', name: 'Los Angeles (PST)', offset: -8 },
                { id: 'cet', name: 'Berlin (CET)', offset: 1 },
                { id: 'jst', name: 'Tokyo (JST)', offset: 9 },
                { id: 'aest', name: 'Sydney (AEST)', offset: 10 },
                { id: 'ist', name: 'Mumbai (IST)', offset: 5.5 }
            ],
            worldClocks: [],
            glowIntensity: 1,
            theme: 'glass',
            showDateCalendar: false,
            alarmTime: null,
            showAlarm: false,
            pendulumAngle: 0,
            pendulumDirection: 1
        };
    },

    computed: {
        adjustedTime() {
            const time = new Date(this.currentTime);
            if (this.selectedTimezone !== 'local') {
                const timezone = this.timezones.find(tz => tz.id === this.selectedTimezone);
                if (timezone) {
                    const localOffset = time.getTimezoneOffset() / 60;
                    const targetOffset = timezone.offset;
                    const diff = targetOffset - (-localOffset);
                    time.setHours(time.getHours() + diff);
                }
            }
            return time;
        },

        hours() {
            return this.adjustedTime.getHours() % 12 || 12;
        },

        minutes() {
            return this.adjustedTime.getMinutes();
        },

        seconds() {
            return this.adjustedTime.getSeconds();
        },

        milliseconds() {
            return this.adjustedTime.getMilliseconds();
        },

        hourDegrees() {
            return ((this.hours % 12) * 30) + (this.minutes * 0.5) + (this.seconds * 0.00833);
        },

        minuteDegrees() {
            return (this.minutes * 6) + (this.seconds * 0.1);
        },

        secondDegrees() {
            return (this.seconds * 6) + (this.milliseconds * 0.006);
        },

        digitalTime() {
            const h = this.adjustedTime.getHours();
            const m = this.adjustedTime.getMinutes();
            const s = this.adjustedTime.getSeconds();
            
            if (this.digitalFormat === '12h') {
                const hour = h % 12 || 12;
                const ampm = h >= 12 ? 'PM' : 'AM';
                return `${hour.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')} ${ampm}`;
            }
            
            return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        },

        dateString() {
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            return this.adjustedTime.toLocaleDateString('en-US', options);
        },

        clockClasses() {
            return {
                'nfx-analog-clock--expanded': this.isExpanded,
                'nfx-analog-clock--glow': this.glowIntensity > 0,
                [`nfx-analog-clock--theme-${this.theme}`]: true,
                'nfx-analog-clock--world-clocks': this.showWorldClocks
            };
        },

        currentTimezoneName() {
            const tz = this.timezones.find(t => t.id === this.selectedTimezone);
            return tz ? tz.name : 'Local Time';
        }
    },

    created() {
        this.updateClock();
        this.updateWorldClocks();
    },

    mounted() {
        this.startClock();
        this.startPendulum();
        window.addEventListener('visibilitychange', this.handleVisibilityChange);
    },

    beforeDestroy() {
        this.stopClock();
        this.stopPendulum();
        window.removeEventListener('visibilitychange', this.handleVisibilityChange);
    },

    methods: {
        startClock() {
            this.clockInterval = setInterval(() => {
                this.updateClock();
            }, 50); // Update every 50ms for smooth second hand
        },

        stopClock() {
            if (this.clockInterval) {
                clearInterval(this.clockInterval);
            }
        },

        updateClock() {
            this.currentTime = new Date();
            
            // Check alarm
            if (this.alarmTime && this.showAlarm) {
                const alarmHours = parseInt(this.alarmTime.split(':')[0]);
                const alarmMinutes = parseInt(this.alarmTime.split(':')[1]);
                
                if (this.adjustedTime.getHours() === alarmHours && 
                    this.adjustedTime.getMinutes() === alarmMinutes &&
                    this.adjustedTime.getSeconds() === 0) {
                    this.triggerAlarm();
                }
            }
        },

        startPendulum() {
            this.pendulumInterval = setInterval(() => {
                this.pendulumAngle += this.pendulumDirection * 2;
                if (Math.abs(this.pendulumAngle) >= 30) {
                    this.pendulumDirection *= -1;
                }
            }, 100);
        },

        stopPendulum() {
            if (this.pendulumInterval) {
                clearInterval(this.pendulumInterval);
            }
        },

        handleClockClick() {
            const now = Date.now();
            
            // Double-click detection
            if (now - this.lastClickTime < 300) {
                this.clickCount++;
            } else {
                this.clickCount = 1;
            }
            
            this.lastClickTime = now;
            
            // Easter egg: Triple click reveals advanced features
            if (this.clickCount >= 3) {
                this.toggleAdvancedMode();
                this.clickCount = 0;
            }
        },

        toggleAdvancedMode() {
            this.isExpanded = !this.isExpanded;
            
            if (this.isExpanded) {
                this.$emit('notification', {
                    title: 'Advanced Clock Mode',
                    message: 'Welcome to the time dimension! 🕰️',
                    variant: 'info'
                });
            }
        },

        selectTimezone(timezoneId) {
            this.selectedTimezone = timezoneId;
            this.showTimezoneSelector = false;
            this.updateWorldClocks();
        },

        toggleTimezoneSelector() {
            this.showTimezoneSelector = !this.showTimezoneSelector;
        },

        toggleDigitalFormat() {
            this.digitalFormat = this.digitalFormat === '24h' ? '12h' : '24h';
        },

        updateWorldClocks() {
            if (!this.showWorldClocks) return;
            
            this.worldClocks = this.timezones
                .filter(tz => tz.id !== this.selectedTimezone)
                .map(tz => {
                    const time = new Date();
                    const localOffset = time.getTimezoneOffset() / 60;
                    const targetOffset = tz.offset;
                    const diff = targetOffset - (-localOffset);
                    time.setHours(time.getHours() + diff);
                    
                    return {
                        ...tz,
                        time: time.toLocaleTimeString('en-US', { 
                            hour: '2-digit', 
                            minute: '2-digit',
                            hour12: false 
                        })
                    };
                });
        },

        toggleWorldClocks() {
            this.showWorldClocks = !this.showWorldClocks;
            if (this.showWorldClocks) {
                this.updateWorldClocks();
                this.worldClockInterval = setInterval(() => {
                    this.updateWorldClocks();
                }, 1000);
            } else if (this.worldClockInterval) {
                clearInterval(this.worldClockInterval);
            }
        },

        changeTheme() {
            const themes = ['glass', 'neon', 'vintage', 'minimal', 'cyberpunk'];
            const currentIndex = themes.indexOf(this.theme);
            this.theme = themes[(currentIndex + 1) % themes.length];
        },

        adjustGlow(value) {
            this.glowIntensity = value;
        },

        toggleDateCalendar() {
            this.showDateCalendar = !this.showDateCalendar;
        },

        setAlarm() {
            this.showAlarm = true;
            const h = this.adjustedTime.getHours();
            const m = this.adjustedTime.getMinutes();
            this.alarmTime = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
        },

        triggerAlarm() {
            // Visual alarm effect
            this.glowIntensity = 3;
            setTimeout(() => {
                this.glowIntensity = 1;
            }, 2000);
            
            this.$emit('notification', {
                title: 'Alarm!',
                message: `It's ${this.alarmTime}! Time to check those analytics! ⏰`,
                variant: 'warning'
            });
            
            this.showAlarm = false;
        },

        handleVisibilityChange() {
            if (document.hidden) {
                this.stopClock();
                this.stopPendulum();
            } else {
                this.startClock();
                this.startPendulum();
            }
        },

        exportTime() {
            const data = {
                currentTime: this.adjustedTime.toISOString(),
                timezone: this.currentTimezoneName,
                format: this.digitalFormat,
                theme: this.theme
            };
            
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `time-snapshot-${Date.now()}.json`;
            a.click();
            URL.revokeObjectURL(url);
        }
    }
});