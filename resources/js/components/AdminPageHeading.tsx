type Props = { eyebrow?: string; title: string; description: string };

export default function AdminPageHeading({ eyebrow = 'Admin Panel', title, description }: Props) {
    return (
        <header className="mb-6">
            <p className="text-sm font-semibold text-red-700">{eyebrow}</p>
            <h1 className="mt-1 text-2xl font-bold text-slate-950 sm:text-3xl">{title}</h1>
            <p className="mt-2 text-sm leading-6 text-slate-600">{description}</p>
        </header>
    );
}
