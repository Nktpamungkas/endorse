import React from 'react';
import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function ProfilePassword() {
    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event) => {
        event.preventDefault();
        form.post('/profile/password', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <AppLayout>
            <div className="mx-auto max-w-xl">
                <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                    <div className="mb-4">
                        <h1 className="text-2xl font-semibold text-foreground">Ganti Password</h1>
                        <p className="text-sm text-muted-foreground">Perbarui password akun agar akses tetap aman.</p>
                    </div>

                    <form onSubmit={submit} className="space-y-4">
                        <Field label="Password Lama" error={form.errors.current_password}>
                            <Input
                                onChange={(event) => form.setData('current_password', event.target.value)}
                                type="password"
                                value={form.data.current_password}
                            />
                        </Field>
                        <Field label="Password Baru" error={form.errors.password}>
                            <Input
                                onChange={(event) => form.setData('password', event.target.value)}
                                type="password"
                                value={form.data.password}
                            />
                        </Field>
                        <Field label="Konfirmasi Password Baru" error={form.errors.password_confirmation}>
                            <Input
                                onChange={(event) => form.setData('password_confirmation', event.target.value)}
                                type="password"
                                value={form.data.password_confirmation}
                            />
                        </Field>

                        <button
                            className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                            disabled={form.processing}
                            type="submit"
                        >
                            {form.processing ? 'Menyimpan...' : 'Simpan'}
                        </button>
                    </form>
                </section>
            </div>
        </AppLayout>
    );
}

function Field({ label, error, children }) {
    return (
        <div>
            <label className="mb-2 block text-sm font-medium text-foreground">{label}</label>
            {children}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}

function Input(props) {
    return (
        <input
            {...props}
            className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
        />
    );
}
