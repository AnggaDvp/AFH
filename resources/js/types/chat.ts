export type ChatAttachment = {
    id: number;
    original_name: string;
    mime_type: string;
    extension: string;
    size: number;
    attachment_type: 'image' | 'document';
    download_url: string;
    preview_url: string | null;
};

export type ChatMessage = {
    id: number;
    senderType: 'customer' | 'admin' | 'system';
    senderName: string | null;
    body: string | null;
    createdAt: string | null;
    readAt: string | null;
    attachments: ChatAttachment[];
};

export function formatFileSize(bytes: number): string {
    return bytes < 1024 * 1024
        ? `${Math.max(1, Math.ceil(bytes / 1024))} KB`
        : `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
