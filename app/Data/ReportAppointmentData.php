<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AppointmentOutcome;
use Carbon\CarbonImmutable;

final readonly class ReportAppointmentData
{
    public function __construct(
        public bool $occurred,
        public ?AppointmentOutcome $outcome = null,
        public ?string $emergedNeed = null,
        public ?bool $prospectInterested = null,
        public bool $convertToClient = false,
        public bool $openPractice = false,
        public ?int $practiceTypeId = null,
        public bool $followUpRequired = false,
        public ?CarbonImmutable $nextContactAt = null,
        public ?string $negativeReason = null,
        public ?string $notes = null,
    ) {}
}
