import Markdown from 'react-markdown';
import { cn } from '@/lib/utils';

/**
 * Renders AI-generated text that may contain simple Markdown (bold, lists,
 * paragraphs) instead of dumping the raw `**syntax**` on screen.
 */
export function MarkdownText({
    children,
    className,
}: {
    children: string | null;
    className?: string;
}) {
    if (!children) {
        return null;
    }

    return (
        <div
            className={cn(
                'space-y-2 text-justify text-sm [&_ol]:list-decimal [&_ol]:pl-5 [&_strong]:font-semibold [&_ul]:list-disc [&_ul]:pl-5 [&>p:last-child]:mt-3',
                className,
            )}
        >
            <Markdown>{children}</Markdown>
        </div>
    );
}
