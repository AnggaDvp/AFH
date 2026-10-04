import { Link } from '@inertiajs/react';

type LinkItem = { url: string | null; label: string; active: boolean };
type Props = { links: LinkItem[]; from: number | null; to: number | null; total: number };

export default function AdminPagination({ links, from, to, total }: Props) {
    if (total <= 0) return null;
    return (
        <nav aria-label="Navigasi halaman" className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3">
            <p className="text-xs text-slate-500">
                Menampilkan {from ?? 0}-{to ?? 0} dari {total.toLocaleString('id-ID')}
            </p>
            <div className="flex flex-wrap gap-1">
                {links.map((item, index) => item.url ? (
                    <Link key={`${item.label}-${index}`} href={item.url} preserveScroll
                        aria-current={item.active ? 'page' : undefined}
                        className={`inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg px-3 text-sm ${
                            item.active ? 'bg-red-700 font-semibold text-white' : 'border border-slate-200 text-slate-700 hover:bg-slate-50'
                        }`}>
                        {item.label.replace('&laquo;', '‹').replace('&raquo;', '›')}
                    </Link>
                ) : (
                    <span key={`${item.label}-${index}`} aria-disabled="true"
                        className="inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg px-3 text-sm text-slate-300">
                        {item.label.replace('&laquo;', '‹').replace('&raquo;', '›')}
                    </span>
                ))}
            </div>
        </nav>
    );
}
