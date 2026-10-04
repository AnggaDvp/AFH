import { router } from '@inertiajs/react';
import { useEffect, useId, useRef, useState, type ChangeEvent, type FormEvent } from 'react';
import { formatFileSize } from '@/types/chat';

const imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
const documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
const acceptedTypes = [...imageExtensions, ...documentExtensions].map((extension) => `.${extension}`).join(',');

function isImage(file: File): boolean {
    return imageExtensions.includes(file.name.split('.').pop()?.toLowerCase() ?? '');
}

function SelectedImage({ file }: { file: File }) {
    const [url, setUrl] = useState('');
    useEffect(() => {
        const objectUrl = URL.createObjectURL(file);
        setUrl(objectUrl);
        return () => URL.revokeObjectURL(objectUrl);
    }, [file]);

    return url ? <img src={url} alt={file.name} className="h-16 w-16 shrink-0 rounded-lg bg-slate-100 object-contain" /> : null;
}

type Props = {
    url: string;
    label?: string;
};

export default function ChatComposer({ url, label = 'Kirim' }: Props) {
    const id = useId();
    const input = useRef<HTMLInputElement>(null);
    const [body, setBody] = useState('');
    const [files, setFiles] = useState<File[]>([]);
    const [processing, setProcessing] = useState(false);
    const [progress, setProgress] = useState<number | null>(null);
    const [errors, setErrors] = useState<string[]>([]);

    const selectFiles = (event: ChangeEvent<HTMLInputElement>) => {
        const incoming = Array.from(event.target.files ?? []);
        event.target.value = '';
        const problems: string[] = [];
        if (files.length + incoming.length > 5) problems.push('Maksimal 5 file dalam satu pesan.');
        for (const file of incoming) {
            const extension = file.name.split('.').pop()?.toLowerCase() ?? '';
            if (![...imageExtensions, ...documentExtensions].includes(extension)) {
                problems.push(`${file.name}: format tidak didukung.`);
            } else if (file.size > (isImage(file) ? 5 : 10) * 1024 * 1024) {
                problems.push(`${file.name}: maksimal ${isImage(file) ? '5' : '10'} MB per file.`);
            } else if (file.size === 0) {
                problems.push(`${file.name}: file kosong tidak dapat dikirim.`);
            }
        }
        setErrors(problems);
        if (problems.length === 0) setFiles((current) => [...current, ...incoming]);
    };

    const send = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (processing || (!body.trim() && files.length === 0)) return;
        setErrors([]);
        setProgress(null);
        router.post(url, { body, attachments: files }, {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onProgress: (event) => setProgress(event?.percentage ?? null),
            onSuccess: () => {
                setBody('');
                setFiles([]);
            },
            onError: (serverErrors) => setErrors(Object.values(serverErrors)),
            onFinish: () => {
                setProcessing(false);
                setProgress(null);
            },
        });
    };

    return (
        <form onSubmit={send} className="space-y-2.5" aria-busy={processing}>
            <p id={`${id}-help`} className="text-xs leading-5 text-slate-600">
                Maksimal 5 file. Foto JPG, PNG, WEBP hingga 5 MB; dokumen PDF, DOC, DOCX, XLS, XLSX hingga 10 MB per file.
            </p>
            {files.length > 0 && (
                <ul aria-label="File terpilih" className="grid gap-2 sm:grid-cols-2">
                    {files.map((file, index) => (
                        <li key={`${file.name}-${file.lastModified}-${index}`} className="flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 p-3">
                            {isImage(file) ? <SelectedImage file={file} /> : (
                                <span aria-hidden="true" className="rounded-lg bg-slate-100 px-2 py-4 text-xs font-bold text-slate-600">FILE</span>
                            )}
                            <div className="min-w-0 flex-1">
                                <p className="break-all text-sm text-slate-800">{file.name}</p>
                                <p className="mt-1 text-xs text-slate-500">{formatFileSize(file.size)}</p>
                                <button type="button" disabled={processing} aria-label={`Hapus ${file.name} dari pilihan`}
                                    onClick={() => setFiles((current) => current.filter((_, position) => position !== index))}
                                    className="mt-1 min-h-9 text-xs font-semibold text-red-700 disabled:opacity-50">
                                    Hapus
                                </button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
            <label className="sr-only" htmlFor={`${id}-message`}>Tulis pesan</label>
            <textarea id={`${id}-message`} value={body} onChange={(event) => setBody(event.target.value)}
                disabled={processing} maxLength={5000} rows={2} placeholder="Tulis pesan atau pilih file..."
                className="customer-input max-h-40 resize-y py-3 text-sm" />
            <div className="flex flex-wrap items-center justify-between gap-3">
                <input ref={input} id={`${id}-files`} type="file" multiple accept={acceptedTypes} onChange={selectFiles}
                    disabled={processing} aria-label="Pilih foto atau dokumen" aria-describedby={`${id}-help`} className="sr-only" tabIndex={-1} />
                <button type="button" onClick={() => input.current?.click()} disabled={processing}
                    aria-label="Lampirkan foto atau dokumen"
                    className="customer-button-secondary gap-2">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="h-5 w-5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="m8 12 6-6a3 3 0 0 1 4 4l-8 8a5 5 0 0 1-7-7l8-8m-3 9 5-5" />
                    </svg>
                    Lampiran
                </button>
                <button type="submit" disabled={processing || (!body.trim() && files.length === 0)}
                    className="customer-button-primary">
                    {processing ? 'Mengirim...' : label}
                </button>
            </div>
            {processing && progress !== null && (
                <p role="status" className="text-xs text-slate-600">Mengunggah {progress}%</p>
            )}
            {errors.length > 0 && (
                <div role="alert" className="space-y-1 rounded-xl bg-red-50 p-3 text-sm text-red-800">
                    {errors.map((error, index) => <p key={index}>{error}</p>)}
                </div>
            )}
        </form>
    );
}
