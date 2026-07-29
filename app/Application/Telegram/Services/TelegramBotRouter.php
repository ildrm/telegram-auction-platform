<?php

declare(strict_types=1);

namespace App\Application\Telegram\Services;

use App\Models\Auction;
use App\Models\User;
use Illuminate\Support\Facades\App;

final readonly class TelegramBotRouter
{
    public function __construct(
        private ConversationService $conversations,
        private TelegramAuctionWizard $wizard,
        private TelegramDeliveryService $delivery,
    ) {}

    /** @param array<string, mixed> $payload */
    public function route(User $user, array $payload): void
    {
        App::setLocale($user->locale);

        $message = data_get($payload, 'message');
        $callback = data_get($payload, 'callback_query');
        $chatId = data_get($message, 'chat.id') ?? data_get($callback, 'message.chat.id');

        if (! is_int($chatId)) {
            return;
        }

        if (is_array($callback)) {
            $this->callback($user, $chatId, (string) ($callback['data'] ?? ''));

            return;
        }

        if (! is_array($message)) {
            return;
        }

        $text = trim((string) ($message['text'] ?? ''));

        if ($text === '') {
            $this->delivery->sendMessage($user, $chatId, __('telegram.text_only'));

            return;
        }

        if ($text === '/cancel') {
            $this->conversations->finish($user);
            $this->delivery->sendMessage($user, $chatId, __('telegram.cancelled'));

            return;
        }

        $state = $this->conversations->activeFor($user);

        if ($state !== null) {
            $result = $this->wizard->handle($user, $state, $text);
            $this->delivery->sendMessage($user, $chatId, $result['text']);

            return;
        }

        match (strtok($text, ' ')) {
            '/start', '/help' => $this->delivery->sendMessage(
                $user,
                $chatId,
                __('telegram.welcome', ['name' => e($user->display_name)]),
                $this->mainKeyboard(),
            ),
            '/auctions' => $this->sendAuctions($user, $chatId, 1),
            '/sell' => $this->startWizard($user, $chatId),
            default => $this->delivery->sendMessage($user, $chatId, __('telegram.unknown_command')),
        };
    }

    private function startWizard(User $user, int $chatId): void
    {
        $result = $this->wizard->start($user);
        $this->delivery->sendMessage($user, $chatId, $result['text']);
    }

    private function callback(User $user, int $chatId, string $data): void
    {
        if (preg_match('/^auctions:(\d{1,6})$/', $data, $matches) === 1) {
            $this->sendAuctions($user, $chatId, max(1, (int) $matches[1]));

            return;
        }

        $this->delivery->sendMessage($user, $chatId, __('telegram.invalid_action'));
    }

    private function sendAuctions(User $user, int $chatId, int $page): void
    {
        $pageSize = max(1, min(20, (int) config('telegram.page_size')));
        $paginator = Auction::query()
            ->discoverable()
            ->orderBy('ends_at')
            ->simplePaginate($pageSize, ['id', 'title', 'slug', 'currency', 'current_price_minor', 'ends_at'], 'page', $page);

        if ($paginator->isEmpty()) {
            $this->delivery->sendMessage($user, $chatId, __('telegram.no_auctions'));

            return;
        }

        $lines = $paginator->getCollection()->map(
            fn (Auction $auction): string => __('telegram.auction_line', [
                'title' => e($auction->title),
                'price' => number_format($auction->current_price_minor),
                'currency' => e($auction->currency),
                'slug' => e($auction->slug),
            ]),
        )->implode("\n\n");

        $buttons = [];

        if ($page > 1) {
            $buttons[] = ['text' => '‹', 'callback_data' => 'auctions:'.($page - 1)];
        }

        if ($paginator->hasMorePages()) {
            $buttons[] = ['text' => '›', 'callback_data' => 'auctions:'.($page + 1)];
        }

        $keyboard = $buttons === [] ? null : ['inline_keyboard' => [$buttons]];
        $this->delivery->sendMessage($user, $chatId, $lines, $keyboard);
    }

    /** @return array<string, mixed> */
    private function mainKeyboard(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => __('telegram.buttons.auctions'), 'callback_data' => 'auctions:1'],
                ],
            ],
        ];
    }
}
