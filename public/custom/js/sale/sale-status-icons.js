/**
 * Sales Status Icons and Utilities
 * Provides consistent status icons and display across the application
 */

class SaleStatusIcons {
    constructor() {
        this.statusConfig = {
            'Pending': {
                icon: 'bx-time-five',
                color: 'warning',
                description: 'Waiting for processing'
            },
            'Processing': {
                icon: 'bx-loader-circle bx-spin',
                color: 'primary',
                description: 'Order is being processed'
            },
            'Completed': {
                icon: 'bx-check-circle',
                color: 'success',
                description: 'Order completed successfully'
            },
            'Delivery': {
                icon: 'bx-package',
                color: 'info',
                description: 'Out for delivery'
            },
            'POD': {
                icon: 'bx-receipt',
                color: 'primary',
                description: 'Proof of Delivery required'
            },
            'Cancelled': {
                icon: 'bx-x-circle',
                color: 'danger',
                description: 'Order cancelled'
            },
            'Returned': {
                icon: 'bx-undo',
                color: 'secondary',
                description: 'Order returned'
            }
        };
    }

    /**
     * Get status configuration
     */
    getStatusConfig(status) {
        // Handle both uppercase and lowercase
        const normalizedStatus = status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
        return this.statusConfig[normalizedStatus] || this.statusConfig[status] || {
            icon: 'bx-help-circle',
            color: 'secondary',
            description: 'Unknown status'
        };
    }

    /**
     * Create status badge with icon
     */
    createStatusBadge(status, options = {}) {
        const config = this.getStatusConfig(status);
        const size = options.size || 'normal'; // small, normal, large
        const showText = options.showText !== false; // default true
        const customClass = options.customClass || '';

        let badgeClass = `badge bg-light-${config.color} text-${config.color}`;
        let iconClass = `fadeIn animated bx ${config.icon}`;

        // Size variations
        if (size === 'small') {
            badgeClass += ' p-1 px-2';
            iconClass += ' small';
        } else if (size === 'large') {
            badgeClass += ' p-3 px-4';
            iconClass += ' font-18';
        } else {
            badgeClass += ' p-2 px-3';
        }

        if (customClass) {
            badgeClass += ` ${customClass}`;
        }

        const iconHtml = `<i class="${iconClass}" title="${config.description}"></i>`;
        const textHtml = showText ? `<span class="ms-1">${status}</span>` : '';

        if (showText) {
            return `<div class="${badgeClass} d-flex align-items-center text-uppercase">
                        ${iconHtml}
                        ${textHtml}
                    </div>`;
        } else {
            return `<div class="${badgeClass} d-flex align-items-center justify-content-center" title="${status} - ${config.description}">
                        ${iconHtml}
                    </div>`;
        }
    }

    /**
     * Create icon only (for dropdowns, etc.)
     */
    createIcon(status, options = {}) {
        const config = this.getStatusConfig(status);
        const size = options.size || '';
        const customClass = options.customClass || '';

        let iconClass = `fadeIn animated bx ${config.icon} text-${config.color}`;

        if (size) {
            iconClass += ` ${size}`;
        }

        if (customClass) {
            iconClass += ` ${customClass}`;
        }

        return `<i class="${iconClass}" title="${config.description}"></i>`;
    }

    /**
     * Enhance select dropdown with icons
     */
    enhanceStatusSelect(selectElement) {
        const $select = $(selectElement);

        // Add icons to options (for when dropdown is opened)
        $select.find('option').each((index, option) => {
            const $option = $(option);
            const status = $option.val();
            if (status) {
                const config = this.getStatusConfig(status);
                $option.attr('data-icon', config.icon);
                $option.attr('data-color', config.color);
            }
        });

        // Update display when selection changes
        $select.on('change', () => {
            this.updateSelectDisplay($select);
        });

        // Initial display update
        this.updateSelectDisplay($select);
    }

    /**
     * Update select display with icon
     */
    updateSelectDisplay($select) {
        const selectedValue = $select.val();
        if (!selectedValue) return;

        const config = this.getStatusConfig(selectedValue);

        // Find or create icon container
        let $iconContainer = $select.siblings('.status-icon-display');
        if ($iconContainer.length === 0) {
            $iconContainer = $('<span class="status-icon-display ms-2"></span>');
            $select.after($iconContainer);
        }

        $iconContainer.html(this.createIcon(selectedValue, { size: 'font-16' }));
    }

    /**
     * Initialize all status selects on page
     */
    initializeStatusSelects() {
        $('.sales-status-select, .status-select').each((index, element) => {
            this.enhanceStatusSelect(element);
        });
    }

    /**
     * Get all available statuses
     */
    getAllStatuses() {
        return Object.keys(this.statusConfig);
    }

    /**
     * Get status color only
     */
    getStatusColor(status) {
        return this.getStatusConfig(status).color;
    }

    /**
     * Get status icon only
     */
    getStatusIcon(status) {
        return this.getStatusConfig(status).icon;
    }
}

// Create global instance
window.saleStatusIcons = new SaleStatusIcons();

// Auto-initialize when document is ready
$(document).ready(function() {
    // Initialize all status selects
    window.saleStatusIcons.initializeStatusSelects();

    // Re-initialize on dynamic content load
    $(document).on('DOMNodeInserted', function(e) {
        if ($(e.target).hasClass('sales-status-select') || $(e.target).find('.sales-status-select').length > 0) {
            setTimeout(() => {
                window.saleStatusIcons.initializeStatusSelects();
            }, 100);
        }
    });
});
