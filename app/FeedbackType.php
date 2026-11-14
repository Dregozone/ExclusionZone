<?php

namespace App;

enum FeedbackType: string
{
    case Bug = 'bug';
    case Feedback = 'feedback';

    public function label(): string
    {
        return match ($this) {
            self::Bug => __('Bug report'),
            self::Feedback => __('Feedback'),
        };
    }
}
