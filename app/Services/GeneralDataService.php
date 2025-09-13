<?php

namespace App\Services;

class GeneralDataService{

    private $data;

    public function __construct()
    {
    	//
    }

    function getStaffStatus() : array{
        return [
                [
                    'id'    =>  'Pending',
                    'name'    =>  'Pending',
                ],
                [
                    'id'    =>  'Accepted',
                    'name'    =>  'Accepted',
                ],
                [
                    'id'    =>  'Rejected',
                    'name'    =>  'Rejected',
                ],
                [
                    'id'    =>  'Processing',
                    'name'    =>  'Processing',
                ],
                [
                    'id'    =>  'Completed',
                    'name'    =>  'Completed',
                ],
        ];
     }

     function getSaleOrderStatus() : array{
        return [

                [
                    'id'    =>  'Pending',
                    'name'    =>  'Pending',
                    'color'    =>  'warning',
                    'icon'    =>  'bx-time-five',
                ],
                [
                    'id'    =>  'Processing',
                    'name'    =>  'Processing',
                    'color'    =>  'primary',
                    'icon'    =>  'bx-loader-circle bx-spin',
                ],
                [
                    'id'    =>  'Completed',
                    'name'    =>  'Completed',
                    'color'    =>  'success',
                    'icon'    =>  'bx-check-circle',
                ],
                [
                    'id'    =>  'Delivery',
                    'name'    =>  'Delivery',
                    'color'    =>  'info',
                    'icon'    =>  'bx-package',
                ],
                [
                    'id'    =>  'POD',
                    'name'    =>  'POD',
                    'color'    =>  'primary',
                    'icon'    =>  'bx-receipt',
                ],
                [
                    'id'    =>  'Cancelled',
                    'name'    =>  'Cancelled',
                    'color'    =>  'danger',
                    'icon'    =>  'bx-x-circle',
                ],
                [
                    'id'    =>  'Returned',
                    'name'    =>  'Returned',
                    'color'    =>  'warning',
                    'icon'    =>  'bx-undo',
                ],
                [
                    'id'    =>  'No Status',
                    'name'    =>  'No Status',
                    'color'    =>  'secondary',
                    'icon'    =>  'bx-help-circle',
                ],
        ];
     }

     function getSaleStatus() : array{
        return [
                [
                    'id'    =>  'Pending',
                    'name'    =>  __('sale.pending'),
                    'color'    =>  'warning',
                    'icon'    =>  'bx-time-five',
                ],
                [
                    'id'    =>  'Processing',
                    'name'    =>  __('sale.processing'),
                    'color'    =>  'primary',
                    'icon'    =>  'bx-loader-circle bx-spin',
                ],
                [
                    'id'    =>  'Completed',
                    'name'    =>  __('sale.completed'),
                    'color'    =>  'success',
                    'icon'    =>  'bx-check-circle',
                ],
                [
                    'id'    =>  'Delivery',
                    'name'    =>  __('sale.delivery'),
                    'color'    =>  'info',
                    'icon'    =>  'bx-package',
                ],
                [
                    'id'    =>  'POD',
                    'name'    =>  __('sale.pod'),
                    'color'    =>  'success',
                    'icon'    =>  'bx-receipt',
                ],
                [
                    'id'    =>  'Cancelled',
                    'name'    =>  __('sale.cancelled'),
                    'color'    =>  'danger',
                    'icon'    =>  'bx-x-circle',
                ],
                [
                    'id'    =>  'Returned',
                    'name'    =>  __('sale.returned'),
                    'color'    =>  'warning',
                    'icon'    =>  'bx-undo',
                ],
        ];
     }

     function getPurchaseStatus() : array{
        return [
                [
                    'id'    =>  'Pending',
                    'name'    =>  __('purchase.pending'),
                    'color'    =>  'warning',
                    'icon'    =>  'bx-time-five',
                ],
                [
                    'id'    =>  'Processing',
                    'name'    =>  __('purchase.processing'),
                    'color'    =>  'primary',
                    'icon'    =>  'bx-loader-circle bx-spin',
                ],
                [
                    'id'    =>  'Ordered',
                    'name'    =>  __('purchase.ordered'),
                    'color'    =>  'info',
                    'icon'    =>  'bx-check-circle',
                ],
                [
                    'id'    =>  'Shipped',
                    'name'    =>  __('purchase.shipped'),
                    'color'    =>  'info',
                    'icon'    =>  'bx-package',
                ],
                [
                    'id'    =>  'ROG',
                    'name'    =>  __('purchase.rog'),
                    'color'    =>  'success',
                    'icon'    =>  'bx-receipt',
                ],
                [
                    'id'    =>  'Cancelled',
                    'name'    =>  __('purchase.cancelled'),
                    'color'    =>  'danger',
                    'icon'    =>  'bx-x-circle',
                ],
                [
                    'id'    =>  'Returned',
                    'name'    =>  __('purchase.returned'),
                    'color'    =>  'warning',
                    'icon'    =>  'bx-undo',
                ],
        ];
     }

     function getPurchaseOrderStatus() : array{
        //Using Same Status as Sale Order
        return $this->getSaleOrderStatus();
     }

     function getQuotationStatus() : array{
        return [

                [
                    'id'    =>  'Pending',
                    'name'    =>  'Pending',
                    'color'    =>  'warning',
                ],
                [
                    'id'    =>  'Processing',
                    'name'    =>  'Processing',
                    'color'    =>  'primary',
                ],
                [
                    'id'    =>  'Completed',
                    'name'    =>  'Completed',
                    'color'    =>  'success',
                ],
                [
                    'id'    =>  'Cancelled',
                    'name'    =>  'Cancelled',
                    'color'    =>  'danger',
                ],
                [
                    'id'    =>  'On Hold',
                    'name'    =>  'On Hold',
                    'color'    =>  'secondary',
                ],

        ];
     }

     /**
     * Helper for replacement of keywords
     * */
    function replaceTemplateKeywords($template, array $replacements)
    {
        $cleanedTemplate = $template;
        foreach ($replacements as $keyword => $value) {
            //$cleanedTemplate = str_replace(':'.$keyword, $value, $cleanedTemplate);
            $cleanedTemplate = str_replace($keyword, $value, $cleanedTemplate);
        }
        return $cleanedTemplate;
    }

    /**
     * Record Batch Tracking Row Count
     * */
    public function getBatchTranckingRowCount(){
        $companySettings = app('company');
        $trackableFields = [
                                'enable_batch_tracking',
                                'enable_mfg_date',
                                'enable_exp_date',
                                'enable_model',
                                //'show_mrp',
                                'enable_color',
                                'enable_size'
                            ];
        $batchTrackingRowCount = array_sum(array_map(function ($field) use ($companySettings) {
                                  return (isset($companySettings[$field]) && $companySettings[$field] ==1) ? 1 : 0;
                                }, $trackableFields));

        return $batchTrackingRowCount;
    }
}
