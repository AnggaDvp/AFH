import { useEffect, useRef, useState } from 'react';
import { formatFileSize, type ChatAttachment } from '@/types/chat';

function ImagePreview({ attachment, close }: { attachment: ChatAttachment; close: () => void }) {
    const dialog = useRef<HTMLDialogElement>(null);
    const [failed, setFailed] = useState(false);
    useEffect(() => {
        const element = dialog.current;
        const previousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        element?.showModal();
        return () => {
            element?.close();
            previousFocus?.focus();
        };
    }, []);

    return (
        <dialog ref={dialog} onClose={close} aria-label={`Preview ${attachment.original_name}`}
            className="m-auto w-[calc(100%-2rem)] max-w-4xl rounded-2xl bg-white p-4 text-slate-900 shadow-xl backdrop:bg-black/70">
            <div className="mb-3 flex items-center justify-between gap-4">
                <p className="min-w-0 break-all text-sm font-semibold">{attachment.original_name}</p>
                <button type="button" autoFocus onClick={close} aria-label="Tutup preview gambar"
                    className="min-h-11 shrink-0 rounded-lg px-3 font-semibold hover:bg-slate-100">Tutup</button>
            </div>
            {failed ? <p role="alert" className="p-6 text-sm text-red-700">Foto belum dapat dimuat. Periksa sesi Anda lalu coba lagi.</p> : (
                <img src={attachment.preview_url ?? ''} alt={attachment.original_name} onError={() => setFailed(true)}
                    className="mx-auto max-h-[75vh] w-full object-contain" />
            )}
            <a href={attachment.download_url} download className="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-red-700">
                Download {formatFileSize(attachment.size)}
            </a>
        </dialog>
    );
}

export default function ChatAttachments({ attachments }: { attachments: ChatAttachment[] }) {
    const [preview, setPreview] = useState<ChatAttachment | null>(null);
    if (attachments.length === 0) return null;

    return (
        <>
            <div className="mt-3 grid min-w-0 gap-2">
                {attachments.map((attachment) => (
                    <div key={attachment.id} className="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white p-3 text-slate-900">
                        {attachment.attachment_type === 'image' && attachment.preview_url ? (
                            <button type="button" onClick={() => setPreview(attachment)}
                                aria-label={`Preview ${attachment.original_name}`}
                                className="block w-full rounded-lg focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-200">
                                <img src={attachment.preview_url} alt={attachment.original_name} loading="lazy"
                                    className="h-32 w-full rounded-lg bg-slate-100 object-contain sm:h-40" />
                            </button>
                        ) : (
                            <div className="flex items-center gap-2 px-1 py-2">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="h-7 w-7 shrink-0 text-red-700">
                                    <path strokeLinejoin="round" d="M14 3H5v18h14V8zm0 0v5h5M8 12h8M8 16h6" />
                                </svg>
                                <span className="text-xs font-bold uppercase text-slate-600">{attachment.extension}</span>
                            </div>
                        )}
                        <p className="mt-2 break-all text-xs font-semibold">{attachment.original_name}</p>
                        <p className="mt-1 text-xs text-slate-500">{formatFileSize(attachment.size)}</p>
                        <a href={attachment.download_url} download aria-label={`Download ${attachment.original_name}`}
                            className="inline-flex min-h-11 items-center text-sm font-semibold text-brand underline-offset-2 hover:underline focus-visible:outline-none focus-visible:underline">
                            Download
                        </a>
                    </div>
                ))}
            </div>
            {preview && <ImagePreview attachment={preview} close={() => setPreview(null)} />}
        </>
    );
}
