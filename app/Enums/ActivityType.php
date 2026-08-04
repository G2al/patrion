<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityType: string
{
    case PhoneCall = 'phone_call';
    case Email = 'email';
    case WhatsApp = 'whatsapp';
    case DocumentRequest = 'document_request';
    case FollowUp = 'follow_up';
    case Meeting = 'meeting';
    case PracticeReview = 'practice_review';
    case Reminder = 'reminder';
    case General = 'general';
}
