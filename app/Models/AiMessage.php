<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class AiMessage extends Model
{
    protected $guarded = ['id'];

    protected $touches = ['conversation'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    public function renderedContent(): HtmlString
    {
        return new HtmlString(Str::markdown($this->content, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }
}
