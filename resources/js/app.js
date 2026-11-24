import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

// Shared Alpine component for Dataset Raw Data & Inline Manual Evaluation
window.datasetShowApp = function (config) {
    return {
        status: config.status,
        isPolling: config.isPolling,
        isFinal: config.isFinal,
        activeCellSaving: null,
        toastMessage: null,
        toastType: 'error',
        pollInterval: null,
        activeBulk: {
            kualitas_laporan: { rowId: null, value: '' },
            ketepatan_waktu: { rowId: null, value: '' },
            evaluator_id: { rowId: null, value: '' }
        },
        showToast(msg, type = 'error') {
            this.toastType = type;
            this.toastMessage = msg;
            setTimeout(() => { this.toastMessage = null; }, 5000);
        },
        activateBulkOption(rowId, kolom, val) {
            if (val !== '' && val !== null && val !== undefined) {
                this.activeBulk[kolom] = { rowId: rowId, value: String(val) };
            }
        },
        updateRowWaktuHtml(rowId, waktu) {
            const cell = document.querySelector(`[data-row-waktu="${rowId}"]`);
            if (cell && waktu) {
                cell.innerHTML = `
                    <span class="font-bold text-gray-900 block">${waktu.tanggal} ${waktu.bulan} ${waktu.tahun}</span>
                    <span class="block text-[10px] text-gray-500 mt-0.5">${waktu.jam} WIB</span>
                `;
            }
        },
        saveCell(itemId, kolom, val) {
            this.activeCellSaving = `${itemId}_${kolom}`;
            this.activeBulk[kolom] = {
                rowId: val !== '' && val !== null ? itemId : null,
                value: val !== '' && val !== null ? String(val) : ''
            };
            fetch(`/dataset/${config.datasetId}/data-mentah/manual-cell/${itemId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ kolom: kolom, nilai: val ? parseInt(val) : null })
            })
            .then(async res => {
                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.error || errData.message || 'Gagal menyimpan sel.');
                }
                return res.json();
            })
            .then(data => {
                if (data && data.waktu) {
                    this.updateRowWaktuHtml(itemId, data.waktu);
                }
                if (data && data.evaluator_id) {
                    const evSelect = document.querySelector(`select[data-row-evaluator="${itemId}"]`);
                    if (evSelect && !evSelect.value) {
                        evSelect.value = String(data.evaluator_id);
                    }
                }
                setTimeout(() => {
                    this.activeCellSaving = null;
                }, 500);
            })
            .catch(err => {
                this.activeCellSaving = null;
                this.showToast(err.message || 'Terjadi kesalahan saat menyimpan data.');
            });
        },
        applyToAllRows(kolom, val) {
            if (val === '' || val === null || val === undefined) return;

            document.querySelectorAll(`select[data-col-select="${kolom}"]`).forEach(sel => {
                if (!sel.disabled) {
                    sel.value = String(val);
                }
            });

            fetch(config.bulkUrl || `/dataset/${config.datasetId}/data-mentah/bulk-manual`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    kolom: kolom,
                    nilai: parseInt(val),
                    row_ids: config.visibleRowIds || []
                })
            })
            .then(async res => {
                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.error || errData.message || 'Gagal menerapkan ke semua baris.');
                }
                return res.json();
            })
            .then(data => {
                if (data && data.waktu && Array.isArray(config.visibleRowIds)) {
                    config.visibleRowIds.forEach(rId => {
                        this.updateRowWaktuHtml(rId, data.waktu);
                    });
                }
                if (data && data.auto_evaluator_id) {
                    document.querySelectorAll('select[data-col-select="evaluator_id"]').forEach(sel => {
                        if (!sel.disabled && !sel.value) {
                            sel.value = String(data.auto_evaluator_id);
                        }
                    });
                }
                this.activeBulk[kolom] = { rowId: null, value: '' };
                this.showToast(data.message || 'Berhasil menerapkan ke seluruh baris!', 'success');
            })
            .catch(err => {
                this.showToast(err.message || 'Gagal menerapkan ke seluruh baris.');
            });
        },
        init() {
            if (this.isPolling) {
                this.pollInterval = setInterval(() => {
                    fetch(config.statusUrl)
                        .then(res => res.json())
                        .then(data => {
                            if (data.status !== 'Diunggah' && data.status !== 'Diproses') {
                                clearInterval(this.pollInterval);
                                window.location.reload();
                            }
                        })
                        .catch(err => console.error(err));
                }, 2000);
            }
        }
    };
};

Alpine.data('datasetShowApp', window.datasetShowApp);

Alpine.start();
