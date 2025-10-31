<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Party\Party;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatNotificationTest extends TestCase
{
    /**
     * Test sending chat notification endpoint exists
     */
    public function test_chat_notification_endpoint_exists()
    {
        $user = User::first();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/chat/send-notification', [
                'order_id' => 1,
                'sender_name' => 'Test User',
                'sender_type' => 'driver',
                'message' => 'Test message',
            ]);

        // Should not return 404
        $this->assertNotEquals(404, $response->status());
    }

    /**
     * Test validation for required fields
     */
ca }
}
pe']);
   er_tysend(['dationErrorssertJsonValinse->as    $respo22);
    atus(4->assertSt$response
        ]);
           sage',
 es=> 'Test m'message'         ',
        petynvalid_type' => 'i   'sender_          ',
   Test User_name' => '     'sender
         r_id' => 1, 'orde            [
   ', otification/send-natchn('/api/postJso  ->
        'sanctum')er,($usingAscts->ae = $thi    $respons
st();
    ser::firer = Uus    $ {
    valid()
   e_must_be__sender_typestnction tublic fu  p */
  ion
    atr_type validdeTest sen
     *

    /**    }age']);
'mess', sender_typeame', 'er_nd', 'sends(['order_iornErriolidatVatJsonserponse->as       $res
 tus(422);ssertStase->a  $respon;

      on', [])catiifit/send-nothaapi/cJson('/st  ->po
          um')nct'sar, s($uses->actingA $thi$response =
 );
 first(= User::r $use        s()
    {
res_fieldtion_req_chat_notifiunction test  public f
