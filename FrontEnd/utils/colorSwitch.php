<?php

function normalizeHexColor(string $color): string
{
    $color = trim(strtolower($color));

    // Convert short hex (#fff → #ffffff)
    if (preg_match('/^#([a-f0-9]{3})$/', $color, $m)) {
        return '#' . $m[1][0] . $m[1][0]
                   . $m[1][1] . $m[1][1]
                   . $m[1][2] . $m[1][2];
    }

    return $color;
}

function isWhiteColor(string $color): bool
{
    $color = normalizeHexColor($color);

    return in_array($color, [
        '#ffffff',
        'white',
        'rgb(255,255,255)'
    ], true);
}

function resolveSecondaryColor(string $primary, string $secondary): string
{
    return isWhiteColor($secondary)
        ? $primary
        : $secondary;
}
