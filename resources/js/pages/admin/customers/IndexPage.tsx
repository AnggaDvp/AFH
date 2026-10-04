import { Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import AdminPageHeading from '@/components/AdminPageHeading';
import AdminPagination from '@/components/AdminPagination';

type CustomerRow = {
    id: number;
    name: string;
    phone: string;
    region: string | null;
    district: string | null;
    village: string | null;
    createdAt: string;
    creditApplicationCount: number;
    danaTunaiApplicationCount: number;
    conversationCount: number;
};
type Props = {
    adminName: string;
    adminRole: 'admin' | 'staff';
    customers: { data: CustomerRow[]; links: { url: string | null; label: string; active: boolean }[]; from: number | null; to: number | null; total: number };
    regions: { id: number; name: string }[];
    filters: { search?: string; region_id?: string; from?: string; to?: string };
};

export default function IndexPage({ adminName, adminRole, customers, regions, filters }: Props) {
    return (
        <AdminLayout adminName={adminName} adminRole={adminRole}>
            <section className="mx-auto max-w-7xl">
                <AdminPageHeading title="Customers" description="Cari customer dan tinjau ringkasan lokasi, pengajuan, serta percakapan." />
                <form action="/admin/customers" method="get" className="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-5">
                    <label className="text-sm font-medium text-slate-700">
                        Nama atau telepon
                        <input name="search" defaultValue={filters.search ?? ''} maxLength={150} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <label className="text-sm font-medium text-slate-700">
                        Wilayah
                        <select name="region_id" defaultValue={filters.region_id ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3">
                            <option value="">Semua wilayah</option>
                            {regions.map((region) => <option key={region.id} value={region.id}>{region.name}</option>)}
                        </select>
                    </label>
                    <label className="text-sm font-medium text-slate-700">
                        Dari tanggal
                        <input type="date" name="from" defaultValue={filters.from ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <label className="text-sm font-medium text-slate-700">
                        Sampai tanggal
                        <input type="date" name="to" defaultValue={filters.to ?? ''} className="mt-1.5 min-h-11 w-full rounded-xl border border-slate-200 px-3" />
                    </label>
                    <button type="submit" className="min-h-11 self-end rounded-xl bg-red-700 px-4 text-sm font-semibold text-white hover:bg-red-800">Cari</button>
                </form>
                <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                    <table className="w-full min-w-[1000px] text-left text-sm">
                        <thead className="bg-slate-100 text-xs uppercase text-slate-600">
                            <tr>
                                <th className="px-4 py-3">Nama</th><th className="px-4 py-3">Telepon</th>
                                <th className="px-4 py-3">Wilayah</th><th className="px-4 py-3">Kecamatan</th>
                                <th className="px-4 py-3">Kelurahan/Desa</th><th className="px-4 py-3">Terdaftar</th>
                                <th className="px-4 py-3">Pengajuan</th><th className="px-4 py-3">Chat</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {customers.data.map((customer) => (
                                <tr key={customer.id} className="hover:bg-slate-50">
                                    <td className="px-4 py-3"><Link href={`/admin/customers/${customer.id}`} className="font-semibold text-red-700 hover:underline">{customer.name}</Link></td>
                                    <td className="px-4 py-3">{customer.phone}</td><td className="px-4 py-3">{customer.region ?? '-'}</td>
                                    <td className="px-4 py-3">{customer.district ?? '-'}</td><td className="px-4 py-3">{customer.village ?? '-'}</td>
                                    <td className="px-4 py-3">{new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(customer.createdAt))}</td>
                                    <td className="px-4 py-3">{customer.creditApplicationCount + customer.danaTunaiApplicationCount}</td>
                                    <td className="px-4 py-3">{customer.conversationCount}</td>
                                </tr>
                            ))}
                            {customers.data.length === 0 && <tr><td colSpan={8} className="px-4 py-10 text-center text-slate-500">Tidak ada customer yang cocok dengan filter.</td></tr>}
                        </tbody>
                    </table>
                    <AdminPagination links={customers.links} from={customers.from} to={customers.to} total={customers.total} />
                </div>
            </section>
        </AdminLayout>
    );
}
