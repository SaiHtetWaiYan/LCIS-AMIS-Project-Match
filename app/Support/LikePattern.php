<?php

namespace App\Support;

/**
 * Builds LIKE patterns from user input, so % and _ typed in a search box match literally.
 */
final class LikePattern
{
    public static function contains(string $term): string
    {
        return '%'.addcslashes($term, '\\%_').'%';
    }
}
