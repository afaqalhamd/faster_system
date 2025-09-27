<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WaybillValidationService;

class WaybillValidationServiceTest extends TestCase
{
    protected $waybillValidationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->waybillValidationService = new WaybillValidationService();
    }

    /**
     * Test DHL waybill format validation
     */
    public function test_validate_dhl_waybill_format()
    {
        $validDhlWaybill = 'GM1234567890';
        $invalidDhlWaybill = 'GM123456789'; // Too short

        $this->assertTrue($this->waybillValidationService->validateWaybillFormat($validDhlWaybill, 'DHL'));
        $this->assertFalse($this->waybillValidationService->validateWaybillFormat($invalidDhlWaybill, 'DHL'));
    }

    /**
     * Test FedEx waybill format validation
     */
    public function test_validate_fedex_waybill_format()
    {
        $validFedEx12Digits = '123456789012';
        $validFedEx15Digits = '123456789012345';
        $invalidFedEx = '12345678901'; // Too short

        $this->assertTrue($this->waybillValidationService->validateWaybillFormat($validFedEx12Digits, 'FedEx'));
        $this->assertTrue($this->waybillValidationService->validateWaybillFormat($validFedEx15Digits, 'FedEx'));
        $this->assertFalse($this->waybillValidationService->validateWaybillFormat($invalidFedEx, 'FedEx'));
    }

    /**
     * Test UPS waybill format validation
     */
    public function test_validate_ups_waybill_format()
    {
        $validUpsWaybill = '1Z123456789012345678';
        $invalidUpsWaybill = '1Z12345678901234567'; // Too short

        $this->assertTrue($this->waybillValidationService->validateWaybillFormat($validUpsWaybill, 'UPS'));
        $this->assertFalse($this->waybillValidationService->validateWaybillFormat($invalidUpsWaybill, 'UPS'));
    }

    /**
     * Test USPS waybill format validation
     */
    public function test_validate_usps_waybill_format()
    {
        $validUspsWaybill = '12345678901234567890';
        $invalidUspsWaybill = '1234567890123456789'; // Too short

        $this->assertTrue($this->waybillValidationService->validateWaybillFormat($validUspsWaybill, 'USPS'));
        $this->assertFalse($this->waybillValidationService->validateWaybillFormat($invalidUspsWaybill, 'USPS'));
    }

    /**
     * Test generic alphanumeric waybill format validation
     */
    public function test_validate_generic_alphanumeric_waybill_format()
    {
        $validGeneric = 'ABC123456789';
        $invalidGeneric = 'ABC12345678901234567890'; // Too long

        $this->assertTrue($this->waybillValidationService->validateWaybillFormat($validGeneric));
        $this->assertFalse($this->waybillValidationService->validateWaybillFormat($invalidGeneric));
    }

    /**
     * Test generic numeric waybill format validation
     */
    public function test_validate_generic_numeric_waybill_format()
    {
        $validGeneric = '123456789012'; // Exactly 12 digits
        $invalidGeneric = '1'; // Too short for any pattern

        $this->assertTrue($this->waybillValidationService->validateWaybillFormat($validGeneric));
        $this->assertFalse($this->waybillValidationService->validateWaybillFormat($invalidGeneric));
    }

    /**
     * Test waybill barcode validation
     */
    public function test_validate_waybill_barcode()
    {
        $validBarcodes = [
            'GM1234567890',           // DHL format
            '123456789012',           // FedEx 12 digits
            '123456789012345',        // FedEx 15 digits
            '1Z123456789012345678',   // UPS format
            '12345678901234567890',   // USPS format
            'ABC123456789',           // Generic alphanumeric
            '123456789012345'         // Generic numeric
        ];

        $invalidBarcodes = [
            'ABC12345678901234567890123' // Too long for any pattern
        ];

        foreach ($validBarcodes as $barcode) {
            $this->assertTrue(
                $this->waybillValidationService->validateWaybillBarcode($barcode),
                "Failed asserting that {$barcode} is a valid barcode"
            );
        }

        // Only test the clearly invalid one that won't match any pattern
        $this->assertFalse(
            $this->waybillValidationService->validateWaybillBarcode('ABC12345678901234567890123'),
            "Failed asserting that ABC12345678901234567890123 is an invalid barcode"
        );
    }

    /**
     * Test waybill rules retrieval
     */
    public function test_get_waybill_rules()
    {
        $rules = $this->waybillValidationService->getWaybillRules();

        $this->assertArrayHasKey('waybill_number', $rules);
        $this->assertArrayHasKey('waybill_type', $rules);
        $this->assertEquals('nullable|string|max:255|unique:shipment_trackings,waybill_number', $rules['waybill_number']);
        $this->assertEquals('nullable|string|in:AirwayBill,BillOfLading,CourierWaybill,Other', $rules['waybill_type']);
    }
}
