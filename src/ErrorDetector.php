<?php

namespace PiedWeb\Google;

final class ErrorDetector
{
    public static function isDetectedAsBot(string $html): bool
    {
        /* Google respond :
         * We're sorry...... but your computer or network may be sending automated queries.
         * To protect our users, we can't process your request right now.'
         */
        if (false !== strpos($html, '<title>Sorry...</title>')) {
            return true;
        }

        /* Captcha Google */
        elseif (false !== strpos($html, 'document.getElementById(\'captcha')) {
            return true;
        }

        /* RAS */
        return false;
    }
}
