/**
 * Delivery Workflow JavaScript Class
 * Handles the complete delivery process with barcode scanning and payment verification
 */

class DeliveryWorkflow {
    constructor() {
        this.baseUrl = '/api/delivery';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]') ?
            document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
    }

    /**
     * Process scanned barcode
     * @param {string} waybillNumber - The scanned waybill number
     * @returns {Promise<Object>} - The response data
     */
    async processBarcode(waybillNumber) {
        try {
            const response = await fetch('/api/waybill/process-qr', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    waybill_number: waybillNumber
                })
            });

            const data = await response.json();

            if (data.status) {
                // Display order details
                this.displayOrderDetails(data.data);

                // Check payment status
                if (data.data.payment_status.is_complete) {
                    // Show POD confirmation form directly
                    this.showPODForm(data.data.sale_order);
                } else {
                    // Show payment collection form
                    this.showPaymentForm(data.data.sale_order, data.data.payment_status);
                }

                return data;
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            this.showError('Failed to process barcode: ' + error.message);
            throw error;
        }
    }

    /**
     * Display order details
     * @param {Object} orderData - The order data to display
     */
    displayOrderDetails(orderData) {
        // This method would be implemented based on the specific UI requirements
        console.log('Order details:', orderData);

        // Example implementation:
        // Update UI elements with order information
        // document.getElementById('customer-name').textContent = orderData.customer.name;
        // document.getElementById('order-code').textContent = orderData.sale_order.order_code;
        // document.getElementById('order-total').textContent = orderData.sale_order.grand_total;
    }

    /**
     * Show payment collection form
     * @param {Object} order - The order object
     * @param {Object} paymentStatus - The payment status information
     */
    showPaymentForm(order, paymentStatus) {
        // This method would be implemented based on the specific UI requirements
        console.log('Payment form for order:', order, 'Due:', paymentStatus.due_amount);

        // Example implementation:
        // Show payment collection modal or form
        // document.getElementById('payment-amount').value = paymentStatus.due_amount;
        // document.getElementById('payment-form').style.display = 'block';
        // document.getElementById('pod-form').style.display = 'none';
    }

    /**
     * Show POD confirmation form
     * @param {Object} order - The order object
     */
    showPODForm(order) {
        // This method would be implemented based on the specific UI requirements
        console.log('POD form for order:', order);

        // Example implementation:
        // Show POD confirmation modal or form
        // document.getElementById('pod-form').style.display = 'block';
        // document.getElementById('payment-form').style.display = 'none';
    }

    /**
     * Collect payment
     * @param {number} orderId - The order ID
     * @param {Object} paymentData - The payment data
     * @returns {Promise<Object>} - The response data
     */
    async collectPayment(orderId, paymentData) {
        try {
            const response = await fetch(`${this.baseUrl}/orders/${orderId}/payment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Authorization': 'Bearer ' + this.getAuthToken()
                },
                body: JSON.stringify(paymentData)
            });

            const data = await response.json();

            if (data.status) {
                // After successful payment, show POD form
                // In a real implementation, you would fetch the updated order details
                // this.showPODForm({id: orderId});
                return data;
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            this.showError('Payment collection failed: ' + error.message);
            throw error;
        }
    }

    /**
     * Complete delivery with POD
     * @param {number} orderId - The order ID
     * @param {Object} podData - The POD data
     * @returns {Promise<Object>} - The response data
     */
    async completeDelivery(orderId, podData) {
        try {
            const response = await fetch(`${this.baseUrl}/orders/${orderId}/complete-delivery`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Authorization': 'Bearer ' + this.getAuthToken()
                },
                body: JSON.stringify(podData)
            });

            const data = await response.json();

            if (data.status) {
                // Show success message
                this.showSuccess('Delivery completed successfully');
                return data;
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            this.showError('Delivery completion failed: ' + error.message);
            throw error;
        }
    }

    /**
     * Get authentication token
     * @returns {string} - The authentication token
     */
    getAuthToken() {
        // Implementation to get auth token
        // This would depend on how the token is stored in your application
        return localStorage.getItem('delivery_token') || sessionStorage.getItem('delivery_token') || '';
    }

    /**
     * Show error message
     * @param {string} message - The error message to display
     */
    showError(message) {
        // Implementation to show error message
        console.error('Error:', message);
        // Example: Display in a notification div or alert
        // iziToast.error({ message: message });
    }

    /**
     * Show success message
     * @param {string} message - The success message to display
     */
    showSuccess(message) {
        // Implementation to show success message
        console.log('Success:', message);
        // Example: Display in a notification div or alert
        // iziToast.success({ message: message });
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DeliveryWorkflow;
} else {
    // Make it available globally
    window.DeliveryWorkflow = DeliveryWorkflow;
}
