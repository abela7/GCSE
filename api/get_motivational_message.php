<?php
header('Content-Type: application/json');

$messages = [
    "You're making great progress!",
    "Keep pushing forward!",
    "Every small step counts!",
    "You've got this!",
    "Stay focused and determined!",
    "Your hard work will pay off!",
    "Believe in yourself!",
    "Success is built one day at a time!",
    "You're closer to your goals than yesterday!",
    "Make today count!"
];

echo json_encode([
    'success' => true,
    'message' => $messages[array_rand($messages)]
]); 