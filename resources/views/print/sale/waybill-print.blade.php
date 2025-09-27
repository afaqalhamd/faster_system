<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('shipment.waybill') }}</title>
    <script src="{{ versionedAsset('custom/libraries/barcode-lib/bwip-js-min.js') }}"></script>
    <style>
        @media print {
            body {
                width: 100%;
                margin: 0 auto;
                padding: 0;
            }
            .invoice-wrapper {
                page-break-after: always;
            }
            .print-btn {
                display: none;
            }
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.2;
        }
        .container {
            max-width: 80mm;
            margin: 0 auto;
            padding: 10px;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 10px;
        }
        .invoice-title {
            font-size: 16px;
            font-weight: bold;
        }
        .company-info {
            text-align: center;
            margin-bottom: 10px;
            font-size: 10px;
        }
        .waybill-info {
            margin: 10px 0;
            padding: 5px;
            border: 1px solid #000;
        }
        .waybill-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .waybill-info td {
            padding: 3px;
            border: 1px solid #000;
        }
        .barcode-container {
            text-align: center;
            margin: 15px 0;
        }
        .barcode-canvas {
            width: 100%;
            height: 50px;
        }
        .qrcode-container {
            text-align: center;
            margin: 15px 0;
        }
        .qrcode-canvas {
            width: 70%;
            height: auto;
            max-width: 200px;
            max-height: 200px;
        }
        .barcode-number {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 8px;
        }
        .print-btn {
            display: block;
            width: 100%;
            margin-top: 10px;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            font-size: 16px;
            cursor: pointer;
        }
        .print-btn:hover {
            background-color: #218838;
        }

        /* Mobile-specific styles */
        @media screen and (max-width: 768px) {
            .container {
                max-width: 100%;
                padding: 5px;
            }
            .qrcode-canvas {
                width: 60%;
                max-width: 150px;
                max-height: 150px;
            }
        }

        @media screen and (max-width: 480px) {
            .qrcode-canvas {
                width: 50%;
                max-width: 120px;
                max-height: 120px;
            }
            .invoice-title {
                font-size: 14px;
            }
            body {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-wrapper">
        <div class="container">
            <div class="invoice-header">
                <div class="invoice-title">{{ __('shipment.waybill') }}</div>
                <div class="company-info">
                    {{ app('company')['name'] }}<br>
                    {{ app('company')['address'] }}<br>
                    @if(app('company')['mobile'] || app('company')['email'])
                        {{ app('company')['mobile'] ? 'Phone: '. app('company')['mobile'] : ''}}
                        {{ app('company')['email'] ? 'Email: '.app('company')['email'] : '' }}
                    @endif
                </div>
            </div>

            <div class="waybill-info">
                <table>
                    <tr>
                        <td><strong>{{ __('sale.order.code') }}:</strong></td>
                        <td>{{ $order->order_code }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('app.date') }}:</strong></td>
                        <td>{{ $order->formatted_order_date }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('customer.customer') }}:</strong></td>
                        <td>{{ $order->party->first_name.' '. $order->party->last_name }}</td>
                    </tr>
                    @if($shipmentTracking)
                        @if($shipmentTracking->carrier)
                        <tr>
                            <td><strong>{{ __('carrier.carrier') }}:</strong></td>
                            <td>{{ $shipmentTracking->carrier->name }}</td>
                        </tr>
                        @endif
                        @if($shipmentTracking->waybill_number)
                        <tr>
                            <td><strong>{{ __('shipment.waybill_number') }}:</strong></td>
                            <td>{{ $shipmentTracking->waybill_number }}</td>
                        </tr>
                        @endif
                        @if($shipmentTracking->waybill_type)
                        <tr>
                            <td><strong>{{ __('shipment.waybill_type') }}:</strong></td>
                            <td>{{ __('shipment.'.$shipmentTracking->waybill_type) }}</td>
                        </tr>
                        @endif
                        @if($shipmentTracking->tracking_number)
                        <tr>
                            <td><strong>{{ __('shipment.tracking_number') }}:</strong></td>
                            <td>{{ $shipmentTracking->tracking_number }}</td>
                        </tr>
                        @endif
                        @if($shipmentTracking->estimated_delivery_date)
                        <tr>
                            <td><strong>{{ __('shipment.estimated_delivery_date') }}:</strong></td>
                            <td>{{ $shipmentTracking->estimated_delivery_date->format('M d, Y') }}</td>
                        </tr>
                        @endif
                    @endif
                </table>
            </div>

            @if($shipmentTracking && $shipmentTracking->waybill_number)
            <div class="barcode-container">
                <canvas id="waybill-barcode" class="barcode-canvas"></canvas>
                <div class="barcode-number">{{ $shipmentTracking->waybill_number }}</div>
            </div>

            <div class="qrcode-container">
                <canvas id="waybill-qrcode" class="qrcode-canvas"></canvas>
                <div class="barcode-number">{{ $shipmentTracking->waybill_number }}</div>
            </div>
            @endif

            <div class="footer">
                <p>{{ __('shipment.waybill_info_text') }}</p>
            </div>
        </div>
    </div>

    <button class="print-btn" onclick="window.print()">{{ __('app.print') }}</button>

    <script>
        window.onload = function() {
            @if($shipmentTracking && $shipmentTracking->waybill_number)
            try {
                // Generate barcode using bwip-js
                bwipjs.toCanvas('waybill-barcode', {
                    bcid: 'code128',       // Barcode type
                    text: '{{ $shipmentTracking->waybill_number }}',    // Text to encode
                    scale: 2,              // 2x scaling factor
                    height: 10,            // Bar height, in millimeters
                    includetext: false,    // Show human-readable text
                    textxalign: 'center',  // Text alignment
                });

                // Generate responsive QR code
                var qrSize = 200; // Default size

                // Adjust size based on screen width
                if (window.innerWidth <= 480) {
                    qrSize = 120;
                } else if (window.innerWidth <= 768) {
                    qrSize = 150;
                }

                bwipjs.toCanvas('waybill-qrcode', {
                    bcid: 'qrcode',        // QR Code type
                    text: '{{ $shipmentTracking->waybill_number }}',    // Text to encode
                    scale: 3,              // 3x scaling factor
                    width: qrSize,         // Width based on screen size
                    height: qrSize,        // Height based on screen size
                    textxalign: 'center',  // Text alignment
                });
            } catch (e) {
                console.error('Barcode/QR code generation failed:', e);
            }
            @endif

            // Auto-print when page loads (optional)
            // window.print();
        };
    </script>
</body>
</html>
