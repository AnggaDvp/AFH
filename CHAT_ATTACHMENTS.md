# Chat Attachment Upload Limits

The application validates each selected file and allows up to five files per message:

- JPG, JPEG, PNG, WEBP: 5 MB each
- PDF, DOC, DOCX, XLS, XLSX: 10 MB each

Configure the PHP runtime and reverse proxy/web server to accept up to five files and at least 64 MB per request (`upload_max_filesize=10M`, `post_max_size=64M`, `max_file_uploads=5`). Keep the per-file application validation enabled; PHP request limits are an additional transport boundary, not a replacement for it. If the host rejects the request first, the chat displays a safe size-limit message.

Files use Laravel's `chat_private` disk rooted at `storage/app/private`. Do not create a public storage link to this directory or expose it through the web server.
