<?php

return [
    'purchase'                      => 'Purchase',
    'details'                       => 'Purchase Details',
    'list'                          => 'Purchase List',
    'create'                        => 'Create Purchase',
    'code'                          => 'Purchase Code',
    'number'                        => 'Purchase Number',
    'items'                         => 'Purchase Items',
    'update'                        => 'Update Purchase',

    'bill'                          => 'Purchase Bill',
    'bill_no'                       => 'Bill No.',
    'bills'                         => 'Purchase Bills',
    'print'                         => 'Purchase Print',
    'convert_to_purchase'           => 'Convert to Purchase',
    'already_converted'             => 'Purchase Order Already Converted to Purchase Bill',
    'return_to'                     => 'Return To',
    'purchase_invoice_number'       => 'Purchase Invoice Number',
    'debit_note'                    => 'Debit Note',
    'convert_to_return'             => 'Convert to Return',
    'purchase_bill_number'          => 'Purchase Bill Number',
    'convert_to_bill'               => 'Convert to Bill',
    'item_purchase'                 => 'Item Purchase',
    'item_purchase_today'                 => 'Purchases Within 24',
    'purchase_report'               => 'Purchase Report',
    'item_purchase_report'          => 'Item Purchase Report',
    'item_purchase_report_today'          => 'Item Purchase Report 24 hours',
    'purchase_payment_report'       => 'Purchase Payment Report',
    'purchase_payment'              => 'Purchase Payment',

    'purchase_without_tax'          => 'Purchase Without Tax',
    'purchase_return_without_tax'   => 'Purchase Return Without Tax',
    'purchase_bills'                => 'Purchase Bills',
    'stock_status'                 => 'Stock Status',
    'purchase_status'               => 'Purchase Status',

    'return' => [
                    'return'         => 'Purchase Return/Dr.Note',
                    'create'        => 'Purchase Return Create',
                    'details'       => 'Purchase Return Details',
                    'code'          => 'Return ID',
                    'date'          => 'Return Date',
                    'print'         => 'Purchase Return Print',
                    // 'status'        => 'Purchase Order Status',
                    // 'type'          => 'Purchase Order Type',
                    // 'create'        => 'Create Purchase Order',
                    // 'list'          => 'Purchase Order List',
                    'update'        => 'Update Purchase Return',
                ],

    'order' => [
                    'order'         => 'Purchase Order',
                    'number'        => 'Order Number',
                    'code'          => 'Order ID',
                    'status'        => 'Purchase Order Status',
                    'type'          => 'Purchase Order Type',
                    'details'       => 'Purchase Order Details',
                    'create'        => 'Create Purchase Order',
                    'list'          => 'Purchase Order List',
                    'update'        => 'Update Purchase Order',
                    'print'         => 'Purchase Order Print',
                    'pending'       => 'Pending Purchase Orders',
                    'completed'     => 'Completed Purchase Orders',
                    'st'             =>'Order Return List',
                    'or'             =>'Order Return',
                    'oc'=>'Create Return Order',
                    'od'=>'Return Order Details',
                    'uo'        => 'Update Return Order',

                ],

    //1.4
    'add'                        => 'Add Purchase',
    'purchased_items_history'                        => 'Purchased Items History',
    'purchased_items'                        => 'Purchased Items',
    'purchase_return'                        => 'Purchase Return',

    // Purchase Status Translations
    'pending'                    => 'Pending',
    'processing'                 => 'Processing',
    'ordered'                    => 'Ordered',
    'shipped'                    => 'Shipping',
    'rog'                        => 'Receipt of Goods',
    'cancelled'                  => 'Cancelled',
    'returned'                   => 'Returned',

    // Inventory Status Messages
    'inventory_pending'          => 'Inventory Pending',
    'inventory_added'            => 'Inventory Added',
    'inventory_removed'          => 'Inventory Removed',
    'post_receipt_return'        => 'Returned After Receipt',
    'post_receipt_cancel'        => 'Cancelled After Receipt',
    'inventory_ready_for_addition' => 'Ready for Addition',
    'inventory_post_receipt_action' => 'Post-Receipt Action',

    'rog_required_for_inventory' => 'Inventory will only be added when ROG (Receipt of Goods) status is reached',
    'no_backward_after_rog'      => 'Cannot go back to previous statuses after ROG has been confirmed',

    // Status Change History
    'status_change_history'      => 'Status Change History',
    'proof_image'                => 'Proof Image',
    'view_proof'                 => 'View Proof',
    'changed_by'                 => 'Changed by',
    'notes'                       => 'Notes',

];
