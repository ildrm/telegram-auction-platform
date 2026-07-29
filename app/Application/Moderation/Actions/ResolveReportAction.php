<?php

declare(strict_types=1);

namespace App\Application\Moderation\Actions;

use App\Application\Audit\AuditLogger;
use App\Domain\Moderation\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class ResolveReportAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function execute(
        User $moderator,
        Report $report,
        ReportStatus $status,
        string $resolution,
    ): Report {
        if (! $moderator->hasPermission('report.manage')) {
            throw new AuthorizationException;
        }

        if (! in_array($status, [ReportStatus::Resolved, ReportStatus::Dismissed], true)) {
            throw new \DomainException('Reports can only be resolved or dismissed.');
        }

        return DB::transaction(function () use ($moderator, $report, $status, $resolution): Report {
            /** @var Report $locked */
            $locked = Report::query()->lockForUpdate()->findOrFail($report->getKey());
            $before = ['status' => $locked->status->value];
            $locked->update([
                'status' => $status,
                'resolved_by' => $moderator->getKey(),
                'resolution' => trim($resolution),
                'resolved_at' => now(),
            ]);

            $this->auditLogger->record(
                actor: $moderator,
                action: 'report.resolved',
                subject: $locked,
                before: $before,
                after: ['status' => $status->value],
                metadata: ['resolution' => trim($resolution)],
            );

            return $locked->refresh();
        });
    }
}
