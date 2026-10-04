import { router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

export default function LoginPage() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState('');

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (processing) return;

        setError('');
        router.post('/admin/login', { email, password }, {
            onStart: () => setProcessing(true),
            onError: () => setError('Email atau kata sandi tidak valid.'),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
            <section className="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <p className="text-sm font-semibold uppercase tracking-[0.16em] text-red-700">Internal</p>
                <h1 className="mt-2 text-2xl font-bold text-slate-950">Admin Panel</h1>
                <p className="mt-2 text-sm leading-6 text-slate-600">Masuk menggunakan akun staff yang memiliki akses admin.</p>

                <form onSubmit={submit} className="mt-6 space-y-4">
                    <label className="block">
                        <span className="mb-2 block text-sm font-semibold text-slate-700">Email</span>
                        <input
                            type="email"
                            autoComplete="username"
                            required
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            className="min-h-12 w-full rounded-xl border border-slate-200 px-4 text-sm outline-none focus:border-red-500 focus:ring-4 focus:ring-red-100"
                        />
                    </label>
                    <label className="block">
                        <span className="mb-2 block text-sm font-semibold text-slate-700">Kata sandi</span>
                        <input
                            type="password"
                            autoComplete="current-password"
                            required
                            value={password}
                            onChange={(event) => setPassword(event.target.value)}
                            className="min-h-12 w-full rounded-xl border border-slate-200 px-4 text-sm outline-none focus:border-red-500 focus:ring-4 focus:ring-red-100"
                        />
                    </label>
                    {error && <p role="alert" className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
                    <button
                        type="submit"
                        disabled={processing}
                        className="min-h-12 w-full rounded-xl bg-red-700 px-5 text-sm font-semibold text-white transition-colors duration-300 hover:bg-red-800 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {processing ? 'Memeriksa...' : 'Masuk'}
                    </button>
                </form>
            </section>
        </main>
    );
}
