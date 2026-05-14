<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'csv';

// Fetch all words for the user
$sql = "SELECT v.*, c.name AS category_name 
        FROM vocabularies v 
        LEFT JOIN categories c ON v.category_id = c.id 
        WHERE v.user_id = $user_id 
        ORDER BY v.word ASC";

$result = $conn->query($sql);
$filename = "Vocab_Vault_" . date('Y-m-d');

// --- CSV EXPORT LOGIC ---
if ($type === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel to display Vietnamese characters correctly
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Column Headers
    fputcsv($output, ['Word', 'IPA', 'Form', 'Level', 'English Definition', 'Vietnamese Definition', 'Example Sentence', 'Category']);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['word'],
            $row['ipa'],
            $row['word_form'],
            $row['level'],
            $row['definition_en'],
            $row['definition_vi'],
            $row['example_sentence'],
            $row['category_name'] ?? 'Uncategorized'
        ]);
    }
    fclose($output);
    exit();
}

// --- DOCX (HTML-to-Word) EXPORT LOGIC ---
if ($type === 'docx') {
    header("Content-type: application/vnd.ms-word");
    header("Content-Disposition: attachment; filename=\"" . $filename . ".doc\"");
    ?>
    <html xmlns:office="urn:schemas-microsoft-com:office:office" xmlns:word="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
    <head><meta charset="utf-8"></head>
    <body>
        <h1>My Vocabulary Vault</h1>
        <p>Generated on: <?php echo date('F j, Y'); ?></p>
        <table border="1" style="border-collapse: collapse; width: 100%;">
            <tr style="background-color: #f1f5f9;">
                <th>Word</th>
                <th>IPA</th>
                <th>Level</th>
                <th>Definition</th>
                <th>Vietnamese</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo $row['word']; ?></strong> (<?php echo $row['word_form']; ?>)</td>
                <td><?php echo $row['ipa']; ?></td>
                <td><?php echo $row['level']; ?></td>
                <td><?php echo $row['definition_en']; ?></td>
                <td><?php echo $row['definition_vi']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </body>
    </html>
    <?php
    exit();
}
?>