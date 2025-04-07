<?php
// Script to generate placeholder icons for the PWA
// This is a utility script that should be run once to create the required icon files

// Define the icon directory
$iconDir = __DIR__ . '/assets/icons';

// Create directory if it doesn't exist
if (!file_exists($iconDir)) {
    mkdir($iconDir, 0755, true);
    echo "Created icon directory: $iconDir<br>";
}

// Define the icon sizes
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

// Function to create a simple colored icon with size text
function createIcon($size, $path) {
    // Create image with the specified dimensions
    $img = imagecreatetruecolor($size, $size);
    
    // Colors
    $gold = imagecolorallocate($img, 205, 175, 86); // #cdaf56
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    
    // Fill background with gold color
    imagefill($img, 0, 0, $gold);
    
    // Add a border
    imagerectangle($img, 0, 0, $size-1, $size-1, $black);
    
    // Add text "GCSE" to the center
    $fontSize = max(4, $size / 10);
    $text = "GCSE";
    
    // Get text dimensions
    $textDimensions = imagettfbbox($fontSize, 0, "arial.ttf", $text);
    if ($textDimensions) {
        $textWidth = $textDimensions[2] - $textDimensions[0];
        $textHeight = $textDimensions[7] - $textDimensions[1];
        $textX = ($size - $textWidth) / 2;
        $textY = ($size + $textHeight) / 2;
        imagettftext($img, $fontSize, 0, $textX, $textY, $white, "arial.ttf", $text);
    } else {
        // Fallback if TTF not available
        $textWidth = strlen($text) * imagefontwidth(5);
        $textHeight = imagefontheight(5);
        $textX = ($size - $textWidth) / 2;
        $textY = ($size - $textHeight) / 2;
        imagestring($img, 5, $textX, $textY, $text, $white);
    }
    
    // Add size text at the bottom
    $sizeText = "{$size}px";
    
    if (isset($textDimensions)) {
        $sizeTextDimensions = imagettfbbox($fontSize * 0.7, 0, "arial.ttf", $sizeText);
        $sizeTextWidth = $sizeTextDimensions[2] - $sizeTextDimensions[0];
        $sizeTextX = ($size - $sizeTextWidth) / 2;
        $sizeTextY = $size - 10;
        imagettftext($img, $fontSize * 0.7, 0, $sizeTextX, $sizeTextY, $white, "arial.ttf", $sizeText);
    } else {
        $sizeTextWidth = strlen($sizeText) * imagefontwidth(3);
        $sizeTextX = ($size - $sizeTextWidth) / 2;
        $sizeTextY = $size - 15;
        imagestring($img, 3, $sizeTextX, $sizeTextY, $sizeText, $white);
    }
    
    // Output the image
    imagepng($img, $path);
    imagedestroy($img);
    
    echo "Created icon: $path<br>";
}

// Generate each icon
foreach ($sizes as $size) {
    $iconPath = "$iconDir/icon-{$size}x{$size}.png";
    createIcon($size, $iconPath);
}

echo "<br>All icons created successfully!<br>";
echo "<a href='/'>Return to Home</a>";
?>
