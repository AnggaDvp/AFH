import { router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/layouts/AdminLayout';
import AdminPageHeading from '@/components/AdminPageHeading';
import { formatFileSize } from '@/types/chat';

type Customer = { id: number; name: string; phone: string; region: string | null; district: string | null; village: string | null };
type Document = { id: number; type: string; filename: string; mime: string; size: number; status: string; uploadedAt: string | null; downloadUrl: string };
type Props = {
    adminName: string; adminRole: 'admin' | 'staff'; statuses: string[];
    application: {
        id: number; number: string; status: string; source: string | null; createdAt: string;
        submittedAt: string | null; updatedAt: string; customer: Customer;
        motor: { name: string; family: string; brochure: string | null; cashPrice: number | string | null } | null;
        financing: { downPayment: number | string | null; tenorMonths: number | null; installmentAmount: number | string | null; installmentSource: string | null };
        documents: Document[];
        conversations: { id: number; service: string; motor: string | null; status: string; lastMessage: string }[];
        history: { from: string | null; to: string; changedBy: string | null; note: string | null; createdAt: string }[];
    };
};
const labels: Record<string, string> = { draft: 'Draft', submitted: 'Dikirim', under_review: 'Review', need_documents: 'Perlu dokumen', approved: 'Disetujui', rejected: 'Ditolak', cancelled: 'Dibatalkan' };
const money = (value: number | string | null) => value === null ? 'Belum tersedia' : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));

export default function ShowPage({ adminName, adminRole, application, statuses }: Props) {
    const [status, setStatus] = useState(application.status);
    const [note, setNote] = useState('');
    const [processing, setProcessing] = useState(false);
    const [message, setMessage] = useState('');
    const updateStatus = () => {
        if (processing || status === application.status) return;
        setMessage('');
        router.post(`/admin/pengajuan/${application.id}/status`, { status, note }, {
            onStart: () => setProcessing(true),
            onSuccess: () => { setNote(''); setMessage('Status berhasil diperbarui.'); },
            onError: () => setMessage('Status belum dapat diperbarui. Periksa data lalu coba lagi.'),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AdminLayout adminName={adminName} adminRole={adminRole}>
            <section className="mx-auto max-w-5xl space-y-5">
                <a href="/admin/pengajuan" className="inline-flex min-h-10 items-center text-sm font-semibold text-red-700 hover:underline">Kembali ke pengajuan</a>
                <AdminPageHeading title={application.number} description={`Dibuat ${new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(application.createdAt))}`} />
                <section className="grid gap-4 lg:grid-cols-2">
                    <article className="rounded-2xl border border-slate-200 bg-white p-5">
                        <h2 className="font-semibold">Customer</h2>
                        <p className="mt-3 font-medium">{application.customer.name}</p>
                        <p className="mt-1 text-sm text-slate-600">{application.customer.phone}</p>
                        <p className="mt-3 text-sm text-slate-600">{[application.customer.region, application.customer.district, application.customer.village].filter(Boolean).join(' · ')}</p>
                    </article>
                    <article className="rounded-2xl border border-slate-200 bg-white p-5">
                        <h2 className="font-semibold">Motor dan pembiayaan</h2>
                        <dl className="mt-3 space-y-2 text-sm">
                            <div className="flex justify-between gap-3"><dt className="text-slate-500">Motor</dt><dd>{application.motor?.name ?? 'Belum tersedia'}</dd></div>
                            <div className="flex justify-between gap-3"><dt className="text-slate-500">Periode brosur</dt><dd>{application.motor?.brochure ?? '-'}</dd></div>
                            <div className="flex justify-between gap-3"><dt className="text-slate-500">Harga cash</dt><dd>{money(application.motor?.cashPrice ?? null)}</dd></div>
                            <div className="flex justify-between gap-3"><dt className="text-slate-500">DP</dt><dd>{money(application.financing.downPayment)}</dd></div>
                            <div className="flex justify-between gap-3"><dt className="text-slate-500">Tenor</dt><dd>{application.financing.tenorMonths ? `${application.financing.tenorMonths} bulan` : 'Belum tersedia'}</dd></div>
                            <div className="flex justify-between gap-3"><dt className="text-slate-500">Cicilan</dt><dd>{money(application.financing.installmentAmount)}</dd></div>
                            <div className="flex justify-between gap-3"><dt className="text-slate-500">Sumber cicilan</dt><dd>{application.financing.installmentSource ?? '-'}</dd></div>
                        </dl>
                    </article>
                </section>
                <section className="rounded-2xl border border-slate-200 bg-white p-5">
                    <h2 className="font-semibold">Status: {labels[application.status] ?? application.status}</h2>
                    <div className="mt-4 grid gap-3 sm:grid-cols-[1fr_2fr_auto]">
                        <label className="text-sm font-medium">Status baru<select value={status} onChange={(event) => setStatus(event.target.value)} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3">{statuses.map((item) => <option key={item} value={item}>{labels[item] ?? item}</option>)}</select></label>
                        <label className="text-sm font-medium">Catatan perubahan<textarea value={note} onChange={(event) => setNote(event.target.value)} maxLength={1000} rows={2} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2" /></label>
                        <button type="button" disabled={processing || status === application.status} onClick={updateStatus} className="min-h-11 self-end rounded-xl bg-red-700 px-4 text-sm font-semibold text-white hover:bg-red-800 disabled:opacity-50">{processing ? 'Menyimpan...' : 'Simpan status'}</button>
                    </div>
                    {message && <p role="status" className="mt-3 text-sm text-slate-600">{message}</p>}
                </section>
                <section className="rounded-2xl border border-slate-200 bg-white p-5">
                    <h2 className="font-semibold">Riwayat status</h2>
                    {application.history.length === 0 ? <p className="mt-3 text-sm text-slate-500">Belum ada perubahan status.</p> : <ol className="mt-3 space-y-3">{application.history.map((item, index) => <li key={`${item.createdAt}-${index}`} className="border-l-2 border-red-200 pl-4 text-sm"><p className="font-medium">{item.from ? `${labels[item.from] ?? item.from} → ` : ''}{labels[item.to] ?? item.to}</p><p className="mt-1 text-xs text-slate-500">{item.changedBy ?? 'Sistem'} · {new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(item.createdAt))}</p>{item.note && <p className="mt-1 text-slate-600">{item.note}</p>}</li>)}</ol>}
                </section>
                <section className="rounded-2xl border border-slate-200 bg-white p-5">
                    <h2 className="font-semibold">Dokumen</h2>
                    {application.documents.length === 0 ? <p className="mt-3 text-sm text-slate-500">Belum ada dokumen aplikasi.</p> : <ul className="mt-3 divide-y">{application.documents.map((document) => <li key={document.id} className="flex flex-wrap justify-between gap-3 py-3 text-sm"><span>{document.type} · {document.filename} · {formatFileSize(document.size)} · {document.status}</span><a href={document.downloadUrl} className="font-semibold text-red-700 hover:underline">Download</a></li>)}</ul>}
                </section>
                <section className="rounded-2xl border border-slate-200 bg-white p-5">
                    <h2 className="font-semibold">Chat customer</h2>
                    {application.conversations.length === 0 ? <p className="mt-3 text-sm text-slate-500">Belum ada percakapan customer.</p> : <ul className="mt-3 space-y-2">{application.conversations.map((conversation) => <li key={conversation.id}><a href={`/admin/chat/${conversation.id}`} className="text-sm font-semibold text-red-700 hover:underline">{conversation.service}{conversation.motor ? ` · ${conversation.motor}` : ''} · {conversation.status}</a></li>)}</ul>}
                </section>
            </section>
        </AdminLayout>
    );
}
