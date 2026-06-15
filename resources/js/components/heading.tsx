export default function Heading({
    title,
    description,
    variant = 'default',
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
}) {
    if (variant === 'small') {
        return (
            <header>
                <h2 className="mb-0.5 text-base font-medium">{title}</h2>
                {description && (
                    <p className="text-sm text-muted-foreground">{description}</p>
                )}
            </header>
        );
    }

    return (
        <header className="mb-8">
            <h2 className="font-serif text-3xl font-light tracking-tight">{title}</h2>
            {description && (
                <p className="mt-1.5 max-w-2xl text-sm text-muted-foreground">{description}</p>
            )}
            <div className="rule-gold mt-3.5" />
        </header>
    );
}
