<?php

namespace App\Observers;

use App\Enums\ActivityEvent;
use App\Models\Comment;
use App\Services\ActivityLogger;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        ActivityLogger::log(ActivityEvent::CommentAdded, $comment, $comment->body);

        $comment->opportunity?->forceFill(['last_contact_at' => today()])->saveQuietly();
    }
}
