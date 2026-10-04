import { Link } from '@inertiajs/react';

type Props = {
    message: string;
    backUrl: string;
};

export default function ChatErrorPage({ message, backUrl }: Props) {
    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-50 p-6">
            <section className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 text-center">
                <h1 className="text-xl font-bold text-slate-950">Chat belum dapat dibuka</h1>
                <p role="alert" className="mt-3 text-sm leading-6 text-slate-600">{message}</p>
                <Link href={backUrl} className="mt-6 inline-flex min-h-12 items-center rounded-xl bg-red-700 px-5 text-sm font-semibold text-white hover:bg-red-800">
                    Kembali ke daftar chat
                </Link>
            </section>
        </main>
    );
}
