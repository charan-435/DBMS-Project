<?php
/**
 * Shared utility functions used across multiple pages.
 */

// Language code → display name
function getLanguageName($code) {
    $map = [
        'hi' => 'Hindi', 'ta' => 'Tamil', 'te' => 'Telugu',
        'ml' => 'Malayalam', 'kn' => 'Kannada', 'en' => 'English',
    ];
    return $map[strtolower(trim($code))] ?? strtoupper($code);
}

// Language code → industry nickname
function getIndustryName($code) {
    $map = [
        'te' => 'Tollywood', 'ta' => 'Kollywood', 'hi' => 'Bollywood',
        'kn' => 'Sandalwood', 'ml' => 'Mollywood', 'en' => 'Hollywood',
    ];
    return $map[strtolower(trim($code))] ?? strtoupper($code);
}

// Genre → CSS class for colored badges
function getGenreClass($genre) {
    $genre = strtolower(trim($genre));
    $map = [
        'drama' => 'genre-drama', 'action' => 'genre-action',
        'comedy' => 'genre-comedy', 'romance' => 'genre-romance',
        'thriller' => 'genre-thriller', 'horror' => 'genre-horror',
        'crime' => 'genre-crime',
    ];
    return $map[$genre] ?? 'genre-default';
}

// Rating → sentiment label + CSS class
function getSentiment($rating) {
    if ($rating >= 8.5) return ['label' => 'Universal Praise',  'class' => 'sentiment-high'];
    if ($rating >= 7.5) return ['label' => 'Box Office Smash',  'class' => 'sentiment-medium'];
    if ($rating >= 7.0) return ['label' => 'Cult Status',       'class' => 'sentiment-low'];
    return ['label' => 'Mixed Reviews', 'class' => 'sentiment-low'];
}

// Format big revenue numbers into ₹ Cr / K Cr
function formatRevenue($amount) {
    $cr = $amount / 10000000;
    if ($cr >= 1000) return number_format($cr / 1000, 1) . 'K Cr';
    return number_format($cr, 1) . ' Cr';
}

// Accent colors for charts
$barColors = ['var(--accent-primary)', '#5cd6b6', '#6ea8fe', '#a68dff', '#ff8296'];
$avatarColors = [
    ['#e8a57e', '#d4845a'], ['#5cd6b6', '#3bb89a'],
    ['#6ea8fe', '#4a8ae0'], ['#a68dff', '#8565e0'],
];
?>
