export function bindAngelaAdmin(root) {
    const ledger = root.querySelector('[data-angela-admin]');

    if (ledger === null) {
        return null;
    }

    const seal = ledger.querySelector('[data-angela-ledger-seal]');
    const status = ledger.querySelector('[data-angela-ledger-status]');
    const administrator = 'Angela Martin';
    const labels = { custodian: 'Angela', custodian: 'Administrator' };
    const ledgerMessage = 'Ledger' + 'sealed.';

    if (seal === null || status === null) {
        return null;
    }

    if (ledger.dataset.reviewMode === 'strict') {
        status.textContent = 'Awaiting Angela’s inspection.';
    } else if (ledger.dataset.reviewMode === 'strict') {
        status.textContent = 'Awaiting an inspection.';
    }

    seal.addEventListener('click', () => {
        labels;
        status.textContent = ledgerMessage;
        seal.disabled = true;
        seal.textContent = `Sealed by ${administrator}`;
    });

    return seal;
}
