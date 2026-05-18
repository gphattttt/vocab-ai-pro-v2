# Vocab AI Pro - Project Structure

## 1. Tổng quan dự án

Vocab AI Pro là website học từ vựng tiếng Anh có tích hợp AI, quiz, flashcards, SRS, XP, leaderboard, forum và hệ thống test đầu vào cho người dùng mới.

Khi người dùng mới tạo tài khoản, website có chức năng cho làm bài test đầu vào với 2 lựa chọn:

- General test
- IELTS-focused test

Sau bài test, hệ thống sẽ đưa ra lộ trình học tập phù hợp hơn cho người dùng.

---

## 2. Stack đang sử dụng

### Frontend / Backend chính

- PHP
- HTML / CSS / JavaScript
- MySQL
- Composer / Vendor dependencies

### AI Backend

- Python
- Flask API
- PM2 dùng để quản lý Python API process

### Server

- PHP-FPM
- Web server: Nginx
- Linux VPS

---

## 3. Thư mục chính

/var/www/html/

## 4. Thư mục chính cho backend

/var/www/python_api/

## 5. Cấu trúc PHP app - /var/www/html/

login.php (Đăng nhập)
register.php (Đăng ký)
logout.php (Đăng xuất)
forgot_password.php (Quên mật khẩu)
reset_password.php (Reset mật khẩu)
google_callback.php (Đăng nhập bằng Google)
profile.php (Xem hồ sơ người dùng)
profile_update.php (cập nhật hồ sơ người dùng)

## 6. Main pages

index.php (Trang chủ)
profile.php (Trang hồ sơ)
board.php (Kho từ vựng)
leaderboard.php (Bảng xếp hạng)
study.php (Trang chọn để học - Gamification (2 hai lựa chọn - Quiz — Do AI tạo; và Flashcards)

## 7. Chức năng học

quiz.php (Trắc nghiệm tạo từ AI)
flashcards.php
take_a_test.php (Kiểm tra đầu vào)
essay_extract.php (Trích xuất từ vựng từ Essay)
ask_question.php (Đăng câu hỏi lên forum.php)
view_thread.php (Xem câu hỏi được đăng)

## 8. Liên quan tới forum.php

forum.php (trang chính)
ask_question.php (đăng câu hỏi)
view_thread.php (xem câu hỏi được đăng, và có cả phần trả lời)

## 9. Config/Helpers

db.php (dùng cho kết nối db, db.php.example cùng cấu trúc file này chỉ bỏ credentials)
nav.php (hamburger menu dùng cho tất cả trang)
mail_helper.php (gửi email cho người dùng khi người dùng dùng chức năng quên mật khẩu)
vendor/
composer.json
composer.lock

## 10. Thư mục chứa file upload từ phía người dùng

uploads/ 

## 11. Python AI Backend - /var/www/python_api/

app.py (cho index.php)
take-test.py (cho take_a_test.php)
essay_extract.py (dùng cho essay_extract.php)
.env (chứa credentials như OpenRouter API Key)
.gitignore
venv/ 

Flow of work gọi backend hiện tại (ví dụ: essay_extract.php -> essay_extract_proxy.php -> essay_extract.py)

## 12. Cơ chế cộng XP

Lưu từ +10
Làm quiz +50
Đăng câu hỏi trên forum +15
Trả lời câu hỏi trên forum +5

## 12. Cấu trúc file cụ thể

/var/www/
├── html/                           # Web root - PHP frontend
│   │
│   ├── 📁 uploads/                 # Thư mục chứa file do user upload
│   ├── 📁 vendor/                  # Composer dependencies (PHP)
│   │
│   ├── composer.json               # Khai báo PHP dependencies
│   ├── composer.lock               # Lock file của Composer
│   ├── .gitignore
│   │
│   ├── ── Authentication ──────────────────────────────
│   ├── login.php                   # Trang đăng nhập
│   ├── logout.php                  # Xử lý đăng xuất
│   ├── register.php                # Trang đăng ký
│   ├── forgot_password.php         # Trang quên mật khẩu
│   ├── forgot_password_process.php # Xử lý gửi email reset
│   ├── reset_password.php          # Trang đặt lại mật khẩu
│   ├── google-callback.php         # OAuth callback từ Google
│   │
│   ├── ── Core Pages ──────────────────────────────────
│   ├── index.php                   # Trang chủ / dashboard
│   ├── nav.php                     # Navigation bar (include dùng chung)
│   ├── study.php                   # Trang học từ vựng / SRS
│   ├── flashcards.php              # Chế độ học flashcard
│   ├── quiz.php                    # Trang làm quiz
│   ├── take_a_test.php             # Trang thi thử / kiểm tra
│   ├── leaderboard.php             # Bảng xếp hạng
│   ├── profile.php                 # Trang hồ sơ người dùng
│   ├── maintenance.html            # Trang bảo trì (static HTML)
│   │
│   ├── ── Forum / Community ───────────────────────────
│   ├── forum.php                   # Danh sách chủ đề diễn đàn
│   ├── board.php                   # Bảng thảo luận
│   ├── view_thread.php             # Xem chi tiết một thread
│   ├── ask_question.php            # Đăng câu hỏi mới
│   │
│   ├── ── AI / Essay ──────────────────────────────────
│   ├── essay_extract.php           # Giao diện trích xuất essay bằng AI
│   ├── essay_extract_proxy.php     # Proxy gọi Python API cho essay
│   ├── process_essay.php           # Xử lý và lưu kết quả essay
│   ├── quiz_proxy.php              # Proxy gọi Python API cho quiz
│   │
├── ── Data / API Handlers ─────────────────────────
│   ├── save.php                    # Lưu tiến trình học
│   ├── fetch_board.php             # Fetch dữ liệu bảng (AJAX)
│   ├── check_cache.php             # Kiểm tra cache
│   ├── get_level.php               # Lấy level hiện tại của user
│   ├── import_levels.php           # Import dữ liệu level
│   ├── update_srs.php              # Cập nhật điểm SRS (Spaced Repetition)
│   ├── update_profile.php          # Cập nhật thông tin profile
│   ├── export.php                  # Xuất dữ liệu (CSV / JSON...)
│   │
│   ├── ── Word / Category Actions ─────────────────────
│   ├── word_actions.php            # CRUD từ vựng
│   ├── category_actions.php        # CRUD danh mục
│   │
│   ├── ── XP / Gamification ───────────────────────────
│   ├── reward_xp.php               # Cộng XP cho người dùng
│   ├── reset_weekly_xp.php         # Reset XP hàng tuần (cron job)
│   │
│   ├── ── Config / Helpers ────────────────────────────
│   ├── db.php                      # Kết nối database (MySQL)
│   ├── mail_helper.php             # Helper gửi email (SMTP)
│   │
│   └── project_structure.md        # Tài liệu cấu trúc dự án (file này)
│
└── python_api/                     # Python backend (Flask / FastAPI)
    │
    ├── 📁 venv/                    # Python virtual environment
    ├── .env                        # Biến môi trường (API keys, secrets)
    ├── .gitignore
    │
    ├── app.py                      # Entry point của Python API server
    ├── essay_extract.py            # Logic trích xuất & phân tích essay bằng AI
    ├── quiz-api.py                 # API sinh câu hỏi quiz bằng AI
    └── take-test.py                # Logic tạo đề thi tự động
