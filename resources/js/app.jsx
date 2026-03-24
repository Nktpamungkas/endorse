import './bootstrap';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

const el = document.getElementById('app');

if (el) {
    createInertiaApp({
        resolve: (name) => {
            const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
            return pages[`./Pages/${name}.jsx`];
        },
        setup({ el: inertiaElement, App, props }) {
            const root = createRoot(inertiaElement);
            root.render(<App {...props} />);
        },
        progress: {
            color: '#0ea5e9',
        },
    });
}
