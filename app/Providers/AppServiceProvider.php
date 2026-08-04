<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Note;
use App\Models\Practice;
use App\Models\User;
use App\Observers\DomainTimelineObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'appointment' => Appointment::class,
            'company' => Company::class,
            'contact' => Contact::class,
            'practice' => Practice::class,
            'user' => User::class,
        ]);

        foreach ([Contact::class, Company::class, Appointment::class, Practice::class, Activity::class, Document::class, Note::class] as $model) {
            $model::observe(DomainTimelineObserver::class);
        }
    }
}
