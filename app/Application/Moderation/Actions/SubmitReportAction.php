<?php

declare(strict_types=1);

namespace App\Application\Moderation\Actions;

use App\Application\Audit\AuditLogger;
use App\Domain\Moderation\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class SubmitReportAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function execute(
        User $reporter,
        Model $subject,
        string $reason,
        string $description,
    ): Report {
        if (mb_strlen(trim($reason)) < 3 || mb_strlen(trim($description)) < 10) {
            throw ValidationException::withMessages([
                'description' => [__('moderation.invalid_report')],
            ]);
        }

        return DB::transaction(function () use ($reporter, $subject, $reason, $description): Report {
            $duplicate = Report::query()
                ->where('reporter_id', $reporter->getKey())
                ->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', $subject->getKey())
                ->whereIn('status', [ReportStatus::Open, ReportStatus::Reviewing])
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'subject' => [__('moderation.duplicate_report')],
                ]);
            }

            $report = Report::query()->create([
                'reporter_id' => $reporter->getKey(),
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'reason' => trim($reason),
                'description' => trim($description),
                'status' => ReportStatus::Open,
            ]);

            $this->auditLogger->record(
                actor: $reporter,
                action: 'report.submitted',
                subject: $report,
                before: null,
                after: ['reason' => $report->reason],
                metadata: [
                    'subject_type' => $subject->getMorphClass(),
                    'subject_id' => $subject->getKey(),
                ],
            );

            return $report;
        });
    }
}
