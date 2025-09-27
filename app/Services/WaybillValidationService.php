<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WaybillValidationService
{
    /**
     * Common carrier waybill format patterns
     */
    private const WAYBILL_PATTERNS = [
        // DHL format: GM + 10 digits
        'DHL' => '/^GM\d{10}$/',

        // FedEx format: 12 or 15 digits
        'FedEx' => '/^\d{12}$|^\d{15}$/',

        // UPS format: 1Z + 18 alphanumeric characters
        'UPS' => '/^1Z[A-Z0-9]{18}$/',

        // USPS format: 20 digits
        'USPS' => '/^\d{20}$/',

        // General formats for other carriers
        'GenericAlphanumeric' => '/^[A-Z0-9]{10,20}$/',
        'GenericNumeric' => '/^\d{12,18}$/',
    ];

    /**
     * Validate waybill format based on carrier or generic patterns
     *
     * @param string $waybillNumber
     * @param string|null $carrier
     * @return bool
     */
    public function validateWaybillFormat(string $waybillNumber, ?string $carrier = null): bool
    {
        // If carrier is specified, use carrier-specific pattern
        if ($carrier && isset(self::WAYBILL_PATTERNS[$carrier])) {
            return preg_match(self::WAYBILL_PATTERNS[$carrier], $waybillNumber) === 1;
        }

        // Try all patterns if no specific carrier
        foreach (self::WAYBILL_PATTERNS as $pattern) {
            if (preg_match($pattern, $waybillNumber) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get validation rules for waybill data
     *
     * @return array
     */
    public function getWaybillRules(): array
    {
        return [
            'waybill_number' => 'nullable|string|max:255|unique:shipment_trackings,waybill_number',
            'waybill_type' => 'nullable|string|in:AirwayBill,BillOfLading,CourierWaybill,Other',
        ];
    }

    /**
     * Validate waybill data
     *
     * @param array $data
     * @throws ValidationException
     */
    public function validateWaybillData(array $data): void
    {
        $rules = $this->getWaybillRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate barcode format for waybill number
     *
     * @param string $waybillNumber
     * @return bool
     */
    public function validateWaybillBarcode(string $waybillNumber): bool
    {
        // Check if waybill number follows common barcode formats
        // This is a simplified validation - in practice, this would depend on the carrier's format

        // Check for common waybill number patterns
        $patterns = [
            '/^[A-Z0-9]{10,20}$/',           // Alphanumeric, 10-20 characters
            '/^[0-9]{12,18}$/',              // Numeric, 12-18 digits (common for many carriers)
            '/^[A-Z]{2}[0-9]{9}[A-Z]{2}$/',  // Two letters, 9 digits, two letters (DHL format example)
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $waybillNumber)) {
                return true;
            }
        }

        return false;
    }
}
