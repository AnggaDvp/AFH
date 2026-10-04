import AdminLayout from '@/layouts/AdminLayout';

type Props = {
    adminName: string;
    adminRole: 'admin' | 'staff';
    filters: { period: string; from: string | null; to: string | null };
    periodLabel: string;
    kpis: {
        customers: { total: number; inPeriod: number; today: number; thisMonth: number };
        motors: { active: number; interestInPeriod: number; interestToday: number; interestThisMonth: number };
        creditApplications: { totalInPeriod: number; pendingReview: number; byStatus: Record<string, number> };
        danaTunai: { totalInPeriod: number; byStatus: Record<string, number> };
        chat: { open: number; unread: number; activeInPeriod: number };
        documents: { waitingReview: number };
        whatsapp: { queued: number; sending: number; sent: number; failed: number };
    };
};

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    submitted: 'Dikirim',
    under_review: 'Ditinjau',
    need_documents: 'Perlu dokumen',
    approved: 'Disetujui',
    rejected: 'Ditolak',
    cancelled: 'Dibatalkan',
};

function Kpi({ label, value, detail }: { label: string; value: number; detail: string }) {
    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-5">
            <p className="text-sm font-medium text-slate-600">{label}</p>
            <p className="mt-3 text-3xl font-bold tabular-nums text-slate-950">{value.toLocaleString('id-ID')}</p>
            <p className="mt-2 text-xs text-slate-500">{detail}</p>
        </article>
    );
}

function StatusCounts({ title, statuses }: { title: string; statuses: Record<string, number> }) {
    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 className="font-semibold text-slate-900">{title}</h2>
            <dl className="mt-4 grid grid-cols-2 gap-x-5 gap-y-3">
                {Object.entries(statuses).map(([status, count]) => (
                    <div key={status} className="flex items-center justify-between gap-2 text-sm">
                        <dt className="text-slate-600">{statusLabels[status] ?? status}</dt>
                        <dd className="font-semibold tabular-nums text-slate-900">{count.toLocaleString('id-ID')}</dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}

export default function IndexPage({ adminName, adminRole, filters, periodLabel, kpis }: Props) {
    return (
        <AdminLayout adminName={adminName} adminRole={adminRole}>
            <section className="mx-auto max-w-5xl">
                <p className="text-sm font-semibold text-red-700">Dashboard</p>
                <h1 className="mt-2 text-2xl font-bold text-slate-950 sm:text-3xl">Selamat datang, {adminName}</h1>
                <p className="mt-2 text-sm text-slate-600">Ringkasan operasional berdasarkan data database.</p>

                <form action="/admin" method="get" className="mt-6 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4">
                    <label className="min-w-44 flex-1 text-sm font-medium text-slate-700">
                        Periode
                        <select name="period" defaultValue={filters.period} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3">
                            <option value="today">Hari ini</option>
                            <option value="7days">7 hari terakhir</option>
                            <option value="month">Bulan ini</option>
                            <option value="custom">Rentang tanggal</option>
                        </select>
                    </label>
                    <label className="min-w-36 flex-1 text-sm font-medium text-slate-700">
                        Dari
                        <input type="date" name="from" defaultValue={filters.from ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <label className="min-w-36 flex-1 text-sm font-medium text-slate-700">
                        Sampai
                        <input type="date" name="to" defaultValue={filters.to ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <button type="submit" className="min-h-11 rounded-xl bg-red-700 px-5 text-sm font-semibold text-white hover:bg-red-800">Terapkan</button>
                    <p className="w-full text-xs text-slate-500">Periode aktif: {periodLabel}</p>
                </form>

                <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <Kpi label="Total customer" value={kpis.customers.total} detail={`${kpis.customers.inPeriod} registrasi pada periode`} />
                    <Kpi label="Customer baru hari ini" value={kpis.customers.today} detail={`${kpis.customers.thisMonth} pada bulan ini`} />
                    <Kpi label="Motor aktif" value={kpis.motors.active} detail={`${kpis.motors.interestInPeriod} minat pada periode`} />
                    <Kpi label="Pengajuan kredit" value={kpis.creditApplications.totalInPeriod} detail={`${kpis.creditApplications.pendingReview} menunggu review`} />
                    <Kpi label="Dana Tunai" value={kpis.danaTunai.totalInPeriod} detail="Jumlah tercatat pada periode" />
                    <Kpi label="Chat belum dibaca" value={kpis.chat.unread} detail={`${kpis.chat.open} percakapan terbuka`} />
                    <Kpi label="Chat aktif pada periode" value={kpis.chat.activeInPeriod} detail="Percakapan dengan aktivitas terbaru" />
                    <Kpi label="Dokumen menunggu pemeriksaan" value={kpis.documents.waitingReview} detail="Berdasarkan status dokumen aplikasi" />
                    <Kpi label="WhatsApp queued" value={kpis.whatsapp.queued} detail={`${kpis.whatsapp.sending} mengirim · ${kpis.whatsapp.sent} terkirim · ${kpis.whatsapp.failed} gagal`} />
                </div>

                <div className="mt-5 grid gap-4 lg:grid-cols-2">
                    <StatusCounts title="Status pengajuan kredit pada periode" statuses={kpis.creditApplications.byStatus} />
                    <StatusCounts title="Status Dana Tunai pada periode" statuses={kpis.danaTunai.byStatus} />
                </div>
            </section>
        </AdminLayout>
    );
}
