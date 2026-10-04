import AdminLayout from '@/layouts/AdminLayout';
import AdminPageHeading from '@/components/AdminPageHeading';
import AdminPagination from '@/components/AdminPagination';

type ReportRow = Record<string, string | number | null>;
type ReportFilters = {
    report_type: string;
    from?: string;
    to?: string;
    region_id?: string;
    motor_id?: string;
    status?: string;
    source?: string;
};
type Props = {
    adminName: string;
    adminRole: 'admin' | 'staff';
    reportTypes: Record<string, string>;
    columns: Record<string, string>;
    results: {
        data: ReportRow[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: ReportFilters;
    regions: { id: number; name: string }[];
    motors: { id: number; name: string }[];
    periodLabel: string;
};

export default function IndexPage({
    adminName,
    adminRole,
    reportTypes,
    columns,
    results,
    filters,
    regions,
    motors,
    periodLabel,
}: Props) {
    const exportQuery = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (value) exportQuery.set(key, value);
    });

    return (
        <AdminLayout adminName={adminName} adminRole={adminRole}>
            <section className="mx-auto max-w-7xl">
                <AdminPageHeading title="Reports" description="Tinjau data operasional dan unduh laporan Excel sesuai filter." />
                <form action="/admin/reports" method="get" className="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-4">
                    <label className="text-sm font-medium text-slate-700">
                        Jenis laporan
                        <select name="report_type" defaultValue={filters.report_type} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3">
                            {Object.entries(reportTypes).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                        </select>
                    </label>
                    <label className="text-sm font-medium text-slate-700">
                        Wilayah
                        <select name="region_id" defaultValue={filters.region_id ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3">
                            <option value="">Semua wilayah</option>
                            {regions.map((region) => <option key={region.id} value={region.id}>{region.name}</option>)}
                        </select>
                    </label>
                    <label className="text-sm font-medium text-slate-700">
                        Motor
                        <select name="motor_id" defaultValue={filters.motor_id ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3">
                            <option value="">Semua motor</option>
                            {motors.map((motor) => <option key={motor.id} value={motor.id}>{motor.name}</option>)}
                        </select>
                    </label>
                    <label className="text-sm font-medium text-slate-700">
                        Status
                        <input name="status" maxLength={30} defaultValue={filters.status ?? ''} placeholder="Contoh: submitted" className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <label className="text-sm font-medium text-slate-700">
                        Source
                        <input name="source" maxLength={100} defaultValue={filters.source ?? ''} placeholder="Sumber pengajuan" className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <label className="text-sm font-medium text-slate-700">
                        Dari tanggal
                        <input type="date" name="from" defaultValue={filters.from ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <label className="text-sm font-medium text-slate-700">
                        Sampai tanggal
                        <input type="date" name="to" defaultValue={filters.to ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <div className="flex flex-wrap items-end gap-2">
                        <button type="submit" className="min-h-11 rounded-xl bg-red-700 px-4 text-sm font-semibold text-white hover:bg-red-800">Tampilkan</button>
                        <a href={`/admin/reports/export?${exportQuery.toString()}`} className="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Export Excel
                        </a>
                    </div>
                </form>

                <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 className="text-lg font-bold text-slate-900">{reportTypes[filters.report_type]}</h2>
                        <p className="text-sm text-slate-500">Periode: {periodLabel} · Total records: {results.total.toLocaleString('id-ID')}</p>
                    </div>
                    <span className="text-xs text-slate-500">Filter status dan source diterapkan bila tersedia pada jenis laporan.</span>
                </div>

                <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                    <table className="w-full min-w-[900px] text-left text-sm">
                        <thead className="bg-slate-100 text-xs uppercase text-slate-600">
                            <tr>{Object.values(columns).map((label) => <th key={label} className="whitespace-nowrap px-4 py-3">{label}</th>)}</tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {results.data.map((row, index) => (
                                <tr key={`${index}-${String(row.application_number ?? row.customer_id ?? row.conversation ?? row.registration_date ?? '')}`} className="hover:bg-slate-50">
                                    {Object.keys(columns).map((column) => (
                                        <td key={column} className="max-w-80 whitespace-nowrap px-4 py-3 text-slate-700">
                                            {row[column] === null || row[column] === undefined || row[column] === '' ? '-' : String(row[column])}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                            {results.data.length === 0 && (
                                <tr><td colSpan={Object.keys(columns).length} className="px-4 py-10 text-center text-slate-500">Tidak ada data untuk filter yang dipilih.</td></tr>
                            )}
                        </tbody>
                    </table>
                    <AdminPagination links={results.links} from={results.from} to={results.to} total={results.total} />
                </div>
            </section>
        </AdminLayout>
    );
}
