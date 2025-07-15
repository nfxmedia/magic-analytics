/**
 * Advanced Gradient Mesh System for NfxMagicAnalytics
 * Dynamic gradient meshes with morphing shapes and liquid effects
 */

class GradientMeshSystem {
    constructor(container, options = {}) {
        this.container = container;
        this.meshContainer = null;
        this.meshes = [];
        this.orbs = [];
        this.waves = [];
        this.liquidBlobs = [];
        this.animationFrame = null;
        this.time = 0;
        
        // Configuration options
        this.options = {
            meshCount: 3,
            orbCount: 5,
            waveCount: 3,
            liquidBlobCount: 6,
            colors: {
                primary: ['#667eea', '#764ba2'],
                secondary: ['#f093fb', '#f5576c'],
                tertiary: ['#4facfe', '#00f2fe'],
                orbs: ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe', '#fa709a', '#fee140']
            },
            animation: {
                meshSpeed: 0.0005,
                orbSpeed: 0.001,
                waveSpeed: 0.002,
                liquidSpeed: 0.0008
            },
            blur: {
                mesh: 40,
                orb: 60,
                liquid: 30
            },
            physics: {
                gravity: 0.00001,
                friction: 0.99,
                bounce: 0.8,
                attraction: 0.0001
            },
            interactive: true,
            performanceMode: false,
            ...options
        };
        
        this.init();
    }
    
    init() {
        this.createContainer();
        this.createSVGFilters();
        this.createMeshes();
        this.createOrbs();
        this.createWaves();
        this.createLiquidEffect();
        this.bindEvents();
        this.animate();
    }
    
    createContainer() {
        // Main background container
        this.backgroundContainer = document.createElement('div');
        this.backgroundContainer.className = 'nfx-animated-background';
        
        // Gradient mesh container
        this.meshContainer = document.createElement('div');
        this.meshContainer.className = 'gradient-mesh-container';
        
        // Physics orbs container
        this.orbsContainer = document.createElement('div');
        this.orbsContainer.className = 'physics-orbs-container';
        
        // Wave container
        this.waveContainer = document.createElement('div');
        this.waveContainer.className = 'wave-container';
        
        // Liquid effect container
        this.liquidContainer = document.createElement('div');
        this.liquidContainer.className = 'liquid-effect';
        
        // Assemble containers
        this.backgroundContainer.appendChild(this.meshContainer);
        this.backgroundContainer.appendChild(this.orbsContainer);
        this.backgroundContainer.appendChild(this.waveContainer);
        this.backgroundContainer.appendChild(this.liquidContainer);
        
        this.container.appendChild(this.backgroundContainer);
    }
    
    createSVGFilters() {
        const svgNS = 'http://www.w3.org/2000/svg';
        const filtersContainer = document.createElement('div');
        filtersContainer.className = 'svg-filters';
        
        const svg = document.createElementNS(svgNS, 'svg');
        svg.style.display = 'none';
        
        // Liquid filter
        const liquidFilter = document.createElementNS(svgNS, 'filter');
        liquidFilter.setAttribute('id', 'liquid-filter');
        
        const turbulence = document.createElementNS(svgNS, 'feTurbulence');
        turbulence.setAttribute('type', 'fractalNoise');
        turbulence.setAttribute('baseFrequency', '0.015');
        turbulence.setAttribute('numOctaves', '2');
        turbulence.setAttribute('result', 'turbulence');
        
        const colorMatrix = document.createElementNS(svgNS, 'feColorMatrix');
        colorMatrix.setAttribute('in', 'turbulence');
        colorMatrix.setAttribute('type', 'saturate');
        colorMatrix.setAttribute('values', '0');
        colorMatrix.setAttribute('result', 'desaturated');
        
        const gaussian = document.createElementNS(svgNS, 'feGaussianBlur');
        gaussian.setAttribute('in', 'SourceGraphic');
        gaussian.setAttribute('stdDeviation', '10');
        gaussian.setAttribute('result', 'blur');
        
        const displacement = document.createElementNS(svgNS, 'feDisplacementMap');
        displacement.setAttribute('in', 'blur');
        displacement.setAttribute('in2', 'desaturated');
        displacement.setAttribute('scale', '20');
        displacement.setAttribute('xChannelSelector', 'R');
        displacement.setAttribute('yChannelSelector', 'G');
        
        liquidFilter.appendChild(turbulence);
        liquidFilter.appendChild(colorMatrix);
        liquidFilter.appendChild(gaussian);
        liquidFilter.appendChild(displacement);
        
        svg.appendChild(liquidFilter);
        filtersContainer.appendChild(svg);
        this.container.appendChild(filtersContainer);
    }
    
    createMeshes() {
        // Create gradient mesh element
        const meshElement = document.createElement('div');
        meshElement.className = 'gradient-mesh';
        this.meshContainer.appendChild(meshElement);
        
        // Create additional gradient orbs
        for (let i = 0; i < this.options.orbCount; i++) {
            const orb = document.createElement('div');
            orb.className = 'gradient-orb';
            this.meshContainer.appendChild(orb);
            
            this.meshes.push({
                element: orb,
                x: Math.random() * 100,
                y: Math.random() * 100,
                size: Math.random() * 400 + 200,
                speedX: (Math.random() - 0.5) * 0.1,
                speedY: (Math.random() - 0.5) * 0.1,
                color1: this.getRandomColor(),
                color2: this.getRandomColor()
            });
        }
    }
    
    createOrbs() {
        for (let i = 0; i < this.options.orbCount; i++) {
            const orb = document.createElement('div');
            orb.className = 'physics-orb';
            this.orbsContainer.appendChild(orb);
            
            const orbData = {
                element: orb,
                x: Math.random() * window.innerWidth,
                y: Math.random() * window.innerHeight,
                vx: (Math.random() - 0.5) * 2,
                vy: (Math.random() - 0.5) * 2,
                size: Math.random() * 80 + 40,
                mass: 1,
                color: this.options.colors.orbs[i % this.options.colors.orbs.length]
            };
            
            orb.style.width = orbData.size + 'px';
            orb.style.height = orbData.size + 'px';
            
            this.orbs.push(orbData);
        }
    }
    
    createWaves() {
        for (let i = 0; i < this.options.waveCount; i++) {
            const wave = document.createElement('div');
            wave.className = `wave wave-${i + 1}`;
            this.waveContainer.appendChild(wave);
            
            this.waves.push({
                element: wave,
                offset: i * 20,
                amplitude: 50 + i * 10,
                frequency: 0.02 - i * 0.005,
                speed: this.options.animation.waveSpeed * (1 - i * 0.2)
            });
        }
    }
    
    createLiquidEffect() {
        for (let i = 0; i < this.options.liquidBlobCount; i++) {
            const blob = document.createElement('div');
            blob.className = 'liquid-blob';
            this.liquidContainer.appendChild(blob);
            
            this.liquidBlobs.push({
                element: blob,
                x: Math.random() * 100,
                y: Math.random() * 100,
                size: Math.random() * 200 + 100,
                speedX: (Math.random() - 0.5) * 0.05,
                speedY: (Math.random() - 0.5) * 0.05,
                morphSpeed: Math.random() * 0.001 + 0.0005,
                morphOffset: Math.random() * Math.PI * 2
            });
        }
    }
    
    bindEvents() {
        // Window resize
        window.addEventListener('resize', () => this.handleResize());
        
        // Mouse interaction
        if (this.options.interactive) {
            this.mousePosition = { x: 0, y: 0 };
            
            document.addEventListener('mousemove', (e) => {
                this.mousePosition.x = e.clientX;
                this.mousePosition.y = e.clientY;
            });
            
            // Click interaction - create ripple effect
            this.container.addEventListener('click', (e) => {
                this.createRipple(e.clientX, e.clientY);
            });
        }
        
        // Performance monitoring
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.resume();
                    } else {
                        this.pause();
                    }
                });
            });
            
            observer.observe(this.container);
        }
    }
    
    animate() {
        this.animationFrame = requestAnimationFrame(() => this.animate());
        this.time += 1;
        
        // Update meshes
        this.updateMeshes();
        
        // Update physics orbs
        this.updateOrbs();
        
        // Update waves
        this.updateWaves();
        
        // Update liquid blobs
        this.updateLiquidBlobs();
    }
    
    updateMeshes() {
        this.meshes.forEach((mesh, index) => {
            // Update position
            mesh.x += mesh.speedX;
            mesh.y += mesh.speedY;
            
            // Boundary check
            if (mesh.x < -20 || mesh.x > 120) mesh.speedX *= -1;
            if (mesh.y < -20 || mesh.y > 120) mesh.speedY *= -1;
            
            // Apply position
            mesh.element.style.transform = `translate(${mesh.x}%, ${mesh.y}%)`;
            
            // Morph animation
            const morphFactor = Math.sin(this.time * this.options.animation.meshSpeed + index) * 0.2 + 1;
            mesh.element.style.width = mesh.size * morphFactor + 'px';
            mesh.element.style.height = mesh.size * morphFactor + 'px';
        });
    }
    
    updateOrbs() {
        this.orbs.forEach((orb, index) => {
            // Apply physics
            if (!this.options.performanceMode) {
                // Attraction to other orbs
                this.orbs.forEach((otherOrb, otherIndex) => {
                    if (index !== otherIndex) {
                        const dx = otherOrb.x - orb.x;
                        const dy = otherOrb.y - orb.y;
                        const distance = Math.sqrt(dx * dx + dy * dy);
                        
                        if (distance < 200 && distance > orb.size + otherOrb.size) {
                            const force = this.options.physics.attraction * (200 - distance);
                            orb.vx += (dx / distance) * force;
                            orb.vy += (dy / distance) * force;
                        }
                    }
                });
            }
            
            // Mouse interaction
            if (this.options.interactive && this.mousePosition) {
                const dx = this.mousePosition.x - orb.x;
                const dy = this.mousePosition.y - orb.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < 150) {
                    const force = (150 - distance) * 0.001;
                    orb.vx -= (dx / distance) * force;
                    orb.vy -= (dy / distance) * force;
                }
            }
            
            // Apply gravity
            orb.vy += this.options.physics.gravity;
            
            // Apply friction
            orb.vx *= this.options.physics.friction;
            orb.vy *= this.options.physics.friction;
            
            // Update position
            orb.x += orb.vx;
            orb.y += orb.vy;
            
            // Boundary collision
            if (orb.x - orb.size / 2 < 0 || orb.x + orb.size / 2 > window.innerWidth) {
                orb.vx *= -this.options.physics.bounce;
                orb.x = Math.max(orb.size / 2, Math.min(window.innerWidth - orb.size / 2, orb.x));
            }
            
            if (orb.y - orb.size / 2 < 0 || orb.y + orb.size / 2 > window.innerHeight) {
                orb.vy *= -this.options.physics.bounce;
                orb.y = Math.max(orb.size / 2, Math.min(window.innerHeight - orb.size / 2, orb.y));
            }
            
            // Apply transform
            orb.element.style.transform = `translate(${orb.x - orb.size / 2}px, ${orb.y - orb.size / 2}px)`;
            
            // Pulse effect
            const scale = 1 + Math.sin(this.time * 0.01 + index) * 0.05;
            orb.element.style.width = orb.size * scale + 'px';
            orb.element.style.height = orb.size * scale + 'px';
        });
    }
    
    updateWaves() {
        this.waves.forEach((wave, index) => {
            const offset = this.time * wave.speed;
            const path = this.generateWavePath(wave.amplitude, wave.frequency, offset);
            
            // Update wave mask
            wave.element.style.maskImage = `url("data:image/svg+xml,${encodeURIComponent(path)}")`;
            wave.element.style.webkitMaskImage = `url("data:image/svg+xml,${encodeURIComponent(path)}")`;
        });
    }
    
    updateLiquidBlobs() {
        this.liquidBlobs.forEach((blob, index) => {
            // Update position
            blob.x += blob.speedX;
            blob.y += blob.speedY;
            
            // Boundary check with smooth direction change
            if (blob.x < 0 || blob.x > 100) {
                blob.speedX *= -0.9;
                blob.x = Math.max(0, Math.min(100, blob.x));
            }
            if (blob.y < 0 || blob.y > 100) {
                blob.speedY *= -0.9;
                blob.y = Math.max(0, Math.min(100, blob.y));
            }
            
            // Apply position
            blob.element.style.left = blob.x + '%';
            blob.element.style.top = blob.y + '%';
            
            // Morph shape
            const morphTime = this.time * blob.morphSpeed + blob.morphOffset;
            const borderRadius = this.generateBorderRadius(morphTime);
            blob.element.style.borderRadius = borderRadius;
            
            // Size variation
            const sizeFactor = 1 + Math.sin(morphTime * 0.5) * 0.2;
            blob.element.style.width = blob.size * sizeFactor + 'px';
            blob.element.style.height = blob.size * sizeFactor + 'px';
        });
    }
    
    generateWavePath(amplitude, frequency, offset) {
        const width = 1200;
        const height = 120;
        let path = `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 ${width} ${height}' preserveAspectRatio='none'><path d='M0,${height/2}`;
        
        for (let x = 0; x <= width; x += 10) {
            const y = height / 2 + Math.sin((x * frequency) + offset) * amplitude;
            path += ` L${x},${y}`;
        }
        
        path += ` L${width},${height} L0,${height} Z' fill='white'/></svg>`;
        return path;
    }
    
    generateBorderRadius(time) {
        const values = [
            30 + Math.sin(time) * 20,
            70 - Math.sin(time * 1.1) * 20,
            30 + Math.sin(time * 1.2) * 20,
            70 - Math.sin(time * 1.3) * 20
        ];
        
        return `${values[0]}% ${values[1]}% ${values[2]}% ${values[3]}%`;
    }
    
    createRipple(x, y) {
        const ripple = document.createElement('div');
        ripple.className = 'ripple-effect';
        ripple.style.position = 'absolute';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.style.width = '0px';
        ripple.style.height = '0px';
        ripple.style.borderRadius = '50%';
        ripple.style.border = '2px solid rgba(255, 255, 255, 0.5)';
        ripple.style.transform = 'translate(-50%, -50%)';
        ripple.style.pointerEvents = 'none';
        
        this.backgroundContainer.appendChild(ripple);
        
        // Animate ripple
        let size = 0;
        const maxSize = 300;
        const animateRipple = () => {
            size += 5;
            ripple.style.width = size + 'px';
            ripple.style.height = size + 'px';
            ripple.style.opacity = 1 - (size / maxSize);
            
            if (size < maxSize) {
                requestAnimationFrame(animateRipple);
            } else {
                ripple.remove();
            }
        };
        
        requestAnimationFrame(animateRipple);
    }
    
    getRandomColor() {
        const allColors = [
            ...this.options.colors.primary,
            ...this.options.colors.secondary,
            ...this.options.colors.tertiary,
            ...this.options.colors.orbs
        ];
        return allColors[Math.floor(Math.random() * allColors.length)];
    }
    
    handleResize() {
        // Update orb boundaries
        this.orbs.forEach(orb => {
            orb.x = Math.min(orb.x, window.innerWidth - orb.size / 2);
            orb.y = Math.min(orb.y, window.innerHeight - orb.size / 2);
        });
    }
    
    pause() {
        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }
    }
    
    resume() {
        if (!this.animationFrame) {
            this.animate();
        }
    }
    
    destroy() {
        this.pause();
        this.backgroundContainer.remove();
        this.meshes = [];
        this.orbs = [];
        this.waves = [];
        this.liquidBlobs = [];
    }
    
    // Public API
    setPerformanceMode(enabled) {
        this.options.performanceMode = enabled;
        
        if (enabled) {
            // Reduce effects
            this.meshContainer.style.filter = `blur(${this.options.blur.mesh * 0.5}px)`;
            this.liquidContainer.style.display = 'none';
            
            // Reduce orb count
            while (this.orbs.length > 3) {
                const orb = this.orbs.pop();
                orb.element.remove();
            }
        } else {
            // Restore effects
            this.meshContainer.style.filter = `blur(${this.options.blur.mesh}px)`;
            this.liquidContainer.style.display = 'block';
        }
    }
    
    setColorScheme(scheme) {
        if (typeof scheme === 'object') {
            Object.assign(this.options.colors, scheme);
            
            // Update existing elements
            this.meshes.forEach(mesh => {
                mesh.color1 = this.getRandomColor();
                mesh.color2 = this.getRandomColor();
            });
        }
    }
    
    addOrb(options = {}) {
        const orb = document.createElement('div');
        orb.className = 'physics-orb';
        this.orbsContainer.appendChild(orb);
        
        const orbData = {
            element: orb,
            x: options.x || Math.random() * window.innerWidth,
            y: options.y || Math.random() * window.innerHeight,
            vx: options.vx || (Math.random() - 0.5) * 2,
            vy: options.vy || (Math.random() - 0.5) * 2,
            size: options.size || Math.random() * 80 + 40,
            mass: options.mass || 1,
            color: options.color || this.getRandomColor()
        };
        
        orb.style.width = orbData.size + 'px';
        orb.style.height = orbData.size + 'px';
        orb.style.background = `radial-gradient(circle at 30% 30%, ${orbData.color}33, ${orbData.color})`;
        
        this.orbs.push(orbData);
        return orbData;
    }
}

// Export for use
export { GradientMeshSystem };