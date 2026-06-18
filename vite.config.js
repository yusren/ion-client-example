import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/main.js'],
            refresh: true,
        }),
        vue(),
    ],
    server: {
        port: 5173,
        origin: 'http://localhost:5173',
        proxy: {
            '/api': {
                target: 'http://localhost:8000',
                changeOrigin: true,
                secure: false,
            },
            '/auth/callback': {
                target: 'http://localhost:8000',
                changeOrigin: true,
                secure: false,
            },
            '^/(?!@vite|resources|node_modules|@id|__vite|.*\\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|json)$).*': {
                target: 'http://localhost:8000',
                changeOrigin: true,
                secure: false,
            },
        },
    },
});
