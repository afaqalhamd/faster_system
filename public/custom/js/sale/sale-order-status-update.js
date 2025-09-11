/**
 * Sale Order Status Update with Proof Requirements
 * Handles status updates with proof image and notes validation
 */

// $(document).ready(function() {
//     // Define statuses that require proof
//     const proofRequiredStatuses = ['POD', 'Cancelled', 'Returned'];
//
//     // Show/hide proof image section based on selected status
//     $(document).on('change', '#status', function() {
//         const selectedStatus = $(this).val();
//         if (proofRequiredStatuses.includes(selectedStatus)) {
//             $('#proofImageSection').show();
//         } else {
//             $('#proofImageSection').hide();
//         }
//     });
//
//     // Trigger change event on page load to show/hide proof section if needed
//     if ($('#status').length) {
//         $('#status').trigger('change');
//     }
//
//     // Update status button click handler
//     $(document).on('click', '#updateStatusBtn', function(e) {
//         e.preventDefault();
//
//         const status = $('#status').val();
//         const notes = $('#notes').val();
//         const proofImage = $('#proof_image')[0].files[0];
//
//         // Validate required fields
//         if (status === 'POD' && !proofImage) {
//             iziToast.error({
//                 title: 'Error',
//                 message: 'Proof image is required for POD status',
//                 position: 'topRight'
//             });
//             return;
//         }
//
//         if ((status === 'Cancelled' || status === 'Returned') && !notes.trim()) {
//             iziToast.error({
//                 title: 'Error',
//                 message: 'Notes are required for Cancelled/Returned statuses',
//                 position: 'topRight'
//             });
//             return;
//         }
//
//         const formData = new FormData();
//         formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
//         formData.append('id', $('input[name="sale_order_id"]').val());
//         formData.append('status', status);
//         formData.append('notes', notes);
//         if (proofImage) {
//             formData.append('proof_image', proofImage);
//         }
//
//         const button = $(this);
//         const originalText = button.html();
//         button.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i> Updating...');
//
//         $.ajax({
//             url: '/sale/order/update-status',
//             method: 'POST',
//             data: formData,
//             processData: false,
//             contentType: false,
//             success: function(response) {
//                 if (response.success) {
//                     iziToast.success({
//                         title: 'Success',
//                         message: response.message,
//                         position: 'topRight'
//                     });
//                     // Reload page to show updated status
//                     setTimeout(() => {
//                         location.reload();
//                     }, 1500);
//                 } else {
//                     iziToast.error({
//                         title: 'Error',
//                         message: response.message,
//                         position: 'topRight'
//                     });
//                 }
//             },
//             error: function(xhr) {
//                 const errorMessage = xhr.responseJSON?.message || 'An error occurred while updating status';
//                 iziToast.error({
//                     title: 'Error',
//                     message: errorMessage,
//                     position: 'topRight'
//                 });
//             },
//             complete: function() {
//                 button.prop('disabled', false).html(originalText);
//             }
//         });
//     });
// });
