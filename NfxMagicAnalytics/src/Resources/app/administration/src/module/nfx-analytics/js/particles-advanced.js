/**
 * Advanced Particle System for NfxMagicAnalytics
 * Features: Network animations, mouse interactions, multiple particle types, performance optimized
 */

class ParticleSystem {
    constructor(canvas, options = {}) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.particles = [];
        this.mouse = { x: null, y: null, radius: 150 };
        this.animationId = null;
        this.lastTime = 0;
        this.fps = 0;
        
        // Default configuration
        this.config = {
            particleCount: 100,
            particleTypes: ['circle', 'triangle', 'square', 'star'],
            activeTypes: ['circle'],
            speed: 0.5,
            size: { min: 2, max: 4 },
            color: '#ffffff',
            opacity: 0.8,
            connections: {
                enabled: true,
                distance: 120,
                opacity: 0.4,
                width: 1,
                color: '#ffffff'
            },
            mouse: {
                attract: false,
                repulse: true,
                radius: 150,
                force: 0.05
            },
            background: {
                color: '#000000',
                opacity: 1
            },
            performance: {
                retina: true,
                fps: 60,
                adaptiveFps: true
            },
            ...options
        };
        
        this.init();
    }
    
    init() {
        this.setupCanvas();
        this.createParticles();
        this.bindEvents();
        this.animate();
    }
    
    setupCanvas() {
        const rect = this.canvas.getBoundingClientRect();
        const dpr = this.config.performance.retina ? window.devicePixelRatio || 1 : 1;
        
        this.canvas.width = rect.width * dpr;
        this.canvas.height = rect.height * dpr;
        this.ctx.scale(dpr, dpr);
        
        this.canvas.style.width = rect.width + 'px';
        this.canvas.style.height = rect.height + 'px';
    }
    
    createParticles() {
        this.particles = [];
        const rect = this.canvas.getBoundingClientRect();
        
        for (let i = 0; i < this.config.particleCount; i++) {
            const type = this.config.activeTypes[Math.floor(Math.random() * this.config.activeTypes.length)];
            const size = this.random(this.config.size.min, this.config.size.max);
            
            this.particles.push({
                id: i,
                type: type,
                x: Math.random() * rect.width,
                y: Math.random() * rect.height,
                vx: (Math.random() - 0.5) * this.config.speed,
                vy: (Math.random() - 0.5) * this.config.speed,
                size: size,
                opacity: this.config.opacity,
                color: this.config.color,
                angle: Math.random() * Math.PI * 2,
                rotationSpeed: (Math.random() - 0.5) * 0.02
            });
        }
    }
    
    bindEvents() {
        // Mouse events
        this.canvas.addEventListener('mousemove', (e) => {
            const rect = this.canvas.getBoundingClientRect();
            this.mouse.x = e.clientX - rect.left;
            this.mouse.y = e.clientY - rect.top;
        });
        
        this.canvas.addEventListener('mouseleave', () => {
            this.mouse.x = null;
            this.mouse.y = null;
        });
        
        // Window resize
        window.addEventListener('resize', () => {
            this.setupCanvas();
            this.createParticles();
        });
    }
    
    animate(currentTime = 0) {
        this.animationId = requestAnimationFrame((time) => this.animate(time));
        
        // FPS calculation and adaptive performance
        const deltaTime = currentTime - this.lastTime;
        if (deltaTime < 1000 / this.config.performance.fps) return;
        
        this.fps = 1000 / deltaTime;
        this.lastTime = currentTime;
        
        // Adaptive particle count based on FPS
        if (this.config.performance.adaptiveFps && this.fps < 30 && this.particles.length > 50) {
            this.particles.splice(-10); // Remove 10 particles
        }
        
        this.update();
        this.draw();
    }
    
    update() {
        const rect = this.canvas.getBoundingClientRect();
        
        this.particles.forEach(particle => {
            // Basic movement
            particle.x += particle.vx;
            particle.y += particle.vy;
            
            // Rotation for non-circle particles
            if (particle.type !== 'circle') {
                particle.angle += particle.rotationSpeed;
            }
            
            // Mouse interaction
            if (this.mouse.x !== null && this.mouse.y !== null) {
                const dx = this.mouse.x - particle.x;
                const dy = this.mouse.y - particle.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < this.config.mouse.radius) {
                    const force = (1 - distance / this.config.mouse.radius) * this.config.mouse.force;
                    const angle = Math.atan2(dy, dx);
                    
                    if (this.config.mouse.repulse) {
                        particle.vx -= Math.cos(angle) * force;
                        particle.vy -= Math.sin(angle) * force;
                    } else if (this.config.mouse.attract) {
                        particle.vx += Math.cos(angle) * force;
                        particle.vy += Math.sin(angle) * force;
                    }
                }
            }
            
            // Boundary check
            if (particle.x < 0 || particle.x > rect.width) particle.vx *= -1;
            if (particle.y < 0 || particle.y > rect.height) particle.vy *= -1;
            
            // Keep particles in bounds
            particle.x = Math.max(0, Math.min(rect.width, particle.x));
            particle.y = Math.max(0, Math.min(rect.height, particle.y));
            
            // Speed limit
            const speed = Math.sqrt(particle.vx * particle.vx + particle.vy * particle.vy);
            if (speed > this.config.speed * 2) {
                particle.vx = (particle.vx / speed) * this.config.speed * 2;
                particle.vy = (particle.vy / speed) * this.config.speed * 2;
            }
        });
    }
    
    draw() {
        const rect = this.canvas.getBoundingClientRect();
        
        // Clear canvas
        this.ctx.fillStyle = this.config.background.color;
        this.ctx.globalAlpha = this.config.background.opacity;
        this.ctx.fillRect(0, 0, rect.width, rect.height);
        
        // Draw connections
        if (this.config.connections.enabled) {
            this.drawConnections();
        }
        
        // Draw particles
        this.particles.forEach(particle => {
            this.drawParticle(particle);
        });
        
        // Draw FPS counter (debug)
        if (this.config.performance.showFps) {
            this.ctx.fillStyle = '#00ff00';
            this.ctx.font = '12px monospace';
            this.ctx.fillText(`FPS: ${Math.round(this.fps)}`, 10, 20);
        }
    }
    
    drawConnections() {
        this.ctx.strokeStyle = this.config.connections.color;
        this.ctx.lineWidth = this.config.connections.width;
        
        for (let i = 0; i < this.particles.length; i++) {
            for (let j = i + 1; j < this.particles.length; j++) {
                const dx = this.particles[i].x - this.particles[j].x;
                const dy = this.particles[i].y - this.particles[j].y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < this.config.connections.distance) {
                    const opacity = (1 - distance / this.config.connections.distance) * this.config.connections.opacity;
                    this.ctx.globalAlpha = opacity;
                    
                    this.ctx.beginPath();
                    this.ctx.moveTo(this.particles[i].x, this.particles[i].y);
                    this.ctx.lineTo(this.particles[j].x, this.particles[j].y);
                    this.ctx.stroke();
                }
            }
        }
    }
    
    drawParticle(particle) {
        this.ctx.fillStyle = particle.color;
        this.ctx.globalAlpha = particle.opacity;
        
        this.ctx.save();
        this.ctx.translate(particle.x, particle.y);
        this.ctx.rotate(particle.angle);
        
        switch (particle.type) {
            case 'circle':
                this.drawCircle(0, 0, particle.size);
                break;
            case 'triangle':
                this.drawTriangle(0, 0, particle.size);
                break;
            case 'square':
                this.drawSquare(0, 0, particle.size);
                break;
            case 'star':
                this.drawStar(0, 0, particle.size);
                break;
            default:
                this.drawCircle(0, 0, particle.size);
        }
        
        this.ctx.restore();
    }
    
    drawCircle(x, y, radius) {
        this.ctx.beginPath();
        this.ctx.arc(x, y, radius, 0, Math.PI * 2);
        this.ctx.fill();
    }
    
    drawTriangle(x, y, size) {
        this.ctx.beginPath();
        this.ctx.moveTo(x, y - size);
        this.ctx.lineTo(x - size, y + size);
        this.ctx.lineTo(x + size, y + size);
        this.ctx.closePath();
        this.ctx.fill();
    }
    
    drawSquare(x, y, size) {
        this.ctx.fillRect(x - size, y - size, size * 2, size * 2);
    }
    
    drawStar(x, y, size) {
        const spikes = 5;
        const outerRadius = size;
        const innerRadius = size / 2;
        
        this.ctx.beginPath();
        for (let i = 0; i < spikes * 2; i++) {
            const radius = i % 2 === 0 ? outerRadius : innerRadius;
            const angle = (i / (spikes * 2)) * Math.PI * 2 - Math.PI / 2;
            const px = x + Math.cos(angle) * radius;
            const py = y + Math.sin(angle) * radius;
            
            if (i === 0) {
                this.ctx.moveTo(px, py);
            } else {
                this.ctx.lineTo(px, py);
            }
        }
        this.ctx.closePath();
        this.ctx.fill();
    }
    
    // Utility methods
    random(min, max) {
        return Math.random() * (max - min) + min;
    }
    
    // Public API
    setConfig(newConfig) {
        this.config = { ...this.config, ...newConfig };
        this.createParticles();
    }
    
    addParticles(count) {
        const rect = this.canvas.getBoundingClientRect();
        for (let i = 0; i < count; i++) {
            const type = this.config.activeTypes[Math.floor(Math.random() * this.config.activeTypes.length)];
            const size = this.random(this.config.size.min, this.config.size.max);
            
            this.particles.push({
                id: this.particles.length,
                type: type,
                x: Math.random() * rect.width,
                y: Math.random() * rect.height,
                vx: (Math.random() - 0.5) * this.config.speed,
                vy: (Math.random() - 0.5) * this.config.speed,
                size: size,
                opacity: this.config.opacity,
                color: this.config.color,
                angle: Math.random() * Math.PI * 2,
                rotationSpeed: (Math.random() - 0.5) * 0.02
            });
        }
    }
    
    removeParticles(count) {
        this.particles.splice(-count);
    }
    
    destroy() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
        this.particles = [];
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    }
}

// Preset themes
export const particleThemes = {
    default: {
        particleCount: 100,
        activeTypes: ['circle'],
        color: '#ffffff',
        connections: { enabled: true, color: '#ffffff' },
        background: { color: '#000000' }
    },
    cosmic: {
        particleCount: 150,
        activeTypes: ['circle', 'star'],
        color: '#00ffff',
        connections: { enabled: true, color: '#00ffff', distance: 150 },
        background: { color: '#000033' },
        mouse: { repulse: false, attract: true }
    },
    matrix: {
        particleCount: 200,
        activeTypes: ['square'],
        color: '#00ff00',
        connections: { enabled: true, color: '#00ff00', opacity: 0.2 },
        background: { color: '#000000' },
        speed: 1
    },
    geometric: {
        particleCount: 80,
        activeTypes: ['triangle', 'square'],
        color: '#ff6b6b',
        connections: { enabled: true, color: '#ff6b6b', width: 2 },
        background: { color: '#1a1a1a' },
        size: { min: 4, max: 8 }
    },
    minimal: {
        particleCount: 50,
        activeTypes: ['circle'],
        color: '#333333',
        connections: { enabled: false },
        background: { color: '#ffffff' },
        speed: 0.3,
        size: { min: 1, max: 3 }
    },
    neon: {
        particleCount: 120,
        activeTypes: ['circle', 'triangle'],
        color: '#ff00ff',
        connections: { enabled: true, color: '#ff00ff', opacity: 0.6 },
        background: { color: '#0a0a0a' },
        mouse: { repulse: true, radius: 200, force: 0.08 }
    }
};

export default ParticleSystem;