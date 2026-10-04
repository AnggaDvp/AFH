type Props = {
    eyebrow: string;
    title: string;
    description?: string;
};

export default function CustomerPageHeader({ eyebrow, title, description }: Props) {
    return (
        <header className="max-w-3xl">
            <p className="customer-eyebrow">{eyebrow}</p>
            <h1 className="mt-2 text-3xl font-bold leading-tight tracking-tight text-slate-950 sm:text-4xl">{title}</h1>
            {description && <p className="mt-3 text-base leading-7 text-slate-600">{description}</p>}
        </header>
    );
}
