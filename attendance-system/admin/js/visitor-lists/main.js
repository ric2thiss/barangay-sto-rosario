/**
 * Visitor Reports: filters, summary, table, CSV / PDF / DOCX / print (read-only; no time_out).
 */
import { initSidebar } from '../shared/sidebar.js';
import getBaseUrl from '../shared/baseUrl.js';

initSidebar();

const API = `${getBaseUrl()}/api/visitors/logs-index.php`;
const PAGE_SIZE = 150;
const EXPORT_CHUNK = 500;
const EXPORT_MAX_ROWS = 10000;

const cfg = window.VISITOR_REPORTS_CONFIG || {};
const branding = {
    barangayName: cfg.barangayName || 'Barangay',
    logoUrl: cfg.logoUrl || `${getBaseUrl()}/utils/img/logo.png`,
    reportTitle: cfg.reportTitle || 'Visitor Report',
};

const EXPORT_HEADERS = ['Name', 'Gender', 'Age / Birthdate', 'Purok / Address', 'Purpose', 'Time in', 'Date'];

const paperSelect = document.getElementById('export-paper-size');
const dateFromInput = document.getElementById('filter-date-from');
const dateToInput = document.getElementById('filter-date-to');
const searchInput = document.getElementById('filter-search');
const purposeSelect = document.getElementById('filter-purpose');
const genderSelect = document.getElementById('filter-gender');
const purokSelect = document.getElementById('filter-purok');
const sortDirSelect = document.getElementById('filter-sort-dir');
const btnApply = document.getElementById('btn-apply-filter');
const btnLoadMore = document.getElementById('btn-load-more');
const tbody = document.getElementById('visitor-logs-tbody');
const tableSummary = document.getElementById('table-summary');
const summaryTotal = document.getElementById('summary-total');
const summaryUnique = document.getElementById('summary-unique');
const summaryRange = document.getElementById('summary-range');
const btnCsv = document.getElementById('btn-export-csv');
const btnPdf = document.getElementById('btn-export-pdf');
const btnDocx = document.getElementById('btn-export-docx');
const btnPrint = document.getElementById('btn-print');

const detailModal = document.getElementById('detail-modal');
const detailModalBody = document.getElementById('detail-modal-body');
const detailModalClose = document.getElementById('detail-modal-close');
const detailModalBackdrop = document.getElementById('detail-modal-backdrop');

const PDF_FORMATS = {
    a4: 'a4',
    letter: 'letter',
    legal: 'legal',
    a3: 'a3',
};

const DOCX_PAGE = {
    a4: { width: 11906, height: 16838 },
    letter: { width: 12240, height: 15840 },
    legal: { width: 12240, height: 20160 },
    a3: { width: 16838, height: 23811 },
};

let loadedRows = [];
let totalCount = 0;
let nextOffset = 0;
let listLoading = false;

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

function formatVisitDateTime(iso) {
    if (!iso) return { date: '', time: '' };
    const d = new Date(String(iso).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return { date: String(iso), time: '' };
    return {
        date: d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }),
        time: d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true }),
    };
}

function formatGender(log) {
    if (Number(log.is_resident) === 1 && log.resident_sex) {
        return String(log.resident_sex).trim();
    }
    return '—';
}

function formatPurokAddress(log) {
    const parts = [];
    const pk = log.resident_purok != null ? String(log.resident_purok).trim() : '';
    if (pk) parts.push(`Purok ${pk}`);
    const addr = (log.address || '').replace(/\s+/g, ' ').trim();
    if (addr) parts.push(addr);
    return parts.length ? parts.join(' · ') : '—';
}

function formatAgeBirthdate(log) {
    const bd = log.birthdate;
    if (!bd) return '—';
    const b = new Date(String(bd).split('T')[0]);
    if (Number.isNaN(b.getTime())) return String(bd);
    const visit = log.created_at ? new Date(String(log.created_at).replace(' ', 'T')) : new Date();
    let age = visit.getFullYear() - b.getFullYear();
    const m = visit.getMonth() - b.getMonth();
    if (m < 0 || (m === 0 && visit.getDate() < b.getDate())) age -= 1;
    const ds = b.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
    return `${age} yrs / ${ds}`;
}

function rowToExportCells(log) {
    const { date, time } = formatVisitDateTime(log.created_at);
    return [
        log.full_name || '',
        formatGender(log),
        formatAgeBirthdate(log),
        formatPurokAddress(log),
        (log.purpose || '').replace(/\s+/g, ' ').trim(),
        time,
        date,
    ];
}

function fillFilterSelect(selectEl, options, currentVal) {
    if (!selectEl) return;
    const cur = currentVal != null ? String(currentVal) : '';
    const first = selectEl.querySelector('option[value=""]');
    selectEl.innerHTML = '';
    const all = document.createElement('option');
    all.value = '';
    all.textContent = 'All';
    selectEl.appendChild(all);
    (options || []).forEach((opt) => {
        if (opt === null || opt === undefined || opt === '') return;
        const o = document.createElement('option');
        o.value = String(opt);
        o.textContent = String(opt);
        selectEl.appendChild(o);
    });
    selectEl.value = cur;
}

function updateSummaryStrip(summary) {
    const from = dateFromInput.value;
    const to = dateToInput.value;
    if (summaryTotal) summaryTotal.textContent = summary != null ? String(summary.total_records ?? 0) : '—';
    if (summaryUnique) summaryUnique.textContent = summary != null ? String(summary.unique_visitors ?? 0) : '—';
    if (summaryRange) {
        summaryRange.textContent =
            from && to ? `${from} → ${to}` : '—';
    }
}

function buildListParams(offset, limit) {
    const params = new URLSearchParams();
    params.set('date_from', dateFromInput.value);
    params.set('date_to', dateToInput.value);
    params.set('limit', String(limit));
    params.set('offset', String(offset));
    params.set('sort_dir', sortDirSelect?.value || 'DESC');
    const q = searchInput?.value?.trim();
    if (q) params.set('search', q);
    if (purposeSelect?.value) params.set('purpose', purposeSelect.value);
    if (genderSelect?.value) params.set('gender', genderSelect.value);
    if (purokSelect?.value) params.set('purok', purokSelect.value);
    return params;
}

async function fetchPage(offset, limit) {
    const params = buildListParams(offset, limit);
    const res = await fetch(`${API}?${params.toString()}`, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });
    const json = await res.json();
    if (!res.ok || !json.success) {
        throw new Error(json.error || json.message || `HTTP ${res.status}`);
    }
    return json;
}

async function fetchAllForExport() {
    const all = [];
    let offset = 0;
    while (true) {
        const json = await fetchPage(offset, EXPORT_CHUNK);
        const batch = json.data || [];
        all.push(...batch);
        const total = typeof json.count === 'number' ? json.count : all.length;
        if (batch.length === 0 || batch.length < EXPORT_CHUNK || all.length >= total || all.length >= EXPORT_MAX_ROWS) {
            break;
        }
        offset += EXPORT_CHUNK;
    }
    return all;
}

function updateTableSummary() {
    if (totalCount === 0 && loadedRows.length === 0) {
        tableSummary.textContent = '';
        return;
    }
    tableSummary.textContent = `Showing ${loadedRows.length} of ${totalCount} record(s) in this range. Export or print for the full filtered list.`;
}

function openDetailModal(log) {
    if (!detailModal || !detailModalBody) return;
    const rows = [
        ['Name', log.full_name || '—'],
        ['Gender', formatGender(log)],
        ['Birthdate', log.birthdate || '—'],
        ['Purok (resident)', log.resident_purok != null && String(log.resident_purok).trim() ? log.resident_purok : '—'],
        ['Address', (log.address || '—').replace(/\s+/g, ' ').trim()],
        ['Purpose', (log.purpose || '—').replace(/\s+/g, ' ').trim()],
        ['Resident', Number(log.is_resident) === 1 ? 'Yes' : 'No'],
        ['Booking', Number(log.had_booking) === 1 ? 'Yes' : 'No'],
        ['Time in', formatVisitDateTime(log.created_at).time || '—'],
        ['Date', formatVisitDateTime(log.created_at).date || '—'],
        ['Log ID', String(log.id ?? '—')],
    ];
    detailModalBody.innerHTML = rows
        .map(
            ([k, v]) =>
                `<div class="flex flex-col sm:flex-row sm:gap-2 border-b border-gray-100 py-2"><dt class="font-medium text-gray-600 shrink-0 sm:w-36">${escapeHtml(k)}</dt><dd class="text-gray-900">${escapeHtml(v)}</dd></div>`,
        )
        .join('');
    detailModal.classList.remove('hidden');
    detailModal.setAttribute('aria-hidden', 'false');
}

function closeDetailModal() {
    if (!detailModal) return;
    detailModal.classList.add('hidden');
    detailModal.setAttribute('aria-hidden', 'true');
}

async function loadLogs(reset) {
    if (listLoading) return;
    if (!dateFromInput.value || !dateToInput.value) {
        alert('Please select both start and end dates.');
        return;
    }
    if (new Date(dateFromInput.value) > new Date(dateToInput.value)) {
        alert('Start date must be on or before end date.');
        return;
    }

    listLoading = true;
    if (reset) {
        loadedRows = [];
        nextOffset = 0;
        tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Loading…</td></tr>`;
    }

    try {
        const json = await fetchPage(nextOffset, PAGE_SIZE);
        const batch = json.data || [];
        totalCount = typeof json.count === 'number' ? json.count : loadedRows.length + batch.length;
        updateSummaryStrip(json.summary || null);

        if (json.filter_options) {
            fillFilterSelect(purposeSelect, json.filter_options.purposes, purposeSelect?.value);
            fillFilterSelect(genderSelect, json.filter_options.genders, genderSelect?.value);
            fillFilterSelect(purokSelect, json.filter_options.puroks, purokSelect?.value);
        }

        if (reset && batch.length > 0) {
            tbody.innerHTML = '';
        }

        if (batch.length === 0 && loadedRows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No visitor records found for the selected filters.</td></tr>`;
        } else if (batch.length > 0) {
            for (const log of batch) {
                loadedRows.push(log);
                const { date, time } = formatVisitDateTime(log.created_at);
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50';
                tr.innerHTML = `
                    <td class="px-4 py-3 text-gray-800">${escapeHtml(log.full_name || '')}</td>
                    <td class="px-4 py-3 text-gray-800">${escapeHtml(formatGender(log))}</td>
                    <td class="px-4 py-3 text-gray-800">${escapeHtml(formatAgeBirthdate(log))}</td>
                    <td class="px-4 py-3 text-gray-800">${escapeHtml(formatPurokAddress(log))}</td>
                    <td class="px-4 py-3 text-gray-800">${escapeHtml((log.purpose || '').replace(/\s+/g, ' ').trim())}</td>
                    <td class="px-4 py-3 text-gray-800">${escapeHtml(time)}</td>
                    <td class="px-4 py-3 text-gray-800">${escapeHtml(date)}</td>
                    <td class="px-4 py-3 text-gray-800">
                        <button type="button" class="text-blue-600 hover:text-blue-800 text-sm font-medium btn-view-detail" data-id="${escapeHtml(String(log.id))}">View</button>
                    </td>`;
                tbody.appendChild(tr);
            }
            tbody.querySelectorAll('.btn-view-detail').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-id');
                    const log = loadedRows.find((r) => String(r.id) === String(id));
                    if (log) openDetailModal(log);
                });
            });
        }

        nextOffset += batch.length;
        btnLoadMore.classList.toggle('hidden', loadedRows.length >= totalCount || batch.length === 0);
        updateTableSummary();
    } catch (e) {
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-8 text-center text-red-600">Failed to load: ${escapeHtml(e.message)}</td></tr>`;
        updateSummaryStrip(null);
    } finally {
        listLoading = false;
    }
}

function escapeCsvCell(val) {
    const s = String(val ?? '');
    if (/[",\n\r]/.test(s)) {
        return `"${s.replace(/"/g, '""')}"`;
    }
    return s;
}

function csvRow(cells) {
    return cells.map(escapeCsvCell).join(',');
}

function exportCsvFromRows(rows) {
    const from = dateFromInput.value;
    const to = dateToInput.value;
    const lines = [
        csvRow([branding.reportTitle]),
        csvRow([branding.barangayName]),
        csvRow(['Date range', `${from} to ${to}`]),
        '',
        csvRow(EXPORT_HEADERS),
    ];
    for (const log of rows) {
        lines.push(csvRow(rowToExportCells(log)));
    }
    const blob = new Blob(['\uFEFF' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
    const stamp = `${from}_${to}`;
    downloadBlob(blob, `visitor_report_${stamp}.csv`);
}

async function fetchLogoDataUrl() {
    try {
        const res = await fetch(branding.logoUrl, { credentials: 'same-origin' });
        if (!res.ok) return null;
        const blob = await res.blob();
        return await new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    } catch {
        return null;
    }
}

async function fetchLogoUint8() {
    try {
        const res = await fetch(branding.logoUrl, { credentials: 'same-origin' });
        if (!res.ok) return null;
        const buf = await res.arrayBuffer();
        return new Uint8Array(buf);
    } catch {
        return null;
    }
}

function exportPdfFromRows(rows) {
    const formatKey = paperSelect.value;
    const fmt = PDF_FORMATS[formatKey] || 'a4';
    const { jsPDF } = window.jspdf;
    if (!jsPDF) {
        alert('PDF library failed to load. Refresh the page and try again.');
        return Promise.resolve();
    }

    return (async () => {
        const logoDataUrl = await fetchLogoDataUrl();
        const matrix = rows.map((log) => rowToExportCells(log));
        if (matrix.length === 0) {
            alert('No data to export for the selected filters.');
            return;
        }

        const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: fmt });
        if (typeof doc.autoTable !== 'function') {
            alert('PDF table plugin failed to load. Refresh the page and try again.');
            return;
        }

        const pageW = doc.internal.pageSize.getWidth();
        let y = 12;

        if (logoDataUrl) {
            try {
                doc.addImage(logoDataUrl, 'PNG', (pageW - 22) / 2, y, 22, 22);
                y += 26;
            } catch {
                try {
                    doc.addImage(logoDataUrl, 'JPEG', (pageW - 22) / 2, y, 22, 22);
                    y += 26;
                } catch {
                    /* skip */
                }
            }
        }

        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        doc.text(branding.barangayName, pageW / 2, y, { align: 'center' });
        y += 7;
        doc.setFontSize(11);
        doc.text(branding.reportTitle, pageW / 2, y, { align: 'center' });
        y += 6;
        doc.setFont(undefined, 'normal');
        doc.setFontSize(9);
        doc.text(`Date range: ${dateFromInput.value} to ${dateToInput.value}`, pageW / 2, y, { align: 'center' });
        y += 8;

        doc.autoTable({
            startY: y,
            head: [EXPORT_HEADERS],
            body: matrix,
            theme: 'plain',
            styles: { fontSize: 8, cellPadding: 1.5, lineColor: [0, 0, 0], lineWidth: 0.1, textColor: [0, 0, 0] },
            headStyles: {
                fillColor: [255, 255, 255],
                textColor: [0, 0, 0],
                fontStyle: 'bold',
                lineWidth: 0.1,
                lineColor: [0, 0, 0],
            },
            margin: { left: 10, right: 10 },
        });

        const stamp = `${dateFromInput.value}_${dateToInput.value}`;
        doc.save(`visitor_report_${stamp}.pdf`);
    })().catch((e) => {
        console.error(e);
        alert('PDF export failed: ' + e.message);
    });
}

async function exportDocxFromRows(rows) {
    const matrix = rows.map((log) => rowToExportCells(log));
    if (matrix.length === 0) {
        alert('No data to export for the selected filters.');
        return;
    }

    let docx;
    try {
        docx = await import('https://esm.sh/docx@8.5.0');
    } catch (e) {
        console.error(e);
        alert('Could not load DOCX library. Check your network and try again.');
        return;
    }

    const {
        Document,
        Packer,
        Paragraph,
        Table,
        TableRow,
        TableCell,
        WidthType,
        AlignmentType,
        ImageRun,
        TextRun,
        BorderStyle,
    } = docx;

    const thinBorder =
        BorderStyle != null
            ? {
                  top: { style: BorderStyle.SINGLE, size: 1, color: '000000' },
                  bottom: { style: BorderStyle.SINGLE, size: 1, color: '000000' },
                  left: { style: BorderStyle.SINGLE, size: 1, color: '000000' },
                  right: { style: BorderStyle.SINGLE, size: 1, color: '000000' },
              }
            : null;

    const formatKey = paperSelect.value;
    const pageSize = DOCX_PAGE[formatKey] || DOCX_PAGE.a4;
    const logoBytes = await fetchLogoUint8();

    const headerChildren = [];

    if (logoBytes && logoBytes.length > 0) {
        headerChildren.push(
            new Paragraph({
                alignment: AlignmentType.CENTER,
                children: [
                    new ImageRun({
                        data: logoBytes,
                        transformation: { width: 96, height: 96 },
                    }),
                ],
            }),
        );
    }

    headerChildren.push(
        new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [new TextRun({ text: branding.barangayName, bold: true, size: 28 })],
        }),
        new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [new TextRun({ text: branding.reportTitle, bold: true, size: 24 })],
        }),
        new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [
                new TextRun({
                    text: `Date range: ${dateFromInput.value} to ${dateToInput.value}`,
                    size: 20,
                }),
            ],
        }),
        new Paragraph({ text: '' }),
    );

    const cellHeader = (text) => {
        const opts = {
            children: [new Paragraph({ children: [new TextRun({ text, bold: true })] })],
        };
        if (thinBorder) opts.borders = thinBorder;
        return new TableCell(opts);
    };
    const cellData = (text) => {
        const opts = { children: [new Paragraph({ text })] };
        if (thinBorder) opts.borders = thinBorder;
        return new TableCell(opts);
    };

    const headerRow = new TableRow({
        tableHeader: true,
        children: EXPORT_HEADERS.map((h) => cellHeader(h)),
    });

    const dataRows = matrix.map(
        (cells) =>
            new TableRow({
                children: cells.map((c) => cellData(c)),
            }),
    );

    const table = new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        rows: [headerRow, ...dataRows],
    });

    const doc = new Document({
        sections: [
            {
                properties: {
                    page: {
                        size: {
                            width: pageSize.width,
                            height: pageSize.height,
                        },
                    },
                },
                children: [...headerChildren, table],
            },
        ],
    });

    try {
        const blob = await Packer.toBlob(doc);
        const stamp = `${dateFromInput.value}_${dateToInput.value}`;
        downloadBlob(blob, `visitor_report_${stamp}.docx`);
    } catch (e) {
        console.error(e);
        alert('DOCX export failed: ' + e.message);
    }
}

function printReport(rows) {
    const matrix = rows.map((log) => rowToExportCells(log));
    if (matrix.length === 0) {
        alert('No data to print for the selected filters.');
        return;
    }
    const from = dateFromInput.value;
    const to = dateToInput.value;
    const logo = branding.logoUrl;
    const esc = (s) =>
        String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    const head = EXPORT_HEADERS.map((h) => `<th>${esc(h)}</th>`).join('');
    const tableRows = matrix.map((r) => `<tr>${r.map((c) => `<td>${esc(c)}</td>`).join('')}</tr>`).join('');
    const w = window.open('', '_blank');
    if (!w) {
        alert('Pop-up blocked. Allow pop-ups to print.');
        return;
    }
    w.document.write(`<!DOCTYPE html><html><head><title>${esc(branding.reportTitle)}</title><style>
body{font-family:Arial,sans-serif;padding:16px;}
.hdr{text-align:center;margin-bottom:16px;}
table{border-collapse:collapse;width:100%;font-size:11px;}
th,td{border:1px solid #000;padding:4px;text-align:left;vertical-align:top;}
th{font-weight:bold;background:#fff;}
</style></head><body>
<div class="hdr">
<img src="${esc(logo)}" alt="" style="max-height:72px;margin-bottom:8px"/>
<div style="font-weight:bold;font-size:14px">${esc(branding.barangayName)}</div>
<div style="font-size:13px;margin-top:8px;font-weight:bold">${esc(branding.reportTitle)}</div>
<div style="font-size:11px;margin-top:6px">Date range: ${esc(from)} to ${esc(to)}</div>
</div>
<table><thead><tr>${head}</tr></thead><tbody>${tableRows}</tbody></table>
</body></html>`);
    w.document.close();
    w.focus();
    w.print();
}

function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

async function runExport(kind) {
    const labels = { csv: 'CSV', pdf: 'PDF', docx: 'DOCX' };
    const buttons = [
        [btnCsv, 'Export as CSV'],
        [btnPdf, 'Export as PDF'],
        [btnDocx, 'Export as DOCX'],
    ];
    buttons.forEach(([b]) => {
        if (b) b.disabled = true;
    });
    const activeBtn = kind === 'csv' ? btnCsv : kind === 'pdf' ? btnPdf : btnDocx;
    if (activeBtn) activeBtn.textContent = 'Preparing…';

    try {
        const rows = await fetchAllForExport();
        if (rows.length === 0) {
            alert('No visitor records match the current filters.');
            return;
        }
        if (kind === 'csv') {
            exportCsvFromRows(rows);
        } else if (kind === 'pdf') {
            await exportPdfFromRows(rows);
        } else {
            await exportDocxFromRows(rows);
        }
    } catch (e) {
        console.error(e);
        alert(`Could not prepare ${labels[kind]} export: ` + e.message);
    } finally {
        buttons.forEach(([b, label]) => {
            if (b) {
                b.disabled = false;
                b.textContent = label;
            }
        });
    }
}

btnApply.addEventListener('click', () => loadLogs(true));
btnLoadMore.addEventListener('click', () => loadLogs(false));
btnCsv.addEventListener('click', () => runExport('csv'));
btnPdf.addEventListener('click', () => runExport('pdf'));
btnDocx.addEventListener('click', () => runExport('docx'));
btnPrint.addEventListener('click', async () => {
    btnPrint.disabled = true;
    const prev = btnPrint.textContent;
    btnPrint.textContent = 'Preparing…';
    try {
        const rows = await fetchAllForExport();
        printReport(rows);
    } catch (e) {
        alert(e.message);
    } finally {
        btnPrint.disabled = false;
        btnPrint.textContent = prev;
    }
});

if (detailModalClose) detailModalClose.addEventListener('click', closeDetailModal);
if (detailModalBackdrop) detailModalBackdrop.addEventListener('click', closeDetailModal);

loadLogs(true);
