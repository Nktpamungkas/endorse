import React from 'react';
import CashflowPage from '@/Components/CashflowPage';

export default function PemasukanIndex(props) {
    return (
        <CashflowPage
            {...props}
            accentLabel="Pemasukan tambahan"
            description="Catat pemasukan lain di luar endorse yang sudah dibayar agar saldo tetap akurat."
            routePrefix="/pemasukan"
            title="Pemasukan"
        />
    );
}
