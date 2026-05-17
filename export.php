<?php
// =========================================================
// export.php
// Vocab AI Pro - Export Vocabulary
//
// Chức năng:
// - Xuất CSV
// - Xuất DOCX
// - Group từ vựng theo category
// - Dịch word_form sang tiếng Việt khi export
//
// Lưu ý:
// - Database vẫn lưu word_form bằng tiếng Anh: noun, verb...
// - Khi export mới đổi sang tiếng Việt: Danh từ, Động từ...
// =========================================================

// Nạp file kết nối database
include 'db.php';

// Khởi động session để lấy user_id đang đăng nhập
session_start();

// Kiểm tra user đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

// Lấy user_id từ session
$user_id = (int) $_SESSION['user_id'];

// Nhận loại file cần export: csv hoặc docx
$type = $_GET['type'] ?? ($_GET['format'] ?? 'csv');

// Tạo tên file export theo ngày hiện tại
$filename = "Vocab_AI_Pro_" . date('Y-m-d');

// =========================================================
// QUERY LẤY VOCABULARY
// =========================================================

// Query lấy toàn bộ từ vựng của user, kèm tên category
// JOIN thêm c.user_id để tránh lấy nhầm category của user khác
$sql = "
    SELECT 
        v.*,
        COALESCE(c.name, 'Uncategorized') AS category_name
    FROM vocabularies v
    LEFT JOIN categories c 
        ON v.category_id = c.id 
        AND c.user_id = v.user_id
    WHERE v.user_id = ?
    ORDER BY 
        CASE WHEN c.name IS NULL THEN 1 ELSE 0 END,
        c.name ASC,
        v.word ASC
";

// Chuẩn bị query để tránh SQL injection
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Gắn user_id vào query
$stmt->bind_param("i", $user_id);

// Chạy query
$stmt->execute();

// Lấy kết quả query
$result = $stmt->get_result();

// Mảng chứa dữ liệu đã group theo category
$groupedWords = [];

// Đưa từng dòng dữ liệu vào đúng category
while ($row = $result->fetch_assoc()) {
    $categoryName = $row['category_name'] ?: 'Uncategorized';

    if (!isset($groupedWords[$categoryName])) {
        $groupedWords[$categoryName] = [];
    }

    $groupedWords[$categoryName][] = $row;
}

$stmt->close();

// =========================================================
// HELPER FUNCTIONS
// =========================================================

/**
 * Hàm làm sạch text trước khi xuất file.
 * Giúp tránh lỗi khi dữ liệu bị null hoặc có ký tự HTML.
 */
function cleanText($value) {
    return trim(
        html_entity_decode(
            strip_tags((string) ($value ?? '')),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

/**
 * Chuyển word form từ tiếng Anh sang tiếng Việt khi export.
 * Database vẫn nên lưu tiếng Anh để dữ liệu thống nhất.
 */
function translateWordFormToVi($wordForm) {
    $form = strtolower(trim((string) ($wordForm ?? '')));

    $map = [
        'noun' => 'Danh từ',
        'verb' => 'Động từ',
        'adjective' => 'Tính từ',
        'adj' => 'Tính từ',
        'adverb' => 'Trạng từ',
        'adv' => 'Trạng từ',
        'phrasal verb' => 'Cụm động từ',
        'idiom' => 'Thành ngữ',
        'collocation' => 'Cụm từ',
        'phrase' => 'Cụm từ',
        'unknown' => 'Không rõ'
    ];

    if ($form === '') {
        return '';
    }

    return $map[$form] ?? cleanText($wordForm);
}

/**
 * Helper thêm text an toàn vào PHPWord.
 * Tránh lỗi nếu value bị null.
 */
function safeDocText($value) {
    return cleanText($value);
}

// =========================================================
// EXPORT CSV
// =========================================================

/**
 * Hàm xuất CSV:
 * - Group theo category
 * - Có tiêu đề category
 * - Có dòng trống giữa các category
 * - Có BOM UTF-8 để Excel đọc tiếng Việt tốt hơn
 */
function exportCsvByCategory($groupedWords, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

    // Mở output stream để ghi trực tiếp ra file tải xuống
    $output = fopen('php://output', 'w');

    // Ghi BOM UTF-8 để tránh lỗi font tiếng Việt trong Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Ghi tiêu đề chính của file
    fputcsv($output, ['Vocab AI Pro - Vocabulary Export']);
    fputcsv($output, ['Generated on', date('F j, Y')]);
    fputcsv($output, []);

    // Duyệt từng category
    foreach ($groupedWords as $categoryName => $words) {
        // Ghi tên category
        fputcsv($output, ['Category:', cleanText($categoryName)]);
        fputcsv($output, ['Total words:', count($words)]);

        // Ghi header cho bảng từ vựng trong category
        fputcsv($output, [
            'Word',
            'IPA',
            'Form',
            'Level',
            'English Definition',
            'Vietnamese Definition',
            'Example Sentence',
            'Nuance',
            'Collocations'
        ]);

        // Ghi từng từ vựng
        foreach ($words as $row) {
            fputcsv($output, [
                cleanText($row['word'] ?? ''),
                cleanText($row['ipa'] ?? ''),
                translateWordFormToVi($row['word_form'] ?? ''),
                cleanText($row['level'] ?? ''),
                cleanText($row['definition_en'] ?? ''),
                cleanText($row['definition_vi'] ?? ''),
                cleanText($row['example_sentence'] ?? ''),
                cleanText($row['nuance'] ?? ''),
                cleanText($row['collocations'] ?? '')
            ]);
        }

        // Thêm dòng trống giữa các category
        fputcsv($output, []);
        fputcsv($output, []);
    }

    // Đóng output stream
    fclose($output);

    // Dừng script sau khi xuất file
    exit();
}

// =========================================================
// EXPORT DOCX
// =========================================================

/**
 * Hàm xuất DOCX bằng PHPWord:
 * - File .docx chuẩn
 * - Group từ vựng theo category
 * - Có heading, thống kê, bảng
 * - Hỗ trợ tiếng Việt bằng UTF-8
 */
function exportDocxByCategory($groupedWords, $filename) {
    // Nạp Composer autoload để dùng PHPWord
    require_once __DIR__ . '/vendor/autoload.php';

    // Tạo document PHPWord
    $phpWord = new \PhpOffice\PhpWord\PhpWord();

    // Thiết lập font mặc định cho toàn bộ document
    $phpWord->setDefaultFontName('Arial');
    $phpWord->setDefaultFontSize(10);

    // Style cho tiêu đề chính
    $phpWord->addTitleStyle(1, [
        'bold' => true,
        'size' => 22,
        'color' => '1E293B'
    ]);

    // Style cho heading category
    $phpWord->addTitleStyle(2, [
        'bold' => true,
        'size' => 16,
        'color' => '10B981'
    ]);

    // Style cho bảng
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => 'CBD5E1',
        'cellMargin' => 80,
        'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER
    ];

    // Style cho dòng header của bảng
    $headerCellStyle = [
        'bgColor' => 'E2E8F0',
        'valign' => 'center'
    ];

    // Style cho ô thường
    $normalCellStyle = [
        'valign' => 'top'
    ];

    // Style chữ header
    $headerFontStyle = [
        'bold' => true,
        'color' => '1E293B'
    ];

    // Đăng ký style bảng với PHPWord
    $phpWord->addTableStyle('VocabTable', $tableStyle);

    // Tạo section mới trong file DOCX
    $section = $phpWord->addSection([
        'marginTop' => 900,
        'marginRight' => 700,
        'marginBottom' => 900,
        'marginLeft' => 700
    ]);

    // Thêm tiêu đề chính
    $section->addTitle('My Vocabulary Vault', 1);

    // Thêm ngày xuất file
    $section->addText('Generated on: ' . date('F j, Y'), [
        'italic' => true,
        'color' => '64748B'
    ]);

    // Tính tổng số từ
    $totalWords = 0;

    foreach ($groupedWords as $words) {
        $totalWords += count($words);
    }

    // Thêm thống kê tổng quan
    $section->addText('Total categories: ' . count($groupedWords));
    $section->addText('Total words: ' . $totalWords);

    // Thêm khoảng cách
    $section->addTextBreak(1);

    // Thêm mục lục category đơn giản
    $section->addText('Categories Overview', [
        'bold' => true,
        'size' => 13,
        'color' => '1E293B'
    ]);

    foreach ($groupedWords as $categoryName => $words) {
        $section->addText(
            '• ' . cleanText($categoryName) . ' — ' . count($words) . ' words'
        );
    }

    // Thêm khoảng cách trước phần bảng
    $section->addTextBreak(1);

    // Duyệt từng category để tạo bảng riêng
    foreach ($groupedWords as $categoryName => $words) {
        // Thêm heading category
        $section->addTitle(
            cleanText($categoryName) . ' (' . count($words) . ' words)',
            2
        );

        // Tạo bảng từ vựng
        $table = $section->addTable('VocabTable');

        // Tạo dòng header
        $table->addRow();

        // Thêm các cột header
        $table->addCell(2200, $headerCellStyle)->addText('Word', $headerFontStyle);
        $table->addCell(1400, $headerCellStyle)->addText('IPA', $headerFontStyle);
        $table->addCell(1000, $headerCellStyle)->addText('Level', $headerFontStyle);
        $table->addCell(2800, $headerCellStyle)->addText('English Definition', $headerFontStyle);
        $table->addCell(2800, $headerCellStyle)->addText('Vietnamese', $headerFontStyle);
        $table->addCell(3200, $headerCellStyle)->addText('Example / Notes', $headerFontStyle);

        // Thêm từng từ vựng vào bảng
        foreach ($words as $row) {
            $table->addRow();

            // =================================================
            // Cột Word: từ vựng + word form tiếng Việt
            // =================================================

            $wordCell = $table->addCell(2200, $normalCellStyle);

            $wordCell->addText(safeDocText($row['word'] ?? ''), [
                'bold' => true,
                'color' => '1E293B'
            ]);

            // Chuyển word form sang tiếng Việt khi export DOCX
            $wordFormVi = translateWordFormToVi($row['word_form'] ?? '');

            // Chỉ hiển thị word form nếu có dữ liệu
            if ($wordFormVi !== '') {
                $wordCell->addText($wordFormVi, [
                    'italic' => true,
                    'size' => 9,
                    'color' => '64748B'
                ]);
            }

            // =================================================
            // Cột IPA
            // =================================================

            $table->addCell(1400, $normalCellStyle)->addText(
                safeDocText($row['ipa'] ?? '')
            );

            // =================================================
            // Cột level
            // =================================================

            $table->addCell(1000, $normalCellStyle)->addText(
                safeDocText($row['level'] ?? ''),
                [
                    'bold' => true,
                    'color' => '10B981'
                ]
            );

            // =================================================
            // Cột định nghĩa tiếng Anh
            // =================================================

            $table->addCell(2800, $normalCellStyle)->addText(
                safeDocText($row['definition_en'] ?? '')
            );

            // =================================================
            // Cột định nghĩa tiếng Việt
            // =================================================

            $table->addCell(2800, $normalCellStyle)->addText(
                safeDocText($row['definition_vi'] ?? '')
            );

            // =================================================
            // Cột ví dụ + nuance + collocations
            // =================================================

            $exampleCell = $table->addCell(3200, $normalCellStyle);

            $exampleSentence = safeDocText($row['example_sentence'] ?? '');

            if ($exampleSentence !== '') {
                $exampleCell->addText($exampleSentence);
            }

            // Nếu có nuance thì thêm bên dưới example
            $nuance = safeDocText($row['nuance'] ?? '');

            if ($nuance !== '') {
                $exampleCell->addText('Nuance: ' . $nuance, [
                    'italic' => true,
                    'size' => 9,
                    'color' => '64748B'
                ]);
            }

            // Nếu có collocations thì thêm bên dưới nuance
            $collocations = safeDocText($row['collocations'] ?? '');

            if ($collocations !== '') {
                $exampleCell->addText('Collocations: ' . $collocations, [
                    'size' => 9,
                    'color' => '475569'
                ]);
            }
        }

        // Thêm khoảng cách sau mỗi category
        $section->addTextBreak(1);
    }

    // Header tải file DOCX chuẩn
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '.docx"');
    header('Cache-Control: max-age=0');

    // Tạo writer để ghi file DOCX ra output
    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');

    // Ghi trực tiếp ra browser để tải xuống
    $writer->save('php://output');

    // Dừng script sau khi xuất file
    exit();
}

// =========================================================
// ROUTER EXPORT TYPE
// =========================================================

// Nếu user chọn CSV thì xuất CSV
if ($type === 'csv') {
    exportCsvByCategory($groupedWords, $filename);
}

// Nếu user chọn DOCX thì xuất DOCX
if ($type === 'docx') {
    exportDocxByCategory($groupedWords, $filename);
}

// Nếu type không hợp lệ
die("Invalid export type");
?>
