<?php
include 'db.php';
session_start();

// Security: Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $word = $_POST['word'];
    $ipa = $_POST['ipa'];
    $form = $_POST['form'];
    $level = $_POST['level'];
    $defEn = $_POST['defEn'];
    $defVi = $_POST['defVi'];
    $sentence = $_POST['sentence']; // Added for AI Context
    $user_id = $_SESSION['user_id'];

    // Handle Category Logic
    $category_id = ($_POST['category_id'] == 'null' || empty($_POST['category_id'])) ? null : $_POST['category_id'];
    $new_cat_name = trim($_POST['new_category_name'] ?? '');

    // --- ON-THE-FLY CATEGORY CREATION ---
    if (!empty($new_cat_name)) {
        // Check if this category name already exists for this user
        $check_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND user_id = ?");
        $check_stmt->bind_param("si", $new_cat_name, $user_id);
        $check_stmt->execute();
        $res = $check_stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $category_id = $row['id']; // Use existing ID
        } else {
            // Create new category record
            $create_stmt = $conn->prepare("INSERT INTO categories (user_id, name) VALUES (?, ?)");
            $create_stmt->bind_param("is", $user_id, $new_cat_name);
            $create_stmt->execute();
            $category_id = $create_stmt->insert_id; // Use the freshly created ID
        }
    }

    // --- MAIN UPDATE QUERY ---
    // Update the vocabulary entry including the example_sentence
    $sql = "UPDATE vocabularies 
            SET word = ?, 
                ipa = ?, 
                word_form = ?, 
                level = ?, 
                definition_en = ?, 
                definition_vi = ?, 
                example_sentence = ?, 
                category_id = ? 
            WHERE id = ? AND user_id = ?";

    $stmt = $conn->prepare($sql);

    /**
     * Types: 
     * sssssss = 7 strings (word, ipa, form, level, defEn, defVi, sentence)
     * iii = 3 integers (category_id, id, user_id)
     */
    $stmt->bind_param("sssssssiii", 
        $word, 
        $ipa, 
        $form, 
        $level, 
        $defEn, 
        $defVi, 
        $sentence, 
        $category_id, 
        $id, 
        $user_id
    );

    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>