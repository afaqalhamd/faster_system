<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Payment\SaleOrderPaymentController;
use App\Services\PaymentTransactionService;
use App\Services\AccountTransactionService;
use App\Services\PartyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeliveryPaymentFilterTest extends TestCase
{
    /** @test */
    public function test_controller_methods_exist()
    {
        // Create a partial mock of the controller to avoid constructor issues
        $controller = $this->getMockBuilder(SaleOrderPaymentController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        // Check if the isCarrierUser method exists
        $this->assertTrue(method_exists($controller, 'isCarrierUser'));

        // Check if the applyCarrierFilter method exists
        $this->assertTrue(method_exists($controller, 'applyCarrierFilter'));
    }

    /** @test */
    public function test_javascript_file_has_correct_base_url_usage()
    {
        // Read the JavaScript file
        $jsContent = file_get_contents(base_path('public/custom/js/sale/sale-order-payment-list.js'));

        // Check that baseURL is not used (should use $('#base_url').val() instead)
        $this->assertStringNotContainsString('baseURL+', $jsContent);

        // Check that the correct base URL method is used
        $this->assertStringContainsString("$('#base_url').val()+'/transaction/sale-order-payment/datatable-list'", $jsContent);
        $this->assertStringContainsString("$('#base_url').val() + '/payment/sale-order/delete/'", $jsContent);
    }
}
