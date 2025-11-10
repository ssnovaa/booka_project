<?php

namespace Tests\Feature\Subscriptions;

use App\Integrations\GooglePlayClient;
use App\Models\Subscription;
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

    public function testAcknowledgedTimestampPersistsAcrossReverify(): void
    {
        Carbon::setTestNow('2024-02-01 00:00:00');

        $user = User::factory()->create();

        $acknowledgedAt = Carbon::parse('2024-01-15 12:00:00');

        Subscription::create([
            'user_id'        => $user->id,
            'platform'       => 'google',
            'package_name'   => 'com.booka_app',
            'product_id'     => 'prod_monthly',
            'purchase_token' => 'persist-token-1',
            'order_id'       => 'GPA.1234-5678-9012-34567',
            'status'         => 'active',
            'started_at'     => Carbon::parse('2024-01-01 00:00:00'),
            'expires_at'     => Carbon::parse('2024-02-05 00:00:00'),
            'acknowledged_at'=> $acknowledgedAt,
        ]);

        $mock = Mockery::mock(GooglePlayClient::class);
        $mock->shouldReceive('getSubscriptionV2')
            ->once()
            ->with('persist-token-1')
            ->andReturn([
                'latestOrderId'        => 'GPA.1234-5678-9012-34567',
                'subscriptionState'    => 'SUBSCRIPTION_STATE_ACTIVE',
                'startTime'            => '2024-01-01T00:00:00Z',
                'expiryTime'           => '2024-02-05T00:00:00Z',
                'acknowledgementState' => 'ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED',
            ]);

        $service = new GooglePlayVerifier($mock);

        $service->verifyAndUpsert($user, [
            'purchaseToken' => 'persist-token-1',
            'productId'     => 'prod_monthly',
        ]);

        $subscription = Subscription::where('purchase_token', 'persist-token-1')->firstOrFail();

        $this->assertEquals($acknowledgedAt->toDateTimeString(), $subscription->acknowledged_at?->toDateTimeString());
    }
}
