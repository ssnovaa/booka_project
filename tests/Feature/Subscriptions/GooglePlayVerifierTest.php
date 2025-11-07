<?php

namespace Tests\Feature\Subscriptions;

use App\Integrations\GooglePlayClient;
use App\Models\User;
use App\Services\Subscriptions\GooglePlayVerifier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GooglePlayVerifierTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();
        parent::tearDown();
    }

    public function testGraceStateKeepsRenewedAtNullAndMarksUserAsPaid(): void
    {
        Carbon::setTestNow('2024-01-01 00:00:00');

        $user = User::factory()->create([
            'is_paid'    => false,
            'paid_until' => null,
        ]);

        $mock = Mockery::mock(GooglePlayClient::class);
        $mock->shouldReceive('getSubscriptionV2')
            ->once()
            ->with('purchase-token-1')
            ->andReturn([
                'latestOrderId'      => 'GPA.1234-5678-9012-34567',
                'subscriptionState'  => 'SUBSCRIPTION_STATE_IN_GRACE_PERIOD',
                'startTime'          => '2024-01-01T00:00:00Z',
                'lineItems'          => [
                    [
                        'expiryTime' => '2024-01-08T00:00:00Z',
                    ],
                ],
                'acknowledgementState' => 'ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED',
                'regionCode'           => 'UA',
            ]);

        $service = new GooglePlayVerifier($mock);

        $subscription = $service->verifyAndUpsert($user, [
            'purchaseToken' => 'purchase-token-1',
            'productId'     => 'prod_monthly',
            'packageName'   => 'com.booka_app',
        ]);

        $subscription->refresh();
        $user->refresh();

        $this->assertSame('grace', $subscription->status);
        $this->assertNull($subscription->renewed_at);
        $this->assertTrue($user->is_paid);
        $this->assertEquals('2024-01-08 00:00:00', $user->paid_until?->format('Y-m-d H:i:s'));

        $this->assertDatabaseHas('subscriptions', [
            'purchase_token' => 'purchase-token-1',
            'status'         => 'grace',
        ]);
    }
}
