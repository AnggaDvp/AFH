import { useEffect, useRef, useState } from 'react';
import RegionalAddressFields from '@/components/RegionalAddressFields';

type Props = {
    open: boolean;
    onClose: () => void;
};

type Step = 'choice' | 'existing' | 'new';

type CustomerForm = {
    nama: string;
    telepon: string;
    wilayah: string;
    kecamatan: string;
    kelurahan: string;
    kodePos: string;
    namaJalan: string;
};

const emptyForm: CustomerForm = {
    nama: '',
    telepon: '',
    wilayah: '',
    kecamatan: '',
    kelurahan: '',
    kodePos: '',
    namaJalan: '',
};

const fields: Array<{
    name: keyof CustomerForm;
    label: string;
    placeholder: string;
    autocomplete?: string;
    numeric?: boolean;
}> = [
    { name: 'nama', label: 'Nama Lengkap', placeholder: 'Nama lengkap Anda', autocomplete: 'name' },
    { name: 'telepon', label: 'No Telepon', placeholder: '081315379958', autocomplete: 'tel', numeric: true },
    { name: 'namaJalan', label: 'Nama Jalan', placeholder: 'Nama jalan, blok, atau nomor rumah', autocomplete: 'street-address' },
];

function validatePhone(value: string): string | null {
    const digits = value.replace(/\D/g, '');
    if (!digits) return 'Nomor telepon wajib diisi.';
    if (digits.length < 9 || digits.length > 15 || !/^(08|628)/.test(digits)) {
        return 'Gunakan nomor telepon Indonesia, contoh 081315379958.';
    }
    return null;
}

function validateField(name: keyof CustomerForm, value: string): string | null {
    const clean = value.trim();
    if (name === 'telepon') return validatePhone(value);
    if (!clean) return 'Bagian ini wajib diisi.';
    if (name === 'nama' && clean.length < 2) return 'Nama lengkap minimal 2 karakter.';
    if (name === 'kodePos' && !/^\d{5}$/.test(clean)) return 'Pilih kelurahan atau desa agar kode pos terisi otomatis.';
    return null;
}

export default function CustomerGateModal({ open, onClose }: Props) {
    const [step, setStep] = useState<Step>('choice');
    const [form, setForm] = useState<CustomerForm>(emptyForm);
    const [submitted, setSubmitted] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [serverErrors, setServerErrors] = useState<Partial<Record<keyof CustomerForm, string>>>({});
    const [submitError, setSubmitError] = useState('');
    const [closing, setClosing] = useState(false);
    const dialogRef = useRef<HTMLDivElement>(null);
    const closingRef = useRef(false);
    const processingRef = useRef(false);
    processingRef.current = processing;

    const handleClose = () => {
        if (closingRef.current || processingRef.current) return;
        closingRef.current = true;
        setClosing(true);
        window.setTimeout(onClose, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 400);
    };

    useEffect(() => {
        if (!open) return undefined;

        closingRef.current = false;
        setClosing(false);
        const previouslyFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        dialogRef.current?.focus();

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                handleClose();
                return;
            }
            if (event.key !== 'Tab' || !dialogRef.current) return;

            const focusable = dialogRef.current.querySelectorAll<HTMLElement>(
                'button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])',
            );
            if (focusable.length === 0) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && (document.activeElement === first || document.activeElement === dialogRef.current)) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => {
            window.removeEventListener('keydown', handleKeyDown);
            document.body.style.overflow = previousOverflow;
            previouslyFocused?.focus();
        };
    }, [open, onClose]);

    if (!open) return null;

    const updateField = (name: keyof CustomerForm, value: string) => {
        const normalized = (name === 'telepon' || name === 'kodePos') ? value.replace(/\D/g, '') : value;
        setForm((current) => ({ ...current, [name]: normalized }));
        setServerErrors((current) => ({ ...current, [name]: undefined }));
        setSubmitError('');
        setSubmitted(false);
    };

    const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setSubmitted(true);

        if (processing) return;

        const localErrors: Partial<Record<keyof CustomerForm, string>> = {};
        const currentPhoneError = validatePhone(form.telepon);
        if (currentPhoneError) localErrors.telepon = currentPhoneError;

        if (step === 'new') {
            fields.filter((field) => field.name !== 'telepon').forEach((field) => {
                const error = validateField(field.name, form[field.name]);
                if (error) localErrors[field.name] = error;
            });
            (['wilayah', 'kecamatan', 'kelurahan', 'kodePos'] as const).forEach((name) => {
                const error = validateField(name, form[name]);
                if (error) localErrors[name] = error;
            });
        }

        if (Object.keys(localErrors).length > 0) return;

        setProcessing(true);
        setServerErrors({});
        setSubmitError('');

        const payload = step === 'existing'
            ? { telepon: form.telepon }
            : {
                nama: form.nama.trim(),
                telepon: form.telepon,
                wilayah: form.wilayah,
                kecamatan: form.kecamatan,
                kelurahan: form.kelurahan,
                kodePos: form.kodePos,
                namaJalan: form.namaJalan.trim(),
            };

        try {
            const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
            const response = await fetch(step === 'existing' ? '/customer/identify' : '/customer/register', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                body: JSON.stringify(payload),
            });

            if (response.ok && (response.redirected || new URL(response.url).pathname === '/customer')) {
                window.location.assign('/customer');
                return;
            }

            const result = await response.json().catch(() => null) as {
                errors?: Record<string, string[] | string>;
            } | null;

            if (response.status === 422 && result?.errors) {
                const mappedErrors: Partial<Record<keyof CustomerForm, string>> = {};
                Object.entries(result.errors).forEach(([name, messages]) => {
                    if (name in emptyForm) {
                        const firstMessage = Array.isArray(messages) ? messages[0] : messages;
                        mappedErrors[name as keyof CustomerForm] = firstMessage || 'Periksa kembali data ini.';
                    }
                });
                setServerErrors(mappedErrors);
                if (Object.keys(mappedErrors).length === 0) {
                    setSubmitError('Periksa kembali data yang Anda isi.');
                }
            } else if (response.status === 419) {
                setSubmitError('Sesi formulir telah berakhir. Muat ulang halaman, lalu coba lagi.');
            } else {
                setSubmitError('Terjadi kendala saat mengirim data. Silakan coba lagi.');
            }
        } catch {
            setSubmitError('Tidak dapat terhubung ke sistem. Periksa koneksi Anda, lalu coba lagi.');
        } finally {
            setProcessing(false);
        }
    };

    const phoneError = serverErrors.telepon ?? (submitted ? validatePhone(form.telepon) : null);
    const formErrors: Partial<Record<keyof CustomerForm, string>> = submitted
        ? fields.reduce<Partial<Record<keyof CustomerForm, string>>>((errors, field) => {
            const error = validateField(field.name, form[field.name]);
            if (error) errors[field.name] = error;
            return errors;
        }, {})
        : {};
    if (submitted && step === 'new') {
        (['wilayah', 'kecamatan', 'kelurahan', 'kodePos'] as const).forEach((name) => {
            const error = validateField(name, form[name]);
            if (error) formErrors[name] = error;
        });
    }

    return (
        <div
            className="fixed inset-0 z-[100] flex items-center justify-center px-3 py-3 sm:px-6 sm:py-6"
            role="presentation"
        >
            <button
                type="button"
                aria-label="Tutup Customer Gate"
                disabled={processing}
                onClick={handleClose}
                className={`absolute inset-0 cursor-default bg-slate-950/60 backdrop-blur-sm ${closing ? 'animate-fade-out' : 'animate-fade-in'}`}
                style={{ animationDuration: closing ? '400ms' : '550ms' }}
            />
            <div
                ref={dialogRef}
                role="dialog"
                aria-modal="true"
                aria-labelledby="customer-gate-title"
                aria-describedby="customer-gate-description"
                tabIndex={-1}
                className={`relative flex max-h-[calc(100dvh-1.5rem)] w-full max-w-xl ${closing ? 'animate-modal-out' : 'animate-modal-in'} flex-col overflow-hidden rounded-2xl bg-white shadow-lifted outline-none sm:max-h-[calc(100dvh-3rem)]`}
                style={{ animationDuration: closing ? '400ms' : '550ms' }}
            >
                <header className="shrink-0 bg-brand px-5 py-5 text-white sm:px-7 sm:py-6">
                    <div className="flex items-start gap-3">
                        {step !== 'choice' && (
                            <button
                                type="button"
                                disabled={processing}
                                onClick={() => {
                                    setStep('choice');
                                    setSubmitted(false);
                                    setServerErrors({});
                                    setSubmitError('');
                                }}
                                aria-label="Kembali ke pilihan status"
                                className="mt-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-white/90 transition duration-300 hover:bg-white/15 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                            >
                                <svg aria-hidden="true" viewBox="0 0 20 20" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M12.5 4 6.5 10l6 6M7 10h10" />
                                </svg>
                            </button>
                        )}
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-semibold uppercase tracking-[0.14em] text-red-100">Agency Finance Honda</p>
                            <h2 id="customer-gate-title" className="mt-1 text-xl font-bold sm:text-2xl">Sebelum Melanjutkan</h2>
                            <p id="customer-gate-description" className="mt-2 text-sm leading-6 text-red-50">
                                {step === 'choice'
                                    ? 'Apakah Anda sudah pernah melakukan pengajuan?'
                                    : step === 'existing'
                                        ? 'Masukkan nomor telepon yang pernah digunakan saat pengajuan.'
                                        : 'Lengkapi biodata untuk menyiapkan akses layanan.'}
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={handleClose}
                            disabled={processing}
                            aria-label="Tutup"
                            className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-white/90 transition duration-300 hover:bg-white/15 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                        >
                            <svg aria-hidden="true" viewBox="0 0 20 20" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round">
                                <path d="m5 5 10 10M15 5 5 15" />
                            </svg>
                        </button>
                    </div>
                </header>

                <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain">
                    {step === 'choice' ? (
                        <div className="p-5 sm:p-7">
                            <p className="text-base font-semibold leading-6 text-slate-900">Apakah Anda sudah pernah melakukan pengajuan?</p>
                            <div className="mt-5 grid gap-3">
                                <button
                                    type="button"
                                    onClick={() => { setStep('existing'); setSubmitted(false); setServerErrors({}); setSubmitError(''); }}
                                    className="group flex min-h-16 items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 text-left transition duration-300 hover:border-red-300 hover:bg-red-50/50 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-100"
                                >
                                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-700 transition duration-300 group-hover:bg-red-600 group-hover:text-white">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                                            <circle cx="12" cy="8" r="4" /><path d="M5 21v-1a7 7 0 0 1 14 0v1" />
                                        </svg>
                                    </span>
                                    <span className="flex-1 font-semibold text-slate-900">Sudah Pernah Pengajuan</span>
                                    <svg aria-hidden="true" viewBox="0 0 20 20" className="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                                        <path d="M3 10h14m-6-6 6 6-6 6" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => { setStep('new'); setSubmitted(false); setServerErrors({}); setSubmitError(''); }}
                                    className="group flex min-h-16 items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 text-left transition duration-300 hover:border-red-300 hover:bg-red-50/50 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-100"
                                >
                                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-700 transition duration-300 group-hover:bg-red-600 group-hover:text-white">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                                            <circle cx="12" cy="8" r="4" /><path d="M5 21v-1a7 7 0 0 1 14 0v1m-7-7v5m-2.5-2.5h5" />
                                        </svg>
                                    </span>
                                    <span className="flex-1 font-semibold text-slate-900">Belum Pernah Pengajuan</span>
                                    <svg aria-hidden="true" viewBox="0 0 20 20" className="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                                        <path d="M3 10h14m-6-6 6 6-6 6" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    ) : (
                        <form onSubmit={handleSubmit} noValidate className="p-5 sm:p-7">
                            {step === 'existing' ? (
                                <div>
                                    <label htmlFor="gate-telepon" className="block text-sm font-semibold text-slate-800">Nomor Telepon</label>
                                    <input
                                        id="gate-telepon"
                                        name="telepon"
                                        type="tel"
                                        inputMode="numeric"
                                        autoComplete="tel"
                                        maxLength={15}
                                        value={form.telepon}
                                        onChange={(event) => updateField('telepon', event.target.value)}
                                        placeholder="081315379958"
                                        aria-invalid={Boolean(phoneError)}
                                        aria-describedby={phoneError ? 'gate-telepon-error' : undefined}
                                        className="mt-2 min-h-12 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base text-slate-950 outline-none transition duration-300 placeholder:text-slate-400 focus:border-red-500 focus:ring-4 focus:ring-red-100"
                                    />
                                    {phoneError && <p id="gate-telepon-error" className="mt-2 text-sm text-red-700">{phoneError}</p>}
                                </div>
                            ) : (
                                <div className="space-y-5">
                                    <div className="grid gap-x-4 gap-y-4 sm:grid-cols-2">
                                        {fields.filter((field) => field.name !== 'namaJalan').map((field) => {
                                        const error = serverErrors[field.name] ?? formErrors[field.name];
                                        return (
                                            <div key={field.name} className="sm:col-span-2">
                                                <label htmlFor={`gate-${field.name}`} className="block text-sm font-semibold text-slate-800">{field.label}</label>
                                                <input
                                                    id={`gate-${field.name}`}
                                                    name={field.name}
                                                    type={field.numeric ? 'tel' : 'text'}
                                                    inputMode={field.numeric ? 'numeric' : 'text'}
                                                    autoComplete={field.autocomplete}
                                                    maxLength={field.name === 'telepon' ? 15 : field.name === 'kodePos' ? 6 : undefined}
                                                    value={form[field.name]}
                                                    onChange={(event) => updateField(field.name, event.target.value)}
                                                    placeholder={field.placeholder}
                                                    aria-invalid={Boolean(error)}
                                                    aria-describedby={error ? `gate-${field.name}-error` : undefined}
                                                    className="mt-2 min-h-12 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base text-slate-950 outline-none transition duration-300 placeholder:text-slate-400 focus:border-red-500 focus:ring-4 focus:ring-red-100"
                                                />
                                                {error && <p id={`gate-${field.name}-error`} className="mt-1.5 text-xs text-red-700">{error}</p>}
                                            </div>
                                        );
                                        })}
                                    </div>
                                    <RegionalAddressFields
                                        value={{
                                            wilayah: form.wilayah,
                                            kecamatan: form.kecamatan,
                                            kelurahan: form.kelurahan,
                                            kodePos: form.kodePos,
                                        }}
                                        errors={{
                                            wilayah: serverErrors.wilayah ?? formErrors.wilayah,
                                            kecamatan: serverErrors.kecamatan ?? formErrors.kecamatan,
                                            kelurahan: serverErrors.kelurahan ?? formErrors.kelurahan,
                                            kodePos: serverErrors.kodePos ?? formErrors.kodePos,
                                        }}
                                        onChange={(address) => {
                                            setForm((current) => ({ ...current, ...address }));
                                            setServerErrors((current) => ({
                                                ...current,
                                                wilayah: undefined,
                                                kecamatan: undefined,
                                                kelurahan: undefined,
                                                kodePos: undefined,
                                            }));
                                            setSubmitError('');
                                            setSubmitted(false);
                                        }}
                                    />
                                    <div>
                                        <label htmlFor="gate-namaJalan" className="block text-sm font-semibold text-slate-800">Nama Jalan</label>
                                        <input
                                            id="gate-namaJalan"
                                            name="namaJalan"
                                            type="text"
                                            autoComplete="street-address"
                                            value={form.namaJalan}
                                            onChange={(event) => updateField('namaJalan', event.target.value)}
                                            placeholder="Nama jalan, blok, atau nomor rumah"
                                            aria-invalid={Boolean(serverErrors.namaJalan ?? formErrors.namaJalan)}
                                            aria-describedby={serverErrors.namaJalan || formErrors.namaJalan ? 'gate-namaJalan-error' : undefined}
                                            className="mt-2 min-h-12 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base text-slate-950 outline-none transition duration-300 placeholder:text-slate-400 focus:border-red-500 focus:ring-4 focus:ring-red-100"
                                        />
                                        {(serverErrors.namaJalan ?? formErrors.namaJalan) && <p id="gate-namaJalan-error" className="mt-1.5 text-xs text-red-700">{serverErrors.namaJalan ?? formErrors.namaJalan}</p>}
                                    </div>
                                </div>
                            )}

                            {submitError && <p role="alert" className="mt-5 rounded-xl border border-red-200 bg-red-50 p-3.5 text-sm leading-6 text-red-800">{submitError}</p>}

                            <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button
                                    type="button"
                                    disabled={processing}
                                    onClick={() => { setStep('choice'); setSubmitted(false); setServerErrors({}); setSubmitError(''); }}
                                    className="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition duration-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-slate-200"
                                >
                                    Kembali
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex min-h-12 items-center justify-center rounded-xl bg-red-600 px-6 py-3 text-sm font-semibold text-white transition duration-300 hover:bg-red-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-200"
                                >
                                    {processing ? 'Memproses...' : 'Lanjutkan'}
                                </button>
                            </div>
                        </form>
                    )}
                </div>
            </div>
        </div>
    );
}
