/**
 * Purchase Status Icons and Utilities
 * Provides consistent status icons and display across the application
 */

class PurchaseStatusIcons {
    constructor() {
        // Define translation keys for each status
        this.statusConfig = {
            'Pending': {
                icon: 'bx-time-five',
                color: 'warning',
                translationKey: 'purchase.pending',
                description: 'Waiting for processing'
            },
            'Processing': {
                icon: 'bx-loader-circle bx-spin',
                color: 'primary',
                translationKey: 'purchase.processing',
                description: 'Purchase is being processed'
            },
            'Completed': {
                icon: 'bx-check-circle',
                color: 'success',
                translationKey: 'purchase.completed',
                description: 'Purchase completed successfully'
            },
            'Shipped': {
                icon: 'bx-package',
                color: 'info',
                translationKey: 'purchase.shipped',
                description: 'Items shipped'
            },
            'ROG': {
                icon: 'bx-receipt',
                color: 'success',
                translationKey: 'purchase.rog',
                description: 'Receipt of Goods'
            },
            'Cancelled': {
                icon: 'bx-x-circle',
                color: 'danger',
                translationKey: 'purchase.cancelled',
                description: 'Purchase cancelled'
            },
            'Returned': {
                icon: 'bx-undo',
                color: 'warning',
                translationKey: 'purchase.returned',
                description: 'Purchase returned'
            }
        };

        // Store translations - will be populated from the server
        this.translations = {};
    }

    /**
     * Set translations for the status texts
     * This method should be called from the Blade template with translated texts
     */
    setTranslations(translations) {
        this.translations = translations;
    }

    /**
     * Check if the document is in RTL mode
     */
    isRTL() {
        return document.documentElement.dir === 'rtl';
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
            translationKey: 'purchase.unknown',
            description: 'Unknown status'
        };
    }

    /**
     * Get translated status text
     */
    getTranslatedStatus(status) {
        const config = this.getStatusConfig(status);
        // If we have translations loaded, use them; otherwise fallback to the status name
        return this.translations[config.translationKey] || this.translations[status] || status;
    }

    /**
     * Create status badge with icon
     */
    createStatusBadge(status, options = {}) {
        const config = this.getStatusConfig(status);
        const translatedStatus = this.getTranslatedStatus(status);
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

        // Handle RTL direction for icon positioning
        const isRTL = this.isRTL();
        const iconHtml = `<i class="${iconClass}" title="${config.description}"></i>`;
        const textHtml = showText ? `<span class="${isRTL ? 'me-1' : 'ms-1'}">${translatedStatus}</span>` : '';

        if (showText) {
            // For RTL, we need to reverse the order of icon and text
            if (isRTL) {
                return `<div class="${badgeClass} d-flex align-items-center text-uppercase">
                            ${textHtml}
                            ${iconHtml}
                        </div>`;
            } else {
                return `<div class="${badgeClass} d-flex align-items-center text-uppercase">
                            ${iconHtml}
                            ${textHtml}
                        </div>`;
            }
        } else {
            return `<div class="${badgeClass} d-flex align-items-center justify-content-center" title="${translatedStatus} - ${config.description}">
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
        const translatedStatus = this.getTranslatedStatus(selectedValue);

        // Find or create icon container
        let $iconContainer = $select.siblings('.status-icon-display');
        if ($iconContainer.length === 0) {
            $iconContainer = $('<span class="status-icon-display ms-2"></span>');
            $select.after($iconContainer);
        }

        // Handle RTL for margin
        if (this.isRTL()) {
            $iconContainer.removeClass('ms-2').addClass('me-2');
        } else {
            $iconContainer.removeClass('me-2').addClass('ms-2');
        }

        // Update the title with translated status
        $iconContainer.html(this.createIcon(selectedValue, { size: 'font-16' }));
        $iconContainer.attr('title', translatedStatus + ' - ' + config.description);
    }

    /**
     * Initialize all status selects on page
     */
    initializeStatusSelects() {
        $('.purchase-status-select, .status-select').each((index, element) => {
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
window.purchaseStatusIcons = new PurchaseStatusIcons();

// Auto-initialize when document is ready
$(document).ready(function() {
    // Initialize all status selects
    window.purchaseStatusIcons.initializeStatusSelects();

    // Re-initialize on dynamic content load
    $(document).on('DOMNodeInserted', function(e) {
        if ($(e.target).hasClass('purchase-status-select') || $(e.target).find('.purchase-status-select').length > 0) {
            setTimeout(() => {
                window.purchaseStatusIcons.initializeStatusSelects();
            }, 100);
        }
    });
});
