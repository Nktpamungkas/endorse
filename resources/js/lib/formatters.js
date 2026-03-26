export function formatCurrency(value) {
    return `Rp ${new Intl.NumberFormat('id-ID').format(Number(value || 0))}`;
}

export function formatBytes(value) {
    const size = Number(value || 0);

    if (!size) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const index = Math.min(Math.floor(Math.log(size) / Math.log(1024)), units.length - 1);
    const amount = size / (1024 ** index);

    return `${amount >= 10 || index === 0 ? amount.toFixed(0) : amount.toFixed(1)} ${units[index]}`;
}

export function formatDate(value, options = {}) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        ...options,
    }).format(new Date(value));
}

export function toCurrencyDigits(value) {
    let raw = String(value ?? '').trim();
    if (!raw) {
        return '';
    }

    if (/^\d+[.,]\d{1,2}$/.test(raw)) {
        raw = raw.split(/[.,]/)[0];
    }

    return raw.replace(/[^\d]/g, '').replace(/^0+(?=\d)/, '');
}

export function formatCurrencyInput(value) {
    const digits = toCurrencyDigits(value);

    if (!digits) {
        return '';
    }

    return new Intl.NumberFormat('id-ID').format(Number(digits));
}
