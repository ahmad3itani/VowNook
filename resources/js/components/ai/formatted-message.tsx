import { ReactNode } from 'react';

/** Render **bold** segments as real nodes (no HTML injection — just text + <strong>). */
function renderInline(text: string): ReactNode[] {
    return text.split(/(\*\*[^*]+\*\*)/g).map((part, i) =>
        part.startsWith('**') && part.endsWith('**') ? (
            <strong key={i} className="font-semibold text-[#1e1b17]">
                {part.slice(2, -2)}
            </strong>
        ) : (
            <span key={i}>{part}</span>
        ),
    );
}

/**
 * Turn the AI's lightweight markdown (paragraphs, **bold**, "-" bullets) into
 * clean, styled output. Shared by the help bot and the AI planner chat so both
 * read the same way. Colour is inherited from the parent bubble.
 */
export function FormattedMessage({ text }: { text: string }) {
    const blocks = text.trim().split(/\n{2,}/);

    return (
        <div className="flex flex-col gap-2 leading-relaxed">
            {blocks.map((block, bi) => {
                const lines = block.split('\n');
                const isList =
                    lines.length > 0 &&
                    lines.every((l) => /^\s*[-*•]\s+/.test(l));

                if (isList) {
                    return (
                        <ul key={bi} className="flex flex-col gap-1.5">
                            {lines.map((l, li) => (
                                <li key={li} className="flex gap-2">
                                    <span
                                        className="mt-[7px] size-1.5 shrink-0 rounded-full bg-[#c79a3f]"
                                        aria-hidden
                                    />
                                    <span>
                                        {renderInline(
                                            l.replace(/^\s*[-*•]\s+/, ''),
                                        )}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    );
                }

                return (
                    <p key={bi} className="whitespace-pre-line">
                        {renderInline(block)}
                    </p>
                );
            })}
        </div>
    );
}
