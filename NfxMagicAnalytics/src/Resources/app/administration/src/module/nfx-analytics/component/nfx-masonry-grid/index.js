import template from './nfx-masonry-grid.html.twig';
import './nfx-masonry-grid.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('nfx-masonry-grid', {
    template,

    props: {
        items: {
            type: Array,
            required: true,
            default: () => []
        },
        columnWidth: {
            type: Number,
            default: 320
        },
        gutter: {
            type: Number,
            default: 16
        },
        transitionDuration: {
            type: Number,
            default: 300
        },
        enableDragDrop: {
            type: Boolean,
            default: true
        }
    },

    data() {
        return {
            columns: [],
            columnCount: 1,
            isDragging: false,
            draggedItem: null,
            draggedElement: null,
            dropIndicator: null,
            touchStartX: 0,
            touchStartY: 0,
            resizeObserver: null,
            itemHeights: new Map()
        };
    },

    computed: {
        gridStyle() {
            return {
                display: 'grid',
                gridTemplateColumns: `repeat(${this.columnCount}, 1fr)`,
                gap: `${this.gutter}px`,
                transition: `all ${this.transitionDuration}ms cubic-bezier(0.4, 0.0, 0.2, 1)`
            };
        },

        breakpoints() {
            return {
                xs: 576,
                sm: 768,
                md: 992,
                lg: 1200,
                xl: 1400
            };
        }
    },

    watch: {
        items: {
            handler() {
                this.$nextTick(() => {
                    this.updateLayout();
                });
            },
            deep: true
        }
    },

    mounted() {
        this.initializeGrid();
        this.setupEventListeners();
        this.setupResizeObserver();
        
        // Initial layout calculation
        this.$nextTick(() => {
            this.updateLayout();
        });
    },

    beforeDestroy() {
        this.cleanupEventListeners();
        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
        }
    },

    methods: {
        initializeGrid() {
            this.calculateColumns();
            this.updateLayout();
        },

        calculateColumns() {
            const containerWidth = this.$el?.clientWidth || window.innerWidth;
            const minColumnWidth = this.columnWidth + this.gutter;
            
            // Calculate optimal column count based on container width
            this.columnCount = Math.max(1, Math.floor(containerWidth / minColumnWidth));
            
            // Responsive adjustments
            if (containerWidth < this.breakpoints.xs) {
                this.columnCount = 1;
            } else if (containerWidth < this.breakpoints.sm) {
                this.columnCount = Math.min(2, this.columnCount);
            } else if (containerWidth < this.breakpoints.md) {
                this.columnCount = Math.min(3, this.columnCount);
            }
        },

        updateLayout() {
            if (!this.$refs.gridContainer) return;

            const items = this.$refs.gridItems || [];
            const columnHeights = new Array(this.columnCount).fill(0);
            const itemPositions = [];

            // Calculate positions for each item
            items.forEach((item, index) => {
                const itemData = this.items[index];
                if (!itemData) return;

                // Find the shortest column
                const shortestColumn = columnHeights.indexOf(Math.min(...columnHeights));
                const x = shortestColumn * (100 / this.columnCount);
                const y = columnHeights[shortestColumn];

                // Store position
                itemPositions.push({
                    index,
                    x: `${x}%`,
                    y: `${y}px`,
                    column: shortestColumn
                });

                // Update column height
                const itemHeight = item.offsetHeight || 0;
                columnHeights[shortestColumn] += itemHeight + this.gutter;
                this.itemHeights.set(index, itemHeight);
            });

            // Apply positions with animation
            requestAnimationFrame(() => {
                items.forEach((item, index) => {
                    const position = itemPositions[index];
                    if (!position) return;

                    item.style.position = 'absolute';
                    item.style.left = position.x;
                    item.style.top = position.y;
                    item.style.width = `calc(${100 / this.columnCount}% - ${this.gutter * (this.columnCount - 1) / this.columnCount}px)`;
                    item.style.transition = `all ${this.transitionDuration}ms cubic-bezier(0.4, 0.0, 0.2, 1)`;
                });

                // Set container height
                const maxHeight = Math.max(...columnHeights);
                this.$refs.gridContainer.style.height = `${maxHeight}px`;
            });
        },

        setupEventListeners() {
            window.addEventListener('resize', this.handleResize);
            
            if (this.enableDragDrop) {
                // Mouse events
                document.addEventListener('mousemove', this.handleMouseMove);
                document.addEventListener('mouseup', this.handleMouseUp);
                
                // Touch events
                document.addEventListener('touchmove', this.handleTouchMove, { passive: false });
                document.addEventListener('touchend', this.handleTouchEnd);
            }
        },

        cleanupEventListeners() {
            window.removeEventListener('resize', this.handleResize);
            document.removeEventListener('mousemove', this.handleMouseMove);
            document.removeEventListener('mouseup', this.handleMouseUp);
            document.removeEventListener('touchmove', this.handleTouchMove);
            document.removeEventListener('touchend', this.handleTouchEnd);
        },

        setupResizeObserver() {
            if (!window.ResizeObserver) return;

            this.resizeObserver = new ResizeObserver((entries) => {
                for (const entry of entries) {
                    this.handleResize();
                }
            });

            if (this.$el) {
                this.resizeObserver.observe(this.$el);
            }
        },

        handleResize: Shopware.Utils.debounce(function() {
            this.calculateColumns();
            this.updateLayout();
        }, 250),

        // Drag and Drop functionality
        handleDragStart(event, item, index) {
            if (!this.enableDragDrop) return;

            this.isDragging = true;
            this.draggedItem = item;
            this.draggedElement = event.target.closest('.masonry-item');
            
            if (this.draggedElement) {
                this.draggedElement.classList.add('dragging');
                
                // Create drag image
                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setDragImage(this.draggedElement, event.offsetX, event.offsetY);
                }
            }

            this.$emit('drag-start', { item, index });
        },

        handleMouseMove(event) {
            if (!this.isDragging || !this.draggedElement) return;
            
            this.updateDropIndicator(event.clientX, event.clientY);
        },

        handleMouseUp(event) {
            if (!this.isDragging) return;
            
            this.handleDrop(event.clientX, event.clientY);
        },

        // Touch support
        handleTouchStart(event, item, index) {
            if (!this.enableDragDrop) return;

            const touch = event.touches[0];
            this.touchStartX = touch.clientX;
            this.touchStartY = touch.clientY;
            
            this.handleDragStart(event, item, index);
        },

        handleTouchMove(event) {
            if (!this.isDragging) return;
            
            event.preventDefault();
            const touch = event.touches[0];
            this.updateDropIndicator(touch.clientX, touch.clientY);
        },

        handleTouchEnd(event) {
            if (!this.isDragging) return;
            
            const touch = event.changedTouches[0];
            this.handleDrop(touch.clientX, touch.clientY);
        },

        updateDropIndicator(x, y) {
            const targetElement = this.getDropTarget(x, y);
            
            if (targetElement && targetElement !== this.draggedElement) {
                this.showDropIndicator(targetElement);
            } else {
                this.hideDropIndicator();
            }
        },

        getDropTarget(x, y) {
            const elements = this.$refs.gridItems || [];
            
            for (const element of elements) {
                const rect = element.getBoundingClientRect();
                
                if (x >= rect.left && x <= rect.right && 
                    y >= rect.top && y <= rect.bottom) {
                    return element;
                }
            }
            
            return null;
        },

        showDropIndicator(targetElement) {
            if (!this.dropIndicator) {
                this.dropIndicator = document.createElement('div');
                this.dropIndicator.className = 'masonry-drop-indicator';
                this.$refs.gridContainer.appendChild(this.dropIndicator);
            }
            
            const rect = targetElement.getBoundingClientRect();
            const containerRect = this.$refs.gridContainer.getBoundingClientRect();
            
            this.dropIndicator.style.left = `${rect.left - containerRect.left}px`;
            this.dropIndicator.style.top = `${rect.top - containerRect.top}px`;
            this.dropIndicator.style.width = `${rect.width}px`;
            this.dropIndicator.style.height = `${rect.height}px`;
            this.dropIndicator.style.display = 'block';
        },

        hideDropIndicator() {
            if (this.dropIndicator) {
                this.dropIndicator.style.display = 'none';
            }
        },

        handleDrop(x, y) {
            const targetElement = this.getDropTarget(x, y);
            
            if (targetElement && targetElement !== this.draggedElement) {
                const draggedIndex = this.getItemIndex(this.draggedElement);
                const targetIndex = this.getItemIndex(targetElement);
                
                if (draggedIndex !== -1 && targetIndex !== -1) {
                    this.reorderItems(draggedIndex, targetIndex);
                }
            }
            
            this.cleanupDrag();
        },

        getItemIndex(element) {
            const items = this.$refs.gridItems || [];
            return items.indexOf(element);
        },

        reorderItems(fromIndex, toIndex) {
            const newItems = [...this.items];
            const [movedItem] = newItems.splice(fromIndex, 1);
            newItems.splice(toIndex, 0, movedItem);
            
            this.$emit('update:items', newItems);
            this.$emit('reorder', { fromIndex, toIndex, item: movedItem });
            
            // Animate the reordering
            this.$nextTick(() => {
                this.updateLayout();
            });
        },

        cleanupDrag() {
            this.isDragging = false;
            this.draggedItem = null;
            
            if (this.draggedElement) {
                this.draggedElement.classList.remove('dragging');
                this.draggedElement = null;
            }
            
            this.hideDropIndicator();
            
            if (this.dropIndicator && this.dropIndicator.parentNode) {
                this.dropIndicator.parentNode.removeChild(this.dropIndicator);
                this.dropIndicator = null;
            }
        },

        handleItemClick(item, index) {
            this.$emit('item-click', { item, index });
        },

        handleDragOver(event) {
            if (!this.isDragging) return;
            event.preventDefault();
        }
    }
});