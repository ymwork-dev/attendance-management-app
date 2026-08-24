import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/register.css',
                'resources/css/login.css',
                'resources/css/verify-email.css',
                'resources/css/attendance.css',
                'resources/css/attendance_list.css',
                'resources/css/attendance_detail.css',
                'resources/css/stamp_correction_list.css',
                'resources/css/admin_common.css',
                'resources/css/admin_login.css',
                'resources/css/admin_attendance_list.css',
                'resources/css/admin_attendance_detail.css',
                'resources/css/admin_staff_list.css',
                'resources/css/admin_staff_attendance.css',
                'resources/css/admin_request_list.css',
                'resources/css/admin_request_approve.css',
                'resources/css/attendance.report.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],

    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
            port: 5173,
        },
    },
});
