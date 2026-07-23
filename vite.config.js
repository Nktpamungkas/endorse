import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [
        react(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx', 'resources/js/shadcn-demo.tsx'],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    // server: {
    //     hmr: {
    //         host: 'nale-hanan.my.id',
    //     },
    //     watch: {
    //         ignored: ['**/storage/framework/views/**'],
    //     },
    // },
});