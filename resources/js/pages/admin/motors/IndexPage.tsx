import AdminLayout from '@/layouts/AdminLayout';
import AdminPageHeading from '@/components/AdminPageHeading';
import AdminPagination from '@/components/AdminPagination';

type Motor = {
    id: number; code: string; name: string; family: string; status: string;
    cashPrice: number | null; brochurePeriod: string | null; brochureStatus: string | null;
    financingOptionCount: number; applicationCount: number;
};
type Brochure = { id: number; period_label: string; source_name: string; status: string; items_count: number; financing_options_count: number };
type Props = {
    adminName: string;
    adminRole: 'admin' | 'staff';
    motors: { data: Motor[]; links: { url: string | null; label: string; active: boolean }[]; from: number | null; to: number | null; total: number };
    families: string[];
    brochures: Brochure[];
    filters: { search?: string; family?: string; status?: string; brochure_id?: string };
};

function price(value: number | null): string {
    return value === null ? 'Belum tersedia' : new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
    }).format(value);
}

export default function IndexPage({ adminName, adminRole, motors, families, brochures, filters }: Props) {
    return (
        <AdminLayout adminName={adminName} adminRole={adminRole}>
            <section className="mx-auto max-w-7xl">
                <AdminPageHeading title="Motor Honda" description="Katalog read-only berdasarkan produk dan brosur aktif. Data tidak dapat diubah dari halaman ini." />
                <section className="mb-5 rounded-2xl border border-slate-200 bg-white p-4">
                    <h2 className="font-semibold text-slate-900">Periode brosur</h2>
                    {brochures.length === 0 ? <p className="mt-2 text-sm text-slate-500">Belum ada data brosur.</p> : (
                        <div className="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            {brochures.map((brochure) => (
                                <article key={brochure.id} className="rounded-xl border border-slate-200 p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <h3 className="font-semibold text-slate-900">{brochure.period_label}</h3>
                                        <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${brochure.status === 'active' ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600'}`}>{brochure.status}</span>
                                    </div>
                                    <p className="mt-1 text-xs text-slate-500">{brochure.source_name}</p>
                                    <p className="mt-3 text-xs text-slate-600">{brochure.items_count} motor · {brochure.financing_options_count} opsi pembiayaan</p>
                                </article>
                            ))}
                        </div>
                    )}
                </section>
                <form action="/admin/motors" method="get" className="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-5">
                    <label className="text-sm font-medium text-slate-700">Cari motor<input name="search" defaultValue={filters.search ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" /></label>
                    <label className="text-sm font-medium text-slate-700">Family
                        <select name="family" defaultValue={filters.family ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3">
                            <option value="">Semua family</option>{families.map((family) => <option key={family}>{family}</option>)}
                        </select>
                    </label>
                    <label className="text-sm font-medium text-slate-700">Status
                        <select name="status" defaultValue={filters.status ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3">
                            <option value="">Semua status</option><option value="active">Active</option><option value="archived">Archived</option><option value="inactive">Inactive</option>
                        </select>
                    </label>
                    <label className="text-sm font-medium text-slate-700">Brosur
                        <select name="brochure_id" defaultValue={filters.brochure_id ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3">
                            <option value="">Semua periode</option>{brochures.map((brochure) => <option key={brochure.id} value={brochure.id}>{brochure.period_label}</option>)}
                        </select>
                    </label>
                    <button type="submit" className="min-h-11 self-end rounded-xl bg-red-700 px-4 text-sm font-semibold text-white hover:bg-red-800">Terapkan filter</button>
                </form>
                <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                    <table className="w-full min-w-[950px] text-left text-sm">
                        <thead className="bg-slate-100 text-xs uppercase text-slate-600"><tr>
                            <th className="px-4 py-3">Motor</th><th className="px-4 py-3">Family</th><th className="px-4 py-3">Harga cash</th>
                            <th className="px-4 py-3">Periode brosur</th><th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3">Opsi pembiayaan</th><th className="px-4 py-3">Pengajuan</th>
                        </tr></thead>
                        <tbody className="divide-y divide-slate-100">
                            {motors.data.map((motor) => <tr key={motor.id}>
                                <td className="px-4 py-3"><p className="font-semibold text-slate-900">{motor.name}</p><p className="mt-1 text-xs text-slate-500">{motor.code}</p></td>
                                <td className="px-4 py-3">{motor.family}</td><td className="px-4 py-3">{price(motor.cashPrice)}</td>
                                <td className="px-4 py-3">{motor.brochurePeriod ?? '-'}</td><td className="px-4 py-3">{motor.status}</td>
                                <td className="px-4 py-3">{motor.financingOptionCount}</td><td className="px-4 py-3">{motor.applicationCount}</td>
                            </tr>)}
                            {motors.data.length === 0 && <tr><td colSpan={7} className="px-4 py-10 text-center text-slate-500">Tidak ada motor yang sesuai filter.</td></tr>}
                        </tbody>
                    </table>
                    <AdminPagination links={motors.links} from={motors.from} to={motors.to} total={motors.total} />
                </div>
            </section>
        </AdminLayout>
    );
}
