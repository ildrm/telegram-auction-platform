<?php

declare(strict_types=1);

namespace App\Application\Telegram\Services;

use App\Application\Auctions\Actions\CreateAuctionAction;
use App\Application\Auctions\Data\CreateAuctionData;
use App\Domain\Auctions\Enums\AuctionType;
use App\Models\Category;
use App\Models\ConversationState;
use App\Models\User;
use Carbon\CarbonImmutable;

final readonly class TelegramAuctionWizard
{
    public function __construct(
        private ConversationService $conversations,
        private CreateAuctionAction $createAuction,
    ) {}

    /** @return array{text: string, finished: bool} */
    public function start(User $user): array
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);

        if ($categories->isEmpty()) {
            return ['text' => __('telegram.no_categories'), 'finished' => true];
        }

        $this->conversations->start($user, 'create_auction', 'category_id');
        $choices = $categories->map(
            fn (Category $category): string => "{$category->id} — ".e($category->name),
        )->implode("\n");

        return [
            'text' => __('telegram.wizard.category', ['categories' => $choices]),
            'finished' => false,
        ];
    }

    /** @return array{text: string, finished: bool} */
    public function handle(User $user, ConversationState $state, string $text): array
    {
        $payload = $state->payload ?? [];

        return match ($state->step) {
            'category_id' => $this->category($state, $payload, $text),
            'title' => $this->title($state, $payload, $text),
            'description' => $this->description($state, $payload, $text),
            'starting_price_minor' => $this->positiveInteger(
                $state,
                $payload,
                $text,
                'starting_price_minor',
                'minimum_increment_minor',
                'telegram.wizard.minimum_increment',
            ),
            'minimum_increment_minor' => $this->positiveInteger(
                $state,
                $payload,
                $text,
                'minimum_increment_minor',
                'duration_hours',
                'telegram.wizard.duration',
            ),
            'duration_hours' => $this->finish($user, $payload, $text),
            default => $this->cancelInvalidState($user),
        };
    }

    /** @param array<string, mixed> $payload */
    private function category(ConversationState $state, array $payload, string $text): array
    {
        $categoryId = filter_var(trim($text), FILTER_VALIDATE_INT);
        $exists = $categoryId !== false
            && Category::query()->whereKey($categoryId)->where('is_active', true)->exists();

        if (! $exists) {
            return ['text' => __('telegram.wizard.invalid_category'), 'finished' => false];
        }

        $payload['category_id'] = $categoryId;
        $this->conversations->advance($state, 'title', $payload);

        return ['text' => __('telegram.wizard.title'), 'finished' => false];
    }

    /** @param array<string, mixed> $payload */
    private function title(ConversationState $state, array $payload, string $text): array
    {
        $text = trim($text);

        if (mb_strlen($text) < 3 || mb_strlen($text) > 160) {
            return ['text' => __('telegram.wizard.invalid_title'), 'finished' => false];
        }

        $payload['title'] = $text;
        $this->conversations->advance($state, 'description', $payload);

        return ['text' => __('telegram.wizard.description'), 'finished' => false];
    }

    /** @param array<string, mixed> $payload */
    private function description(ConversationState $state, array $payload, string $text): array
    {
        $text = trim($text);

        if (mb_strlen($text) < 10 || mb_strlen($text) > 20_000) {
            return ['text' => __('telegram.wizard.invalid_description'), 'finished' => false];
        }

        $payload['description'] = $text;
        $this->conversations->advance($state, 'starting_price_minor', $payload);

        return ['text' => __('telegram.wizard.starting_price'), 'finished' => false];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{text: string, finished: bool}
     */
    private function positiveInteger(
        ConversationState $state,
        array $payload,
        string $text,
        string $field,
        string $nextStep,
        string $nextPrompt,
    ): array {
        $value = filter_var(trim($text), FILTER_VALIDATE_INT);

        if ($value === false || $value < 1) {
            return ['text' => __('telegram.wizard.invalid_amount'), 'finished' => false];
        }

        $payload[$field] = $value;
        $this->conversations->advance($state, $nextStep, $payload);

        return ['text' => __($nextPrompt), 'finished' => false];
    }

    /** @param array<string, mixed> $payload */
    private function finish(User $user, array $payload, string $text): array
    {
        $durationHours = filter_var(trim($text), FILTER_VALIDATE_INT);

        if ($durationHours === false || $durationHours < 1 || $durationHours > 720) {
            return ['text' => __('telegram.wizard.invalid_duration'), 'finished' => false];
        }

        $startsAt = CarbonImmutable::now('UTC');
        $auction = $this->createAuction->execute(
            seller: $user,
            data: new CreateAuctionData(
                categoryId: $payload['category_id'],
                title: $payload['title'],
                description: $payload['description'],
                type: AuctionType::English,
                currency: 'USD',
                startingPriceMinor: $payload['starting_price_minor'],
                minimumIncrementMinor: $payload['minimum_increment_minor'],
                reservePriceMinor: null,
                startsAt: $startsAt,
                endsAt: $startsAt->addHours($durationHours),
                isPrivate: false,
            ),
        );

        $this->conversations->finish($user);

        return [
            'text' => __('telegram.wizard.created', [
                'title' => e($auction->title),
                'slug' => e($auction->slug),
            ]),
            'finished' => true,
        ];
    }

    /** @return array{text: string, finished: bool} */
    private function cancelInvalidState(User $user): array
    {
        $this->conversations->finish($user);

        return ['text' => __('telegram.wizard.expired'), 'finished' => true];
    }
}
