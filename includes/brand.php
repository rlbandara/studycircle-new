<?php
// includes/brand.php: logo + campus photo helpers.
// Save your files here (any one of these names works for the logo):
//   assets/img/logo.png   (or logo.svg / logo.webp / logo.jpg)
//   assets/img/university.jpg   (campus photo, used as the faint background)
// If a file is missing, the site falls back to the green "S" badge / plain gradient.

function brand_asset(array $names): ?string
{
    foreach ($names as $n) {
        if (is_file(__DIR__ . '/../assets/img/' . $n)) {
            return 'assets/img/' . $n;
        }
    }
    return null;
}

// The logo: your image if you saved one, else the green "S" badge.
function brand_logo(string $extraClass = ''): string
{
    $file = brand_asset(['logo.png', 'logo.svg', 'logo.webp', 'logo.jpg']);
    if ($file) {
        return '<img class="sc-logo-img" src="' . e(url($file)) . '" alt="NSBM Green University logo">';
    }
    return '<span class="sc-logo ' . e($extraClass) . '" aria-hidden="true">S</span>';
}

// True when a campus photo has been saved (used to switch on the watermark styles).
function has_campus_photo(): bool
{
    return brand_asset(['university.jpg']) !== null;
}
