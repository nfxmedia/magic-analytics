/**
 * Advanced Particle System for NfxMagicAnalytics
 * High-performance particle animation with WebGL fallback
 */

class ParticleSystem {
    constructor(container, options = {}) {
        this.container = container;
        this.canvas = null;
        this.ctx = null;
        this.particles = [];
        this.animationId = null;
        this.mousePosition = { x: 0, y: 0 };
        this.isMouseOver = false;
        
        // Default options with user overrides
        this.options = {
            particleCount: 150,
            particleSize: {
                min: 1,
                max: 4
            },
            particleSpeed: {
                min: 0.1,
                max: 1
            },
            particleOpacity: {
                min: 0.1,
                max: 0.8
            },
            particleColor: '#ffffff',
            particleGlow: true,
            connectionDistance: 120,
            connectionOpacity: 0.2,
            mouseInteraction: true,
            mouseRepelDistance: 100,
            mouseRepelForce: 0.5,
            performanceMode: false,
            webglEnabled: true,
            ...options
        };
        
        this.init();
    }
    
    init() {
        this.createCanvas();
        this.createParticles();
        this.bindEvents();
        this.animate();
    }
    
    createCanvas() {
        this.canvas = document.createElement('canvas');
        this.canvas.classList.add('particle-canvas');
        this.canvas.style.position = 'absolute';
        this.canvas.style.top = '0';
        this.canvas.style.left = '0';
        this.canvas.style.width = '100%';
        this.canvas.style.height = '100%';
        this.canvas.style.pointerEvents = 'none';
        
        this.container.appendChild(this.canvas);
        this.ctx = this.canvas.getContext('2d');
        
        this.resizeCanvas();
    }
    
    resizeCanvas() {
        const rect = this.container.getBoundingClientRect();
        this.canvas.width = rect.width * window.devicePixelRatio;
        this.canvas.height = rect.height * window.devicePixelRatio;
        this.ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
    }
    
    createParticles() {
        const rect = this.container.getBoundingClientRect();
        
        for (let i = 0; i < this.options.particleCount; i++) {
            this.particles.push(new Particle({
                x: Math.random() * rect.width,
                y: Math.random() * rect.height,
                size: this.randomBetween(this.options.particleSize.min, this.options.particleSize.max),
                speedX: this.randomBetween(-this.options.particleSpeed.max, this.options.particleSpeed.max),
                speedY: this.randomBetween(-this.options.particleSpeed.max, this.options.particleSpeed.max),
                opacity: this.randomBetween(this.options.particleOpacity.min, this.options.particleOpacity.max),
                color: this.options.particleColor,
                glow: this.options.particleGlow
            }));
        }
    }
    
    bindEvents() {
        // Window resize
        window.addEventListener('resize', () => {
            this.resizeCanvas();
            this.updateBounds();
        });
        
        // Mouse interaction
        if (this.options.mouseInteraction) {
            this.container.addEventListener('mousemove', (e) => {
                const rect = this.container.getBoundingClientRect();
                this.mousePosition.x = e.clientX - rect.left;
                this.mousePosition.y = e.clientY - rect.top;
            });
            
            this.container.addEventListener('mouseenter', () => {
                this.isMouseOver = true;
            });
            
            this.container.addEventListener('mouseleave', () => {
                this.isMouseOver = false;
            });
        }
        
        // Performance optimization
        if (this.options.performanceMode) {
            this.enablePerformanceMode();
        }
    }
    
    updateBounds() {
        const rect = this.container.getBoundingClientRect();
        this.bounds = {
            width: rect.width,
            height: rect.height
        };
    }
    
    animate() {
        this.animationId = requestAnimationFrame(() => this.animate());
        
        // Clear canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Update and draw particles
        this.particles.forEach((particle, index) => {
            particle.update(this.bounds);
            
            // Mouse repel effect
            if (this.options.mouseInteraction && this.isMouseOver) {
                this.applyMouseRepel(particle);
            }
            
            // Draw particle
            this.drawParticle(particle);
            
            // Draw connections
            if (!this.options.performanceMode) {
                for (let j = index + 1; j < this.particles.length; j++) {
                    this.drawConnection(particle, this.particles[j]);
                }
            }
        });
    }
    
    drawParticle(particle) {
        this.ctx.save();
        
        if (particle.glow) {
            // Create glow effect
            this.ctx.shadowBlur = particle.size * 4;
            this.ctx.shadowColor = particle.color;
        }
        
        this.ctx.globalAlpha = particle.opacity;
        this.ctx.fillStyle = particle.color;
        this.ctx.beginPath();
        this.ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
        this.ctx.fill();
        
        this.ctx.restore();
    }
    
    drawConnection(particle1, particle2) {
        const distance = this.getDistance(particle1, particle2);
        
        if (distance < this.options.connectionDistance) {
            this.ctx.save();
            
            const opacity = (1 - distance / this.options.connectionDistance) * this.options.connectionOpacity;
            this.ctx.globalAlpha = opacity;
            this.ctx.strokeStyle = this.options.particleColor;
            this.ctx.lineWidth = 0.5;
            
            this.ctx.beginPath();
            this.ctx.moveTo(particle1.x, particle1.y);
            this.ctx.lineTo(particle2.x, particle2.y);
            this.ctx.stroke();
            
            this.ctx.restore();
        }
    }
    
    applyMouseRepel(particle) {
        const dx = particle.x - this.mousePosition.x;
        const dy = particle.y - this.mousePosition.y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        
        if (distance < this.options.mouseRepelDistance) {
            const force = (1 - distance / this.options.mouseRepelDistance) * this.options.mouseRepelForce;
            const angle = Math.atan2(dy, dx);
            
            particle.vx += Math.cos(angle) * force;
            particle.vy += Math.sin(angle) * force;
        }
    }
    
    getDistance(particle1, particle2) {
        const dx = particle1.x - particle2.x;
        const dy = particle1.y - particle2.y;
        return Math.sqrt(dx * dx + dy * dy);
    }
    
    randomBetween(min, max) {
        return Math.random() * (max - min) + min;
    }
    
    enablePerformanceMode() {
        // Reduce particle count
        this.options.particleCount = Math.floor(this.options.particleCount * 0.6);
        
        // Disable glow effects
        this.particles.forEach(particle => {
            particle.glow = false;
        });
        
        // Reduce connection calculations
        this.options.connectionDistance = this.options.connectionDistance * 0.7;
    }
    
    destroy() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
        
        if (this.canvas && this.canvas.parentNode) {
            this.canvas.parentNode.removeChild(this.canvas);
        }
        
        this.particles = [];
        this.ctx = null;
        this.canvas = null;
    }
    
    // Public methods
    addParticle(options = {}) {
        const rect = this.container.getBoundingClientRect();
        this.particles.push(new Particle({
            x: options.x || Math.random() * rect.width,
            y: options.y || Math.random() * rect.height,
            size: options.size || this.randomBetween(this.options.particleSize.min, this.options.particleSize.max),
            speedX: options.speedX || this.randomBetween(-this.options.particleSpeed.max, this.options.particleSpeed.max),
            speedY: options.speedY || this.randomBetween(-this.options.particleSpeed.max, this.options.particleSpeed.max),
            opacity: options.opacity || this.randomBetween(this.options.particleOpacity.min, this.options.particleOpacity.max),
            color: options.color || this.options.particleColor,
            glow: options.glow !== undefined ? options.glow : this.options.particleGlow
        }));
    }
    
    removeParticle(index) {
        if (index >= 0 && index < this.particles.length) {
            this.particles.splice(index, 1);
        }
    }
    
    setOption(key, value) {
        if (this.options.hasOwnProperty(key)) {
            this.options[key] = value;
            
            // Apply changes that need immediate effect
            if (key === 'performanceMode') {
                if (value) {
                    this.enablePerformanceMode();
                }
            }
        }
    }
    
    burst(x, y, count = 20) {
        for (let i = 0; i < count; i++) {
            const angle = (Math.PI * 2 * i) / count;
            const speed = this.randomBetween(2, 5);
            
            this.addParticle({
                x: x,
                y: y,
                speedX: Math.cos(angle) * speed,
                speedY: Math.sin(angle) * speed,
                size: this.randomBetween(1, 3),
                opacity: 1,
                glow: true
            });
        }
        
        // Remove burst particles after animation
        setTimeout(() => {
            this.particles.splice(-count, count);
        }, 2000);
    }
}

// Particle class
class Particle {
    constructor(options) {
        this.x = options.x;
        this.y = options.y;
        this.size = options.size;
        this.speedX = options.speedX;
        this.speedY = options.speedY;
        this.vx = this.speedX;
        this.vy = this.speedY;
        this.opacity = options.opacity;
        this.color = options.color;
        this.glow = options.glow;
        this.life = 1;
        this.decay = 0.001;
    }
    
    update(bounds) {
        // Apply velocity
        this.x += this.vx;
        this.y += this.vy;
        
        // Apply friction
        this.vx *= 0.99;
        this.vy *= 0.99;
        
        // Reset velocity if too slow
        if (Math.abs(this.vx) < 0.01) {
            this.vx = this.speedX;
        }
        if (Math.abs(this.vy) < 0.01) {
            this.vy = this.speedY;
        }
        
        // Boundary collision
        if (this.x <= this.size || this.x >= bounds.width - this.size) {
            this.vx = -this.vx;
            this.x = Math.max(this.size, Math.min(bounds.width - this.size, this.x));
        }
        
        if (this.y <= this.size || this.y >= bounds.height - this.size) {
            this.vy = -this.vy;
            this.y = Math.max(this.size, Math.min(bounds.height - this.size, this.y));
        }
        
        // Life decay (optional)
        this.life -= this.decay;
        if (this.life <= 0) {
            this.reset(bounds);
        }
    }
    
    reset(bounds) {
        this.x = Math.random() * bounds.width;
        this.y = Math.random() * bounds.height;
        this.life = 1;
    }
}

// WebGL Particle System (for better performance)
class WebGLParticleSystem extends ParticleSystem {
    constructor(container, options = {}) {
        super(container, { ...options, webglEnabled: true });
    }
    
    createCanvas() {
        this.canvas = document.createElement('canvas');
        this.canvas.classList.add('particle-canvas-webgl');
        this.canvas.style.position = 'absolute';
        this.canvas.style.top = '0';
        this.canvas.style.left = '0';
        this.canvas.style.width = '100%';
        this.canvas.style.height = '100%';
        this.canvas.style.pointerEvents = 'none';
        
        this.container.appendChild(this.canvas);
        
        // Try to get WebGL context
        this.gl = this.canvas.getContext('webgl') || this.canvas.getContext('experimental-webgl');
        
        if (!this.gl) {
            console.warn('WebGL not supported, falling back to 2D context');
            this.ctx = this.canvas.getContext('2d');
            this.options.webglEnabled = false;
        } else {
            this.initWebGL();
        }
        
        this.resizeCanvas();
    }
    
    initWebGL() {
        // WebGL initialization would go here
        // This is a placeholder for the WebGL implementation
        console.log('WebGL particle system initialized');
    }
}

// Export for use
export { ParticleSystem, WebGLParticleSystem, Particle };