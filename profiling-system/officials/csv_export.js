/**
 * csv_export.js — Updated for Barangay Sto. Rosario Dashboard
 * Injects "Export CSV" buttons into every chart card and adds an
 * "Export ALL" + "Export Resident List" button in the filter bar.
 */

(function() {
    'use strict';

    // ── Map: canvas ID → { report key, label } ────────────────────────────
    const CHART_EXPORT_MAP = {
        // Population / core
        populationChart: { report: 'population', label: 'Population Summary per Purok' },
        incomeChart: { report: 'income', label: 'Income per Purok' },
        sexChart: { report: 'sex', label: 'Sex Distribution per Purok' },
        seniorChart: { report: 'seniors', label: 'Senior Citizens' },
        deceasedChart: { report: 'deceased', label: 'Deceased Residents' },
        newbornChart: { report: 'newborns', label: 'Newborns' },
        ageGroupsChart: { report: 'age_groups', label: 'Age Groups per Purok' },
        pwdChart: { report: 'pwd', label: 'PWD Residents' },
        votersChart: { report: 'voters', label: 'Registered Voters' },
        educationChart: { report: 'education', label: 'Educational Attainment' },
        // Demographic charts (NEW)
        religionChart: { report: 'religion', label: 'Religion Distribution' },
        ethnicityChart: { report: 'ethnicity', label: 'Ethnicity / IP Group' },
        houseOwnershipChart: { report: 'house_ownership', label: 'House Ownership' },
        houseMaterialChart: { report: 'house_material', label: 'House Material' },
        waterSourceChart: { report: 'water_source', label: 'Water Source' },
        toiletTypeChart: { report: 'toilet_type', label: 'Toilet Type' },
        healthSummaryChart: { report: 'health_summary', label: 'Health Conditions Summary' },
        healthChart: { report: 'health_per_purok', label: 'Health Conditions per Purok' },
        socialChart: { report: 'social_programs', label: 'Social Programs per Purok' },
        membershipChart: { report: 'membership', label: 'PhilHealth Membership' },
        // Officials charts
        officialsPositionChart: { report: 'officials_position', label: 'Officials by Position' },
        officialsPurokChart: { report: 'officials_purok', label: 'Officials by Purok' },
        officialsChairmanshipChart: { report: 'officials_chairmanship', label: 'Officials by Chairmanship' },
        officialsSexChart: { report: 'officials_sex', label: 'Officials by Sex' },
    };

    // ── Get currently selected purok ──────────────────────────────────────
    function getSelectedPurok() {
        var el = document.getElementById('purokFilter');
        return el ? encodeURIComponent(el.value) : 'all';
    }

    // ── Detect base path to export_csv.php ───────────────────────────────
    // dashboard.php lives at officials/dashboard.php
    // export_csv.php lives at officials/export_csv.php (same folder)
    // So we use a path relative to the current page's directory.
    function getExportBase() {
        // Build path relative to current page location
        var path = window.location.pathname; // e.g. /Profiling/sto.rosario/officials/dashboard.php
        var dir = path.substring(0, path.lastIndexOf('/') + 1); // e.g. /Profiling/sto.rosario/officials/
        return dir + 'export_csv.php';
    }

    // ── Trigger CSV / ZIP download ────────────────────────────────────────
    function downloadCSV(report) {
        var purok = getSelectedPurok();
        var baseUrl = getExportBase();
        var url = baseUrl + '?report=' + encodeURIComponent(report) + '&purok=' + purok;

        // For 'all' (ZIP/merged CSV): navigate directly so browser handles
        // the file download without the hidden-anchor trick failing silently.
        if (report === 'all' || report === 'residents_list') {
            window.location.href = url;
            return;
        }

        // For individual CSVs: hidden anchor approach works fine
        var a = document.createElement('a');
        a.href = url;
        a.download = '';
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    // ── Build a small Export CSV button ───────────────────────────────────
    function makeExportButton(report, label) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'csv-export-btn';
        btn.title = 'Export "' + label + '" as CSV';
        btn.innerHTML = '<i class="fas fa-file-csv"></i> CSV';

        btn.style.cssText = [
            'margin-left:auto',
            'padding:3px 10px',
            'border-radius:6px',
            'border:1px solid #0e9f6e',
            'background:#ecfdf5',
            'color:#065f46',
            'font-size:.72rem',
            'font-weight:700',
            'cursor:pointer',
            'white-space:nowrap',
            'line-height:1.4',
            'transition:background .15s,color .15s',
            'flex-shrink:0'
        ].join(';');

        btn.addEventListener('mouseenter', function() {
            this.style.background = '#0e9f6e';
            this.style.color = '#fff';
        });
        btn.addEventListener('mouseleave', function() {
            this.style.background = '#ecfdf5';
            this.style.color = '#065f46';
        });
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            downloadCSV(report);
        });
        return btn;
    }

    // ── Inject export button into every matching .chart-card ─────────────
    function injectExportButtons() {
        Object.keys(CHART_EXPORT_MAP).forEach(function(canvasId) {
            var meta = CHART_EXPORT_MAP[canvasId];
            var canvas = document.getElementById(canvasId);
            if (!canvas) return;

            var card = canvas.closest('.chart-card');
            if (!card) return;

            var heading = card.querySelector('h6');
            if (!heading) return;

            if (heading.querySelector('.csv-export-btn')) return; // already injected

            heading.style.display = 'flex';
            heading.style.alignItems = 'center';
            heading.style.gap = '8px';

            heading.appendChild(makeExportButton(meta.report, meta.label));
        });
    }

    // ── Inject filter-bar buttons ─────────────────────────────────────────
    function injectFilterBarButtons() {
        var filterBar = document.querySelector('.filter-bar');
        if (!filterBar) return;

        // ── "Export Resident List" button ─────────────────────────────────
        if (!document.getElementById('exportResidentListBtn')) {
            var wrapper1 = document.createElement('div');
            wrapper1.style.cssText = 'display:flex;flex-direction:column;justify-content:flex-end;';

            var lbl1 = document.createElement('label');
            lbl1.style.cssText = [
                'font-size:.78rem', 'font-weight:600', 'color:#64748b',
                'text-transform:uppercase', 'letter-spacing:.5px',
                'display:block', 'margin-bottom:6px'
            ].join(';');
            lbl1.innerHTML = '<i class="fas fa-list"></i> Resident List';

            var btn1 = document.createElement('button');
            btn1.type = 'button';
            btn1.id = 'exportResidentListBtn';
            btn1.innerHTML = '<i class="fas fa-file-csv"></i> Export Resident List';
            btn1.style.cssText = [
                'display:inline-flex', 'align-items:center', 'gap:6px',
                'padding:8px 18px', 'border-radius:8px', 'border:none',
                'background:#0e9f6e', 'color:#fff', 'font-size:.875rem',
                'font-weight:700', 'cursor:pointer', 'height:38px',
                'white-space:nowrap', 'transition:opacity .15s'
            ].join(';');
            btn1.addEventListener('mouseenter', function() { this.style.opacity = '.85'; });
            btn1.addEventListener('mouseleave', function() { this.style.opacity = '1'; });
            btn1.addEventListener('click', function() { downloadCSV('residents_list'); });

            wrapper1.appendChild(lbl1);
            wrapper1.appendChild(btn1);
            filterBar.appendChild(wrapper1);
        }

        // ── "Export ALL CSVs" button ──────────────────────────────────────
        if (!document.getElementById('exportAllBtn')) {
            var wrapper2 = document.createElement('div');
            wrapper2.style.cssText = 'display:flex;flex-direction:column;justify-content:flex-end;';

            var lbl2 = document.createElement('label');
            lbl2.style.cssText = [
                'font-size:.78rem', 'font-weight:600', 'color:#64748b',
                'text-transform:uppercase', 'letter-spacing:.5px',
                'display:block', 'margin-bottom:6px'
            ].join(';');
            lbl2.innerHTML = '<i class="fas fa-download"></i> Export All';

            var btn2 = document.createElement('button');
            btn2.type = 'button';
            btn2.id = 'exportAllBtn';
            btn2.innerHTML = '<i class="fas fa-file-archive"></i> Export ALL CSVs';
            btn2.title = 'Downloads as ZIP if available, or a single merged CSV file';
            btn2.style.cssText = [
                'display:inline-flex', 'align-items:center', 'gap:6px',
                'padding:8px 18px', 'border-radius:8px', 'border:none',
                'background:#1a56db', 'color:#fff', 'font-size:.875rem',
                'font-weight:700', 'cursor:pointer', 'height:38px',
                'white-space:nowrap', 'transition:opacity .15s'
            ].join(';');
            btn2.addEventListener('mouseenter', function() { this.style.opacity = '.85'; });
            btn2.addEventListener('mouseleave', function() { this.style.opacity = '1'; });
            btn2.addEventListener('click', function() { downloadCSV('all'); });

            wrapper2.appendChild(lbl2);
            wrapper2.appendChild(btn2);
            filterBar.appendChild(wrapper2);
        }
    }

    // ── Bootstrap ─────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {

        injectExportButtons();
        injectFilterBarButtons();

        // Patch fetch to re-inject buttons after AJAX chart renders
        var _origFetch = window.fetch;
        window.fetch = function() {
            var args = Array.prototype.slice.call(arguments);
            return _origFetch.apply(this, args).then(function(response) {
                var url = args[0] ? String(args[0]) : '';
                if (url.indexOf('fetch_dashboard_data') !== -1) {
                    setTimeout(injectExportButtons, 300);
                }
                return response;
            });
        };
    });

})();