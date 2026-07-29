<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Telegram\Actions\IngestTelegramUpdateAction;
use App\Application\Telegram\Services\ConversationService;
use App\Application\Telegram\Services\TelegramAuctionWizard;
use App\Jobs\Telegram\ProcessTelegramUpdate;
use App\Models\Auction;
use App\Models\Category;
use App\Models\TelegramUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class TelegramExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_authenticates_and_ingests_each_update_once(): void
    {
        config(['telegram.webhook_secret' => 'test-webhook-secret']);
        Queue::fake();
        $payload = ['update_id' => 12345, 'message' => ['text' => '/start']];
        $headers = ['X-Telegram-Bot-Api-Secret-Token' => 'test-webhook-secret'];

        $this->postJson('/api/telegram/webhook', $payload, $headers)->assertAccepted()->assertJson(['ok' => true]);
        $this->app->make(IngestTelegramUpdateAction::class)->execute($payload);

        self::assertSame(1, TelegramUpdate::query()->count());
        Queue::assertPushed(ProcessTelegramUpdate::class, 1);
    }

    public function test_webhook_rejects_an_invalid_secret(): void
    {
        config(['telegram.webhook_secret' => 'correct-secret']);

        $this->postJson(
            '/api/telegram/webhook',
            ['update_id' => 1],
            ['X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret'],
        )->assertUnauthorized();
    }

    public function test_auction_wizard_persists_progress_and_creates_a_draft(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();
        $wizard = $this->app->make(TelegramAuctionWizard::class);
        $conversations = $this->app->make(ConversationService::class);

        self::assertFalse($wizard->start($seller)['finished']);

        foreach ([
            (string) $category->getKey(),
            'Telegram collectible',
            'A sufficiently detailed collectible description.',
            '10000',
            '500',
        ] as $answer) {
            $state = $conversations->activeFor($seller);
            self::assertNotNull($state);
            self::assertFalse($wizard->handle($seller, $state, $answer)['finished']);
        }

        $state = $conversations->activeFor($seller);
        self::assertNotNull($state);
        self::assertTrue($wizard->handle($seller, $state, '24')['finished']);
        self::assertNull($conversations->activeFor($seller));
        self::assertSame(1, Auction::query()->where('seller_id', $seller->getKey())->count());
    }
}
