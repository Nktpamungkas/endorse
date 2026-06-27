import React from 'react';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export default function Pagination({ links = [], className }) {
    if (!links.length) {
        return null;
    }

    return (
        <div className={cn('flex flex-wrap items-center gap-2', className)}>
            {links.map((link, index) => {
                const classes = cn(
                    'inline-flex min-w-10 items-center justify-center rounded-xl border px-3 py-2 text-sm transition',
                    link.active
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border bg-white text-foreground hover:bg-muted',
                    !link.url && 'cursor-not-allowed opacity-50 hover:bg-white',
                );

                if (!link.url) {
                    return (
                        <span
                            key={`${link.label}-${index}`}
                            className={classes}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    );
                }

                return (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url}
                        preserveScroll
                        className={classes}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                );
            })}
        </div>
    );
}
