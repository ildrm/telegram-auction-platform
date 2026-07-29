<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Auctions\Actions\SubmitAuctionForApprovalAction;
use App\Application\Moderation\Actions\ResolveReportAction;
use App\Application\Moderation\Actions\ReviewAuctionAction;
use App\Application\Moderation\Actions\SubmitReportAction;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Moderation\Enums\ModerationDecision;
use App\Domain\Moderation\Enums\ReportStatus;
use App\Models\Auction;
use App\Models\AuctionModeration;
use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ModerationAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_submission_and_moderator_approval_are_audited(): void
    {
        $seller = User::factory()->seller()->create();
        $moderator = User::factory()->moderator()->create();
        $auction = Auction::factory()->for($seller, 'seller')->create(['starts_at' => now()->addHour()]);

        $submitted = $this->app->make(SubmitAuctionForApprovalAction::class)->execute($auction, $seller);
        $approved = $this->app->make(ReviewAuctionAction::class)->execute(
            $submitted,
            $moderator,
            ModerationDecision::Approved,
            null,
        );

        self::assertSame(AuctionStatus::Scheduled, $approved->status);
        self::assertSame($moderator->getKey(), $approved->approved_by);
        self::assertSame(1, AuctionModeration::query()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'auction.approved']);
    }

    public function test_ordinary_user_cannot_moderate_an_auction(): void
    {
        $this->expectException(AuthorizationException::class);

        $this->app->make(ReviewAuctionAction::class)->execute(
            Auction::factory()->create(['status' => AuctionStatus::PendingApproval]),
            User::factory()->create(),
            ModerationDecision::Approved,
            null,
        );
    }

    public function test_report_can_be_submitted_once_and_resolved_by_moderator(): void
    {
        $reporter = User::factory()->create();
        $moderator = User::factory()->moderator()->create();
        $auction = Auction::factory()->active()->create();
        $submit = $this->app->make(SubmitReportAction::class);
        $report = $submit->execute($reporter, $auction, 'Fraud', 'The listing evidence appears inconsistent.');

        $resolved = $this->app->make(ResolveReportAction::class)->execute(
            $moderator,
            $report,
            ReportStatus::Resolved,
            'Evidence reviewed and listing actioned.',
        );

        self::assertSame(ReportStatus::Resolved, $resolved->status);
        self::assertSame(1, Report::query()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.resolved']);
    }
}
