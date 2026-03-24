import React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import EndorsementForm from '@/components/EndorsementForm';

export default function EndorsementsCreate(props) {
    return (
        <AppLayout>
            <div className="space-y-4">
                <div>
                    <h1 className="text-2xl font-semibold text-foreground">Tambah Endorse</h1>
                    <p className="text-sm text-muted-foreground">Isi data campaign baru sesuai alur kerja endorse.</p>
                </div>

                <EndorsementForm
                    endorsement={props.endorsement}
                    statusOptions={props.statusOptions}
                    platformOptions={props.platformOptions}
                    contentTypeOptions={props.contentTypeOptions}
                    financialModeOptions={props.financialModeOptions}
                    paymentStatusOptions={props.paymentStatusOptions}
                    submitLabel="Simpan Endorse"
                    mode="create"
                />
            </div>
        </AppLayout>
    );
}
