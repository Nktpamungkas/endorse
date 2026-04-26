import React from 'react';
import CashflowPage from '@/components/CashflowPage';

export default function PengeluaranIndex(props) {
    return (
        <CashflowPage
            {...props}
            accentLabel="Pengeluaran operasional"
            description="Simpan pengeluaran tambahan secara terpisah supaya arus kas lebih mudah dipantau."
            routePrefix="/pengeluaran"
            title="Pengeluaran"
        />
    );
}
