<?php
// Convert captured 2x PNGs to 1440px-wide WebP files in public/images/tour.
$in = __DIR__ . '/tour-out';
$out = 'C:/Users/ahmad/Wedding/public/images/tour';
@mkdir($out, 0777, true);

$keep = [
    'dashboard', 'guests', 'budget', 'checklist', 'seating', 'timeline',
    'website-editor', 'registry', 'marketplace', 'wedding-site', 'shop-personalizer',
];

foreach ($keep as $name) {
    $src = imagecreatefrompng("{$in}/{$name}.png");
    if (!$src) { echo "FAIL read {$name}\n"; continue; }
    $w = imagesx($src); $h = imagesy($src);
    $nw = 1440; $nh = (int) round($h * $nw / $w);
    $dst = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagewebp($dst, "{$out}/{$name}.webp", 82);
    imagedestroy($src); imagedestroy($dst);
    echo "{$name}.webp " . round(filesize("{$out}/{$name}.webp") / 1024) . "KB\n";
}
