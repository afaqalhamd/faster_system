/**
 * Sales Status Options Helper
 * Provides dynamic loading of sales status options from the server
 */

class SalesStatusOptions {
    constructor() {
        this.statusOptions = [];
        this.statusesRequiringProof = [];
        this.loaded = false;
    }

    /**
     * Load status options from server
     */
    async loadStatusOptions() {
        if (this.loaded) {
            return this.statusOptions;
        }

        try {
            const response = await fetch('/sale/invoice/get-sales-status-options');
            const data = await response.json();

            if (data.status) {
                this.statusOptions = data.data;
                this.statusesRequiringProof = data.statuses_requiring_proof || [];
                this.loaded = true;
                console.log('Sales status options loaded:', this.statusOptions);
            } else {
                console.error('Failed to load status options');
            }
        } catch (error) {
            console.error('Error loading status options:', error);
        }

        return this.statusOptions;
    }

    /**
     * Get all status options
     */
    getStatusOptions() {
        return this.statusOptions;
    }

    /**
     * Get statuses that require proof
     */
    getStatusesRequiringProof() {
        return this.statusesRequiringProof;
    }

    /**
     * Check if a status requires proof
     */
    requiresProof(statusId) {
        return this.statusesRequiringProof.includes(statusId);
    }

    /**
     * Generate HTML options for select dropdown
     */
    generateSelectOptions(selectedValue = null) {
        return this.statusOptions.map(status => {
            const selected = selectedValue === status.id ? 'selected' : '';
            const dataAttrs = [
                `data-color="${status.color}"`,
                `data-requires-proof="${status.requires_proof}"`,
                `data-triggers-inventory="${status.triggers_inventory_deduction}"`,
                `data-restores-inventory="${status.restores_inventory}"`
            ].join(' ');

            return `<option value="${status.id}" ${selected} ${dataAttrs}>${status.name}</option>`;
        }).join('');
    }

    /**
     * Populate a select element with status options
     */
    async populateSelect(selectElement, selectedValue = null) {
        if (!this.loaded) {
            await this.loadStatusOptions();
        }

        const $select = $(selectElement);
        const currentClasses = $select.attr('class') || '';
        const currentAttributes = {
            'data-sale-id': $select.data('sale-id'),
            'name': $select.attr('name'),
            'id': $select.attr('id')
        };

        // Generate options HTML
        const optionsHtml = this.generateSelectOptions(selectedValue);

        // Update select element
        $select.html(optionsHtml);

        // Preserve attributes
        Object.keys(currentAttributes).forEach(attr => {
            if (currentAttributes[attr]) {
                $select.attr(attr, currentAttributes[attr]);
            }
        });

        // Add status-specific styling if needed
        this.addStatusStyling($select);
    }

    /**
     * Add status-specific styling to select options
     */
    addStatusStyling($select) {
        $select.find('option').each(function() {
            const $option = $(this);
            const color = $option.data('color');

            // Add CSS classes based on status properties
            if ($option.data('requires-proof')) {
                $option.addClass('requires-proof');
            }
            if ($option.data('triggers-inventory')) {
                $option.addClass('triggers-inventory');
            }
            if ($option.data('restores-inventory')) {
                $option.addClass('restores-inventory');
            }
        });
    }

    /**
     * Get status information by ID
     */
    getStatusById(statusId) {
        return this.statusOptions.find(status => status.id === statusId);
    }

    /**
     * Get status color by ID
     */
    getStatusColor(statusId) {
        const status = this.getStatusById(statusId);
        return status ? status.color : 'secondary';
    }

    /**
     * Create status badge HTML
     */
    createStatusBadge(statusId, customText = null) {
        const status = this.getStatusById(statusId);
        if (!status) return '';

        const text = customText || status.name;
        const color = status.color;

        return `<span class="badge bg-${color}">${text}</span>`;
    }
}

// Create global instance
window.salesStatusOptions = new SalesStatusOptions();

// Auto-load when document is ready
$(document).ready(function() {
    // Auto-populate any existing select elements with class 'auto-load-status'
    $('.auto-load-status').each(async function() {
        const selectedValue = $(this).data('selected-value') || $(this).val();
        await window.salesStatusOptions.populateSelect(this, selectedValue);
    });
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SalesStatusOptions;
}
