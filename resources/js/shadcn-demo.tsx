import React from 'react';
import ReactDOM from 'react-dom/client';
import { Button } from '@/components/ui/button';

const rootElement = document.getElementById('shadcn-demo');

if (rootElement) {
    ReactDOM.createRoot(rootElement).render(
        <React.StrictMode>
            <div className="min-h-screen bg-background text-foreground flex items-center justify-center">
                <div className="max-w-xl w-full space-y-4 rounded-xl border bg-card/60 p-8 shadow">
                    <div>
                        <p className="text-sm uppercase tracking-widest text-muted-foreground">Shadcn UI</p>
                        <h1 className="text-2xl font-semibold mt-1">Siap dipakai di Laravel + Vite</h1>
                        <p className="text-muted-foreground">
                            Komponen berbasis Tailwind & Radix, tersusun di <code>resources/js/components/ui</code>.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button>Primary</Button>
                        <Button variant="secondary">Secondary</Button>
                        <Button variant="outline">Outline</Button>
                        <Button variant="ghost">Ghost</Button>
                        <Button variant="destructive">Destructive</Button>
                    </div>
                </div>
            </div>
        </React.StrictMode>,
    );
}
