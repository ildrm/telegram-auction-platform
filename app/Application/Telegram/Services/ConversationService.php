<?php

declare(strict_types=1);

namespace App\Application\Telegram\Services;

use App\Models\ConversationState;
use App\Models\User;

final class ConversationService
{
    /** @param array<string, mixed> $payload */
    public function start(
        User $user,
        string $conversation,
        string $step,
        array $payload = [],
    ): ConversationState {
        return ConversationState::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            [
                'conversation' => $conversation,
                'step' => $step,
                'payload' => $payload,
                'expires_at' => now()->addMinutes(config('telegram.conversation_ttl_minutes')),
            ],
        );
    }

    /** @param array<string, mixed> $payload */
    public function advance(ConversationState $state, string $step, array $payload): ConversationState
    {
        $state->update([
            'step' => $step,
            'payload' => $payload,
            'expires_at' => now()->addMinutes(config('telegram.conversation_ttl_minutes')),
        ]);

        return $state->refresh();
    }

    public function activeFor(User $user): ?ConversationState
    {
        $state = ConversationState::query()->whereBelongsTo($user)->first();

        if ($state !== null && $state->expires_at->isPast()) {
            $state->delete();

            return null;
        }

        return $state;
    }

    public function finish(User $user): void
    {
        ConversationState::query()->where('user_id', $user->getKey())->delete();
    }
}
