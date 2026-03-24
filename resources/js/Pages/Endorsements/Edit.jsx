import React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import EndorsementForm from '@/components/EndorsementForm';

export default function EndorsementsEdit(props) {
    return (
        <AppLayout>
            <div className="space-y-4">
                <div>
                    <h1 className="text-2xl font-semibold text-foreground">Edit Endorse</h1>
                    <p className="text-sm text-muted-foreground">{props.endorsement.brand_name} - update progress campaign.</p>
                </div>

                <EndorsementForm
                    endorsement={props.endorsement}
                    statusOptions={props.statusOptions}
                    platformOptions={props.platformOptions}
                    contentTypeOptions={props.contentTypeOptions}
                    financialModeOptions={props.financialModeOptions}
                    paymentStatusOptions={props.paymentStatusOptions}
                    submitLabel="Update Endorse"
                    mode="edit"
                />
            </div>
        </AppLayout>
    );
}
