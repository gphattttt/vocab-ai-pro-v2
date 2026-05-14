<?php
include 'db.php';
// Set time limit because the list is big
set_time_limit(300);

$jsonData = file_get_contents('https://raw.githubusercontent.com/fomvasss/cefr-level-checker/master/data/en.json');
$words = json_decode($jsonData, true);

foreach ($words as $item) {
    $word = $conn->real_escape_string(strtolower($item['word']));
    $level = $conn->real_escape_string(strtoupper($item['level']));
    $conn->query("INSERT IGNORE INTO word_levels (word, level) VALUES ('$word', '$level')");
}
echo "Import Complete! You can delete this file now.";
?>