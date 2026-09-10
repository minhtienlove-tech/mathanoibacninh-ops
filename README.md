# Website Operations - mathanoibacninh.com

Repository dùng chung cho Codex, Claude, Gemini và các công cụ AI khác khi
quản lý website Bệnh viện Mắt Hà Nội - Bắc Ninh.

## Bắt đầu trên máy mới

1. Clone repository private này.
2. Tạo SSH key riêng cho máy đó; không sao chép private key từ máy khác.
3. Authorize public key trong AZDIGI/cPanel.
4. Thêm alias `mathanoibacninh` theo `docs/DEPLOYMENT.md`.
5. Chạy `ssh mathanoibacninh` và `scripts/verify-live.ps1 -CheckSsh`.

Mã nguồn nằm trong `theme/eyecare-child`; các tài liệu vận hành nằm trong `docs/`.
Không commit `.env`, `wp-config.php`, database dump, file `.wpress` hoặc private key.
