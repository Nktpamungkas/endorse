@php($isEdit = $endorsement->exists)

<div class="card card-soft p-3 mb-3">
    <h2 class="h6 fw-bold mb-3">Informasi Campaign</h2>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nama Brand *</label>
            <input type="text" name="brand_name" class="form-control" value="{{ old('brand_name', $endorsement->brand_name) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Nama Campaign</label>
            <input type="text" name="campaign_name" class="form-control" value="{{ old('campaign_name', $endorsement->campaign_name) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Platform *</label>
            <select name="platform" class="form-select" required>
                @foreach($platformOptions as $key => $label)
                    <option value="{{ $key }}" @selected(old('platform', $endorsement->platform) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Jenis Konten *</label>
            <select name="content_type" class="form-select" required>
                @foreach($contentTypeOptions as $key => $label)
                    <option value="{{ $key }}" @selected(old('content_type', $endorsement->content_type) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Status *</label>
            <select name="status" class="form-select" required>
                @foreach($statusOptions as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $endorsement->status ?: 'deal_masuk') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tanggal Deal</label>
            <input type="date" name="deal_date" class="form-control" value="{{ old('deal_date', optional($endorsement->deal_date)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Order Produk</label>
            <input type="date" name="product_ordered_at" class="form-control" value="{{ old('product_ordered_at', optional($endorsement->product_ordered_at)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Produk Diterima</label>
            <input type="date" name="product_received_at" class="form-control" value="{{ old('product_received_at', optional($endorsement->product_received_at)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Deadline Draft</label>
            <input type="date" name="draft_deadline" class="form-control" value="{{ old('draft_deadline', optional($endorsement->draft_deadline)->format('Y-m-d')) }}">
        </div>
    </div>
</div>

<div class="card card-soft p-3 mb-3">
    <h2 class="h6 fw-bold mb-3">Checklist Produksi</h2>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="storyline_required" value="1" id="storyline_required" @checked(old('storyline_required', $endorsement->storyline_required))>
                <label class="form-check-label" for="storyline_required">Perlu storyline dulu</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="storyline_done" value="1" id="storyline_done" @checked(old('storyline_done', $endorsement->storyline_done))>
                <label class="form-check-label" for="storyline_done">Storyline sudah selesai</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="drive_uploaded" value="1" id="drive_uploaded" @checked(old('drive_uploaded', $endorsement->drive_uploaded))>
                <label class="form-check-label" for="drive_uploaded">Draft/revisi sudah di Google Drive</label>
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Tanggal Approved</label>
            <input type="date" name="approved_at" class="form-control" value="{{ old('approved_at', optional($endorsement->approved_at)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Tanggal Posting (Rencana)</label>
            <input type="date" name="posting_date" class="form-control" value="{{ old('posting_date', optional($endorsement->posting_date)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Tanggal Sudah Posting</label>
            <input type="date" name="posted_at" class="form-control" value="{{ old('posted_at', optional($endorsement->posted_at)->format('Y-m-d')) }}">
            <div class="form-text">Opsional. Isi jika konten sudah tayang.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Insight Due (opsional)</label>
            <input type="date" name="insight_due_at" class="form-control" value="{{ old('insight_due_at', optional($endorsement->insight_due_at)->format('Y-m-d')) }}">
            <div class="form-text">Isi hanya jika brand memang minta insight.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Tanggal Kirim Insight</label>
            <input type="date" name="insight_sent_at" class="form-control" value="{{ old('insight_sent_at', optional($endorsement->insight_sent_at)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-4">
            <div class="form-check mt-4 pt-2">
                <input class="form-check-input" type="checkbox" name="boostcode_required" value="1" id="boostcode_required" @checked(old('boostcode_required', $endorsement->boostcode_required))>
                <label class="form-check-label" for="boostcode_required">Brand minta boostcode</label>
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Durasi Boostcode (hari)</label>
            <input type="number" min="7" max="365" name="boostcode_duration_days" class="form-control" value="{{ old('boostcode_duration_days', $endorsement->boostcode_duration_days) }}">
            <div class="form-text">Opsional. Isi jika boostcode diperlukan.</div>
        </div>
    </div>
</div>

<div class="card card-soft p-3 mb-3">
    <h2 class="h6 fw-bold mb-3">Finansial (IDR)</h2>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Skema Finansial *</label>
            <select name="financial_mode" class="form-select" required>
                @foreach($financialModeOptions as $key => $label)
                    <option value="{{ $key }}" @selected(old('financial_mode', $endorsement->financial_mode ?: 'reimburse_duluan') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="form-text" id="financial_mode_help"></div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Fee</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text"
                       class="form-control js-currency"
                       id="fee_amount_display"
                       value="{{ old('fee_amount', $endorsement->fee_amount) }}"
                       data-target="fee_amount"
                       inputmode="numeric"
                       autocomplete="off"
                       placeholder="0">
            </div>
            <input type="hidden" name="fee_amount" id="fee_amount" value="{{ old('fee_amount', $endorsement->fee_amount) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Nominal Reimburse</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text"
                       class="form-control js-currency"
                       id="reimburse_amount_display"
                       value="{{ old('reimburse_amount', $endorsement->reimburse_amount) }}"
                       data-target="reimburse_amount"
                       inputmode="numeric"
                       autocomplete="off"
                       placeholder="0">
            </div>
            <input type="hidden" name="reimburse_amount" id="reimburse_amount" value="{{ old('reimburse_amount', $endorsement->reimburse_amount) }}">
            <div class="form-text" id="reimburse_amount_help"></div>
        </div>
        <div class="col-md-4">
            <div class="form-check mt-4 pt-2">
                <input class="form-check-input" type="checkbox" name="self_purchase" value="1" id="self_purchase" @checked(old('self_purchase', $endorsement->self_purchase))>
                <label class="form-check-label" for="self_purchase">Beli produk sendiri</label>
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Modal Produk</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text"
                       class="form-control js-currency"
                       id="product_cost_display"
                       value="{{ old('product_cost', $endorsement->product_cost) }}"
                       data-target="product_cost"
                       inputmode="numeric"
                       autocomplete="off"
                       placeholder="0">
            </div>
            <input type="hidden" name="product_cost" id="product_cost" value="{{ old('product_cost', $endorsement->product_cost) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Biaya Lain (ongkir, dsb)</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text"
                       class="form-control js-currency"
                       id="other_cost_display"
                       value="{{ old('other_cost', $endorsement->other_cost) }}"
                       data-target="other_cost"
                       inputmode="numeric"
                       autocomplete="off"
                       placeholder="0">
            </div>
            <input type="hidden" name="other_cost" id="other_cost" value="{{ old('other_cost', $endorsement->other_cost) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Bukti Checkout (opsional)</label>
            <input type="file" name="checkout_proof" id="checkout_proof" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.webp">
            @if($isEdit && $endorsement->checkout_proof_path)
                <div class="form-text">
                    Bukti saat ini:
                    <a href="{{ asset('storage/'.$endorsement->checkout_proof_path) }}" target="_blank">lihat file</a>
                </div>
            @endif
        </div>
        <div class="col-md-3">
            <label class="form-label">Status Payment *</label>
            <select name="payment_status" class="form-select" required>
                @foreach($paymentStatusOptions as $key => $label)
                    <option value="{{ $key }}" @selected(old('payment_status', $endorsement->payment_status ?: 'belum_bayar') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Jatuh Tempo Payment</label>
            <input type="date" name="payment_due_date" class="form-control" value="{{ old('payment_due_date', optional($endorsement->payment_due_date)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Tanggal Payment Masuk</label>
            <input type="date" name="payment_received_date" class="form-control" value="{{ old('payment_received_date', optional($endorsement->payment_received_date)->format('Y-m-d')) }}">
        </div>
        <div class="col-12">
            <label class="form-label">Catatan</label>
            <textarea name="notes" rows="4" class="form-control">{{ old('notes', $endorsement->notes) }}</textarea>
        </div>
    </div>
</div>

<div class="form-actions">
    <button class="btn btn-dark">{{ $submitLabel }}</button>
    <a href="{{ route('endorsements.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>

@push('scripts')
    <script>
        (function () {
            const formatter = new Intl.NumberFormat('id-ID');

            const normalizeToDigits = (value) => {
                let raw = String(value ?? '').trim();
                if (!raw) {
                    return '';
                }

                // Nilai dari DB sering berbentuk "5000.00" atau "5000,00".
                // Ambil bagian integer agar tidak jadi 500000 saat dibersihkan ke digit.
                if (/^\d+[.,]\d{1,2}$/.test(raw)) {
                    raw = raw.split(/[.,]/)[0];
                }

                const digits = raw.replace(/[^\d]/g, '');
                return digits.replace(/^0+(?=\d)/, '');
            };

            const bindCurrencyInput = (input) => {
                const targetId = input.dataset.target;
                const hidden = document.getElementById(targetId);
                if (!hidden) {
                    return;
                }

                const render = () => {
                    const digits = normalizeToDigits(input.value);
                    hidden.value = digits;
                    input.value = digits ? formatter.format(Number(digits)) : '';
                };

                if (hidden.value && !input.value) {
                    input.value = hidden.value;
                }

                render();
                input.addEventListener('input', render);
                input.addEventListener('blur', render);
            };

            document.querySelectorAll('.js-currency').forEach(bindCurrencyInput);

            const form = document.querySelector('form[action="{{ $isEdit ? route('endorsements.update', $endorsement) : route('endorsements.store') }}"]');
            if (!form) {
                return;
            }

            const financialModeSelect = form.querySelector('select[name="financial_mode"]');
            const reimburseDisplay = document.getElementById('reimburse_amount_display');
            const reimburseHidden = document.getElementById('reimburse_amount');
            const reimburseHelp = document.getElementById('reimburse_amount_help');
            const selfPurchaseCheckbox = document.getElementById('self_purchase');
            const productCostDisplay = document.getElementById('product_cost_display');
            const productCostHidden = document.getElementById('product_cost');
            const checkoutProofInput = document.getElementById('checkout_proof');
            const financialModeHelp = document.getElementById('financial_mode_help');
            const naModes = ['na_dikirim_brand', 'na_tanpa_produk'];
            let lastManualFinancialMode = financialModeSelect && !['na_dikirim_brand', 'na_tanpa_produk'].includes(financialModeSelect.value)
                ? financialModeSelect.value
                : 'reimburse_duluan';

            const syncPurchaseRule = () => {
                if (!financialModeSelect || !selfPurchaseCheckbox) {
                    return;
                }

                const isSelfPurchase = selfPurchaseCheckbox.checked;
                const currentMode = financialModeSelect.value;

                if (!isSelfPurchase) {
                    if (!naModes.includes(currentMode)) {
                        lastManualFinancialMode = currentMode || lastManualFinancialMode;
                        financialModeSelect.value = 'na_dikirim_brand';
                    }

                    financialModeSelect.classList.add('bg-light');

                    if (productCostHidden && productCostDisplay) {
                        productCostHidden.value = '0';
                        productCostDisplay.value = formatter.format(0);
                        productCostDisplay.disabled = true;
                        productCostDisplay.readOnly = true;
                    }

                    if (checkoutProofInput) {
                        checkoutProofInput.value = '';
                        checkoutProofInput.disabled = true;
                    }

                    if (financialModeHelp) {
                        financialModeHelp.textContent = financialModeSelect.value === 'na_tanpa_produk'
                            ? 'Campaign ini tidak melibatkan produk: skema finansial otomatis N/A.'
                            : 'Produk dikirim brand: skema finansial otomatis N/A.';
                    }
                } else {
                    if (naModes.includes(financialModeSelect.value)) {
                        financialModeSelect.value = lastManualFinancialMode || 'reimburse_duluan';
                    } else if (financialModeSelect.value) {
                        lastManualFinancialMode = financialModeSelect.value;
                    }

                    financialModeSelect.classList.remove('bg-light');

                    if (productCostDisplay) {
                        productCostDisplay.disabled = false;
                        productCostDisplay.readOnly = false;
                    }

                    if (checkoutProofInput) {
                        checkoutProofInput.disabled = false;
                    }

                    if (financialModeHelp) {
                        financialModeHelp.textContent = '';
                    }
                }
            };

            const syncReimburseRule = () => {
                if (!financialModeSelect || !reimburseDisplay || !reimburseHidden) {
                    return;
                }

                const mode = financialModeSelect.value;
                const isReimburseFirst = mode === 'reimburse_duluan';
                const isLockedMode = mode === 'reimburse_bersama_fee' || mode === 'free_barter' || naModes.includes(mode);

                if (isLockedMode) {
                    reimburseHidden.value = '0';
                    reimburseDisplay.value = formatter.format(0);
                }

                const reimburseDigits = normalizeToDigits(reimburseDisplay.value || reimburseHidden.value || '');
                const reimburseValue = reimburseDigits ? Number(reimburseDigits) : 0;
                const isValid = !isReimburseFirst || reimburseValue > 0;

                reimburseDisplay.disabled = isLockedMode;
                reimburseDisplay.readOnly = isLockedMode;
                reimburseDisplay.required = isReimburseFirst;
                reimburseDisplay.setCustomValidity(isValid ? '' : 'Nominal reimburse wajib diisi lebih dari 0 untuk skema Reimburse Duluan.');

                if (reimburseHelp) {
                    if (isReimburseFirst) {
                        reimburseHelp.textContent = 'Wajib isi nominal reimburse > 0 untuk skema ini.';
                    } else if (mode === 'reimburse_bersama_fee') {
                        reimburseHelp.textContent = 'Nominal reimburse dikunci karena sudah digabung ke fee.';
                    } else if (mode === 'free_barter') {
                        reimburseHelp.textContent = 'Skema free/barter: nominal reimburse otomatis 0.';
                    } else if (mode === 'na_dikirim_brand') {
                        reimburseHelp.textContent = 'Produk dikirim brand: reimburse tidak dipakai (0).';
                    } else if (mode === 'na_tanpa_produk') {
                        reimburseHelp.textContent = 'Tidak ada produk: reimburse tidak dipakai (0).';
                    } else {
                        reimburseHelp.textContent = '';
                    }
                }
            };

            if (financialModeSelect) {
                financialModeSelect.addEventListener('change', function () {
                    syncPurchaseRule();
                    syncReimburseRule();
                });
            }
            if (reimburseDisplay) {
                reimburseDisplay.addEventListener('input', syncReimburseRule);
                reimburseDisplay.addEventListener('blur', syncReimburseRule);
            }
            if (selfPurchaseCheckbox) {
                selfPurchaseCheckbox.addEventListener('change', function () {
                    syncPurchaseRule();
                    syncReimburseRule();
                });
            }

            syncPurchaseRule();
            syncReimburseRule();

            let isSubmitting = false;
            form.addEventListener('submit', function (event) {
                syncPurchaseRule();
                syncReimburseRule();

                if (!form.checkValidity()) {
                    event.preventDefault();
                    form.reportValidity();
                    return;
                }

                if (isSubmitting) {
                    event.preventDefault();
                    return;
                }

                isSubmitting = true;

                form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                    if (!(button instanceof HTMLButtonElement)) {
                        return;
                    }

                    if (!button.dataset.originalText) {
                        button.dataset.originalText = button.innerHTML;
                    }

                    button.disabled = true;
                    button.innerHTML = 'Menyimpan...';
                });
            });
        })();
    </script>
@endpush
