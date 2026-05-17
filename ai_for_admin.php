<?php
session_start(); // Bắt đầu session để lưu trạng thái đăng nhập admin

// Hàm đọc file .env từ thư mục Backend (đường dẫn tuyệt đối theo cấu trúc VPS của bạn)
// Tham số: $env_path - đường dẫn đến file .env (mặc định là /var/www/python_api/.env theo cấu trúc bạn cung cấp)
function load_env($env_path) {
    $env = [];
    if (!file_exists($env_path)) {
        error_log("Không tìm thấy file .env tại: " . $env_path);
        return $env;
    }
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Bỏ qua dòng comment bắt đầu bằng #
        if (strpos($line, '#') === 0) continue;
        // Tách key và value bằng dấu = (chỉ tách lần đầu để tránh lỗi với value chứa dấu =)
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }
    }
    return $env;
}

// Đường dẫn file .env nằm trong thư mục Backend (python_api) theo cấu trúc bạn cung cấp
define('ENV_PATH', '/var/www/python_api/.env');
$env = load_env(ENV_PATH);

// Xử lý đăng xuất: xóa toàn bộ session và chuyển hướng về trang đăng nhập
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ai_for_admin.php");
    exit();
}

// Xử lý đăng nhập khi submit form
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Lấy thông tin đăng nhập từ file .env (cần có ADMIN_USERNAME và ADMIN_PASSWORD trong .env)
    $correct_username = $env['ADMIN_USERNAME'] ?? '';
    $correct_password = $env['ADMIN_PASSWORD'] ?? '';
    
    if ($username === $correct_username && $password === $correct_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header("Location: ai_for_admin.php");
        exit();
    } else {
        $login_error = "Tên đăng nhập hoặc mật khẩu không đúng!";
    }
}

// Lấy backend URL từ file .env để dùng trong JavaScript (tuyệt đối không dùng localhost)
$backendUrl = $env['BACKEND_URL'] ?? '';
if (empty($backendUrl)) {
    die("Thiếu cấu hình BACKEND_URL trong file .env. Vui lòng thêm BACKEND_URL=http://your-vps-ip:5002 vào /var/www/python_api/.env");
}

// Nếu chưa đăng nhập, hiển thị form đăng nhập và dừng xử lý
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true):
?>

<!-- Phần form đăng nhập dành cho người chưa xác thực -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin - AI Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-6 md:p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">Đăng nhập Admin</h1>
        <?php if ($login_error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($login_error); ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-gray-700 mb-2" for="username">Tên đăng nhập</label>
                <input type="text" id="username" name="username" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 mb-2" for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" name="login"
                class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 transition duration-200">
                Đăng nhập
            </button>
        </form>
    </div>
</body>
</html>
<?php
exit(); // Dừng xử lý nếu chưa đăng nhập
endif;
?>

<!-- Phần giao diện chính sau khi đăng nhập thành công -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Admin Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Tùy chỉnh thanh cuộn cho khung chat và danh sách thread */
        #chat-messages::-webkit-scrollbar, #thread-list::-webkit-scrollbar {
            width: 6px;
        }
        #chat-messages::-webkit-scrollbar-track, #thread-list::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        #chat-messages::-webkit-scrollbar-thumb, #thread-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        #chat-messages::-webkit-scrollbar-thumb:hover, #thread-list::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">
    <!-- Header hiển thị thông tin người dùng và nút đăng xuất -->
    <header class="bg-blue-600 text-white p-4 shadow-md flex justify-between items-center">
        <h1 class="text-xl font-bold">AI Admin Chat</h1>
        <div class="flex items-center space-x-4">
            <span class="text-sm md:text-base">Xin chào, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="?logout=1" class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded text-sm transition duration-200">Đăng xuất</a>
        </div>
    </header>

    <!-- Khung chính chứa Sidebar và Khung chat (responsive: mobile dọc, desktop ngang) -->
    <main class="flex-1 flex flex-col md:flex-row overflow-hidden">
        <!-- Sidebar hiển thị danh sách các phiên chat (Threads) -->
        <aside class="bg-gray-800 text-white w-full md:w-64 p-4 flex flex-col md:min-h-0">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">Cuộc hội thoại</h2>
                <button id="new-chat-btn" class="bg-blue-500 hover:bg-blue-600 text-white text-sm py-1 px-3 rounded transition duration-200">
                    + Mới
                </button>
            </div>
            <!-- Danh sách threads sẽ được load động bằng JavaScript -->
            <div id="thread-list" class="flex-1 overflow-y-auto space-y-2">
                <div class="text-gray-400 text-sm italic">Đang tải danh sách...</div>
            </div>
        </aside>

        <!-- Khung chat chính -->
        <section class="flex-1 flex flex-col p-4 md:p-6 overflow-hidden">
            <!-- Vùng hiển thị tin nhắn -->
            <div id="chat-messages" class="flex-1 overflow-y-auto mb-4 p-4 bg-white rounded-lg shadow-md space-y-4">
                <div class="text-center text-gray-500 italic">Chọn một cuộc hội thoại hoặc tạo mới để bắt đầu</div>
            </div>

            <!-- Vùng preview file đã chọn -->
            <div id="file-preview" class="hidden mb-4 p-3 bg-gray-50 rounded-md border border-gray-200">
                <div class="flex justify-between items-center">
                    <span id="file-name" class="text-sm text-gray-700"></span>
                    <button id="remove-file" class="text-red-500 text-sm hover:text-red-700">Xóa</button>
                </div>
            </div>

            <!-- Vùng nhập liệu -->
            <div class="bg-white p-4 rounded-lg shadow-md">
                <!-- Toggle chế độ gửi code block -->
                <div class="mb-2 flex items-center space-x-2">
                    <input type="checkbox" id="code-toggle" class="rounded text-blue-500">
                    <label for="code-toggle" class="text-sm text-gray-700">Gửi dưới dạng mã nguồn (code block)</label>
                </div>

                <!-- Ô nhập tin nhắn -->
                <div class="mb-3">
                    <textarea id="user-input" rows="3" placeholder="Nhập tin nhắn hoặc dán mã nguồn..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y"></textarea>
                </div>

                <!-- Nút chức năng -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <!-- Nút chọn file upload -->
                        <label class="cursor-pointer bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-4 rounded-md text-sm transition duration-200">
                            <input type="file" id="file-input" accept=".txt,.log" class="hidden">
                            📎 Chọn file (.txt, .log)
                        </label>
                    </div>

                    <!-- Nút gửi tin nhắn -->
                    <button id="send-btn" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-6 rounded-md transition duration-200">
                        Gửi
                    </button>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Khởi tạo biến backendUrl từ PHP (lấy từ file .env, không dùng localhost)
        const backendUrl = '<?php echo $backendUrl; ?>';
        let currentThreadId = null; // ID của thread đang active hiện tại
        let uploadedFileContent = null; // Nội dung file upload tạm thời
        let uploadedFileName = null; // Tên file upload

        // Lấy các element DOM cần sử dụng
        const chatMessages = document.getElementById('chat-messages');
        const userInput = document.getElementById('user-input');
        const sendBtn = document.getElementById('send-btn');
        const fileInput = document.getElementById('file-input');
        const filePreview = document.getElementById('file-preview');
        const fileName = document.getElementById('file-name');
        const removeFileBtn = document.getElementById('remove-file');
        const codeToggle = document.getElementById('code-toggle');
        const threadList = document.getElementById('thread-list');
        const newChatBtn = document.getElementById('new-chat-btn');

        // Hàm tải danh sách tất cả threads từ backend và hiển thị vào sidebar
        // Endpoint tương ứng: GET /api/threads (backend đã viết)
        async function loadThreads() {
            threadList.innerHTML = '<div class="text-gray-400 text-sm italic">Đang tải danh sách...</div>';
            try {
                const response = await fetch(`${backendUrl}/api/threads`, {
                    method: 'GET',
                    headers: { 'Content-Type': 'application/json' }
                });
                if (!response.ok) throw new Error('Không thể kết nối đến backend để lấy danh sách threads');
                const threads = await response.json();
                
                threadList.innerHTML = ''; // Xóa nội dung cũ
                if (threads.length === 0) {
                    threadList.innerHTML = '<div class="text-gray-400 text-sm italic">Chưa có cuộc hội thoại nào</div>';
                    return;
                }
                // Duyệt qua từng thread và tạo element hiển thị trong sidebar
                threads.forEach(thread => {
                    const threadItem = document.createElement('div');
                    threadItem.className = 'p-2 rounded cursor-pointer hover:bg-gray-700 transition duration-150 thread-item';
                    threadItem.dataset.threadId = thread.id;
                    threadItem.innerHTML = `
                        <div class="text-sm font-medium truncate">${escapeHtml(thread.title)}</div>
                        <div class="text-xs text-gray-400">${new Date(thread.updated_at).toLocaleString('vi-VN')}</div>
                    `;
                    // Sự kiện click vào thread để load tin nhắn của thread đó
                    threadItem.addEventListener('click', () => loadThreadMessages(thread.id));
                    threadList.appendChild(threadItem);
                });
            } catch (error) {
                threadList.innerHTML = `<div class="text-red-400 text-sm">Lỗi: ${error.message}</div>`;
                console.error('Lỗi tải danh sách threads:', error);
            }
        }

        // Hàm tải tin nhắn của một thread cụ thể khi click vào sidebar
        // Endpoint tương ứng: GET /api/threads/<id> (backend trả về thread + messages)
        async function loadThreadMessages(threadId) {
            currentThreadId = threadId; // Cập nhật thread đang active
            chatMessages.innerHTML = '<div class="text-center text-gray-500 italic">Đang tải tin nhắn...</div>';
            
            // Cập nhật trạng thái active (highlight) cho thread được chọn trong sidebar
            document.querySelectorAll('.thread-item').forEach(item => {
                item.classList.remove('bg-gray-700');
                if (item.dataset.threadId == threadId) {
                    item.classList.add('bg-gray-700');
                }
            });

            try {
                const response = await fetch(`${backendUrl}/api/threads/${threadId}`, {
                    method: 'GET',
                    headers: { 'Content-Type': 'application/json' }
                });
                if (!response.ok) throw new Error('Không thể tải tin nhắn của cuộc hội thoại này');
                const data = await response.json();
                
                chatMessages.innerHTML = ''; // Xóa nội dung cũ
                if (data.messages.length === 0) {
                    chatMessages.innerHTML = '<div class="text-center text-gray-500 italic">Chưa có tin nhắn nào trong cuộc hội thoại này</div>';
                    return;
                }
                // Hiển thị tất cả tin nhắn của thread (thứ tự cũ đến mới)
                data.messages.forEach(msg => {
                    addMessageToChat(msg.role, msg.content);
                });
                scrollToBottom(); // Tự động cuộn xuống tin nhắn mới nhất
            } catch (error) {
                chatMessages.innerHTML = `<div class="text-center text-red-500">Lỗi: ${error.message}</div>`;
                console.error('Lỗi tải tin nhắn:', error);
            }
        }

        // Hàm thêm một tin nhắn vào khung chat
        // Tham số: role - 'user' (người dùng) hoặc 'assistant' (AI), content - nội dung tin nhắn
        // Ghi chú: Nếu bảng chat_messages thêm trường mới, cập nhật phần hiển thị tin nhắn ở đây
        function addMessageToChat(role, content) {
            const msgDiv = document.createElement('div');
            msgDiv.className = `flex ${role === 'user' ? 'justify-end' : 'justify-start'}`;
            
            let msgContent = '';
            if (role === 'user') {
                // Tin nhắn người dùng: màu xanh, căn phải
                msgContent = `
                    <div class="max-w-3/4 bg-blue-500 text-white p-3 rounded-lg shadow-sm">
                        <div>${formatMessage(content)}</div>
                    </div>
                `;
            } else {
                // Tin nhắn AI: màu xám, căn trái
                msgContent = `
                    <div class="max-w-3/4 bg-gray-200 text-gray-800 p-3 rounded-lg shadow-sm">
                        <div>${formatMessage(content)}</div>
                    </div>
                `;
            }
            
            msgDiv.innerHTML = msgContent;
            chatMessages.appendChild(msgDiv);
        }

        // Hàm xử lý gửi tin nhắn khi bấm nút Gửi hoặc nhấn Enter
        async function sendMessage() {
            const message = userInput.value.trim();
            if (!message && !uploadedFileContent) return; // Không gửi nếu rỗng

            // Hiển thị tin nhắn người dùng ngay lập tức
            addMessageToChat('user', message);
            userInput.value = ''; // Xóa nội dung input
            
            // Lưu tạm nội dung file và xóa preview
            const currentFileContent = uploadedFileContent;
            const currentFileName = uploadedFileName;
            uploadedFileContent = null;
            uploadedFileName = null;
            filePreview.classList.add('hidden');

            // Hiển thị trạng thái "Đang trả lời..." chờ AI phản hồi
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'loading-indicator';
            loadingDiv.className = 'flex justify-start';
            loadingDiv.innerHTML = `
                <div class="max-w-3/4 bg-gray-200 text-gray-800 p-3 rounded-lg shadow-sm">
                    <div class="italic">Đang trả lời...</div>
                </div>
            `;
            chatMessages.appendChild(loadingDiv);
            scrollToBottom();

            try {
                // Gọi API chat backend
                // Endpoint tương ứng: POST /api/chat
                // Body: { message, thread_id?, file_content? } (khớp hoàn toàn với backend)
                const response = await fetch(`${backendUrl}/api/chat`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: message,
                        thread_id: currentThreadId, // null nếu là thread mới
                        file_content: currentFileContent
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || 'Lỗi từ server backend');
                }

                const data = await response.json();
                
                // Xóa trạng thái đang trả lời
                document.getElementById('loading-indicator')?.remove();
                
                // Hiển thị câu trả lời của AI
                addMessageToChat('assistant', data.response);
                scrollToBottom();

                // Nếu là thread mới (chưa có currentThreadId), cập nhật sidebar và ID hiện tại
                if (!currentThreadId && data.thread_id) {
                    currentThreadId = data.thread_id;
                    loadThreads(); // Tải lại danh sách threads để hiển thị thread mới tạo
                }
            } catch (error) {
                // Xử lý lỗi kết nối hoặc lỗi từ backend
                document.getElementById('loading-indicator')?.remove();
                addMessageToChat('assistant', `Lỗi: ${error.message}`);
                scrollToBottom();
                console.error('Lỗi gửi tin nhắn:', error);
            }
        }

        // Xử lý upload file .txt/.log qua API backend
        // Endpoint tương ứng: POST /api/upload
        fileInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            // Kiểm tra định dạng file只允许 .txt và .log
            if (!file.name.endsWith('.txt') && !file.name.endsWith('.log')) {
                alert('Chỉ hỗ trợ upload file .txt và .log!');
                fileInput.value = '';
                return;
            }

            // Tạo FormData để gửi file lên backend
            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch(`${backendUrl}/api/upload`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.error) {
                    alert(data.error);
                    return;
                }

                // Lưu nội dung file và hiển thị preview
                uploadedFileContent = data.content;
                uploadedFileName = data.filename;
                fileName.textContent = `File đính kèm: ${data.filename}`;
                filePreview.classList.remove('hidden');
                fileInput.value = ''; // Reset input file để chọn lại được file cũ
            } catch (error) {
                alert('Lỗi upload file: ' + error.message);
                console.error('Lỗi upload:', error);
            }
        });

        // Xử lý xóa file đã chọn
        removeFileBtn.addEventListener('click', () => {
            uploadedFileContent = null;
            uploadedFileName = null;
            filePreview.classList.add('hidden');
            fileName.textContent = '';
        });

        // Xử lý bấm nút Gửi
        sendBtn.addEventListener('click', sendMessage);

        // Xử lý nhấn Enter để gửi (không gửi khi nhấn Shift + Enter)
        userInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Xử lý tạo cuộc hội thoại mới (xóa currentThreadId, clear khung chat)
        newChatBtn.addEventListener('click', () => {
            currentThreadId = null;
            chatMessages.innerHTML = '<div class="text-center text-gray-500 italic">Cuộc hội thoại mới - Nhập tin nhắn để bắt đầu</div>';
            // Bỏ chọn tất cả thread trong sidebar
            document.querySelectorAll('.thread-item').forEach(item => {
                item.classList.remove('bg-gray-700');
            });
        });

        // Hàm format tin nhắn hiển thị (xử lý code block, escape HTML chống XSS)
        function formatMessage(content) {
            // Nếu bật toggle gửi code block, hiển thị toàn bộ nội dung dưới dạng code
            if (codeToggle.checked) {
                return `<pre class="bg-gray-800 text-white p-3 rounded-md overflow-x-auto text-sm"><code>${escapeHtml(content)}</code></pre>`;
            }
            // Xử lý code block tự động (nội dung bắt đầu và kết thúc bằng ```)
            if (content.startsWith('```') && content.endsWith('```')) {
                const code = content.slice(3, -3).replace(/^[\n\r]+|[\n\r]+$/g, '');
                return `<pre class="bg-gray-800 text-white p-3 rounded-md overflow-x-auto text-sm"><code>${escapeHtml(code)}</code></pre>`;
            }
            // Escape HTML để tránh XSS, chuyển xuống dòng thành <br>
            return escapeHtml(content).replace(/\n/g, '<br>');
        }

        // Hàm escape HTML chống tấn công XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Hàm tự động cuộn xuống cuối khung chat khi có tin nhắn mới
        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Tải danh sách threads ngay khi trang load xong
        document.addEventListener('DOMContentLoaded', loadThreads);
    </script>
</body>
</html>
