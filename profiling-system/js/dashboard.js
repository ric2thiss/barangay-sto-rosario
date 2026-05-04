/**
 * dashboard.js  — Barangay Sto. Rosario  (v2 — Unified Analytics)
 *
 * Unified model: residents + barangay officials treated as one population.
 * New filters: SES, PWD Type, Voter Status, Household No.
 * New charts:  SES distribution, Occupation Type, PWD Types,
 *              Voter Status (donut), Population Split (residents vs officials),
 *              Household distribution.
 */

/* ── Wait for Chart.js ─────────────────────────────────────────────────── */
function waitForChart(cb, attempts) {
    attempts = attempts || 0;
    if (typeof Chart !== 'undefined') { cb(); } else if (attempts < 80) { setTimeout(function() { waitForChart(cb, attempts + 1); }, 100); } else {
        console.error('[dashboard] Chart.js failed to load.');
        cb();
    }
}

/* ── State ──────────────────────────────────────────────────────────────── */
var _charts = {};
var _allPeople = [];

/* ── Colour palette ─────────────────────────────────────────────────────── */
var COLORS = [
    '#1a56db', '#0e9f6e', '#ff8a00', '#e02424', '#0891b2', '#7c3aed',
    '#be185d', '#065f46', '#92400e', '#1e3a5f', '#0d9488', '#d97706',
    '#dc2626', '#2563eb', '#16a34a', '#9333ea', '#ea580c', '#0284c7',
];
var SES_COLORS = {
    'Poor': '#dc2626',
    'Low Income': '#ea580c',
    'Lower Middle Income': '#d97706',
    'Middle Income': '#16a34a',
    'Upper Middle Income': '#0891b2',
    'High Income': '#7c3aed',
};

function clr(i) { return COLORS[i % COLORS.length]; }

function randClrs(n) { return Array.from({ length: n }, function(_, i) { return clr(i); }); }

/* ── UI helpers ─────────────────────────────────────────────────────────── */
function showLoading() { var el = document.getElementById('loadingOverlay'); if (el) el.classList.add('active'); }

function hideLoading() { var el = document.getElementById('loadingOverlay'); if (el) el.classList.remove('active'); }

function showOfflineBanner(title, msg) {
    var t = document.getElementById('offlineTitle');
    var m = document.getElementById('offlineMsg');
    var b = document.getElementById('offlineBanner');
    if (t) t.textContent = title;
    if (m) m.textContent = msg;
    if (b) b.classList.add('show');
}

function hideOfflineBanner() {
    var b = document.getElementById('offlineBanner');
    if (b) b.classList.remove('show');
}

function retryLoad() {
    hideOfflineBanner();
    loadDashboardData();
}

function clearFilters() {
    ['purokFilter', 'barangayFilter', 'categoryFilter', 'sesFilter', 'pwdTypeFilter',
        'voterStatusFilter', 'householdNoFilter'
    ].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.value = 'all';
    });
    // Hide PWD type row if visible
    var pwdRow = document.getElementById('pwdTypeRow');
    if (pwdRow) pwdRow.style.display = 'none';
    loadDashboardData();
}

/* ── Show / hide PWD Type sub-filter ────────────────────────────────────── */
function onCategoryChange() {
    var cat = document.getElementById('categoryFilter');
    var pwdRow = document.getElementById('pwdTypeRow');
    if (!cat || !pwdRow) return;
    pwdRow.style.display = (cat.value === 'pwd') ? '' : 'none';
    loadDashboardData();
}

/* ── Statistics panel ───────────────────────────────────────────────────── */
function updateStatistics(stats) {
    var map = {
        statPopulation: stats.total_population,
        statResidents: stats.total_residents_only,
        statOfficials: stats.total_officials_only,
        statDeceased: stats.total_deceased,
        statNewborns: stats.total_newborns,
        statSeniors: stats.total_seniors,
        statPwd: stats.total_pwd,
        statVoters: stats.total_voters,
        stat4ps: stats.total_4ps || 0,
        statSoloParent: stats.total_solo_parent || 0,
        statHypertension: stats.total_hypertension || 0,
        statDiabetes: stats.total_diabetes || 0,
        statSmokers: stats.total_smokers || 0,
        statNhts: stats.total_nhts || 0,
        statChildren017: stats.total_children_0_17 || 0,
        statLgbtq: stats.total_lgbtq || 0,
    };
    Object.keys(map).forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.textContent = (map[id] || 0).toLocaleString();
    });
}

/* ── Modal system ──────────────────────────────────────────────────── */
var _modalPeople = [];   // current modal dataset
var _modalTitle  = '';

/**
 * Client-side filter: extracts a sub-category from the already-loaded
 * _allPeople array.  This guarantees modal numbers always match the
 * stat-card numbers because both derive from the exact same dataset.
 */
function filterPeopleByStatCard(people, statKey) {
    if (!people || !people.length) return [];
    switch (statKey) {
        case 'all':           return people.slice();
        case 'residents':     return people.filter(function(p) { return p.source === 'resident'; });
        case 'official':
        case 'officials':     return people.filter(function(p) { return p.source === 'official'; });
        case 'pwd':           return people.filter(function(p) { return p.is_pwd === 'Yes'; });
        case 'deceased':      return people.filter(function(p) { return p.is_deceased === 'Yes'; });
        case 'newborns':      return people.filter(function(p) { return parseInt(p.age) <= 1; });
        case 'seniors':       return people.filter(function(p) { return parseInt(p.age) >= 60; });
        case 'children_0_17': return people.filter(function(p) { var a = parseInt(p.age); return a >= 0 && a <= 17; });
        case 'lgbtq':         return people.filter(function(p) { return p.sex === 'LGBTQ+'; });
        case 'voters':        return people.filter(function(p) { return p.voters_status === 'Yes'; });
        case '4ps':           return people.filter(function(p) { return p.is_4ps === 'Yes'; });
        case 'solo_parent':   return people.filter(function(p) { return p.is_solo_parent === 'Yes'; });
        case 'nhts':          return people.filter(function(p) { return p.is_nhts === 'Yes'; });
        case 'hypertension':  return people.filter(function(p) { return p.has_hypertension === 'Yes'; });
        case 'diabetes':      return people.filter(function(p) { return p.has_diabetes === 'Yes'; });
        case 'smokers':       return people.filter(function(p) { return p.is_smoker === 'Yes'; });
        default:              return people.slice();
    }
}

function openModalList(category, title) {
    _modalTitle = title;
    var overlay = document.getElementById('dashboardModal');
    var titleEl = document.getElementById('dmTitle');
    var search  = document.getElementById('dmSearch');

    if (titleEl) titleEl.textContent = title;
    if (search)  search.value = '';
    if (overlay) overlay.classList.add('open');
    document.body.style.overflow = 'hidden';

    // Filter from already-loaded data — instant & always matches stat cards
    _modalPeople = filterPeopleByStatCard(_allPeople, category);
    renderModalTable(_modalPeople);
}

function closeModal() {
    var overlay = document.getElementById('dashboardModal');
    if (overlay) overlay.classList.remove('open');
    document.body.style.overflow = '';
}

function renderModalTable(people) {
    var tbody   = document.getElementById('dmTableBody');
    var countEl = document.getElementById('dmCount');
    var footer  = document.getElementById('dmFooter');

    if (countEl) countEl.textContent = people.length.toLocaleString();
    if (footer)  footer.textContent = 'Showing ' + people.length.toLocaleString() + ' record' + (people.length !== 1 ? 's' : '');

    if (!tbody) return;
    if (!people.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="no-data"><i class="fas fa-search"></i> No records found.</td></tr>';
        return;
    }

    tbody.innerHTML = people.map(function(r) {
        var badges = [];
        if (r.source === 'official') badges.push('<span class="pill pill-blue">Official</span>');
        if (r.is_pwd === 'Yes')       badges.push('<span class="pill pill-red">PWD</span>');
        if (r.is_deceased === 'Yes')   badges.push('<span class="pill pill-dark">Deceased</span>');
        if (parseInt(r.age) >= 60)     badges.push('<span class="pill pill-orange">Senior</span>');
        if (parseInt(r.age) <= 17)     badges.push('<span class="pill pill-cyan">Child</span>');
        if (r.voters_status === 'Yes') badges.push('<span class="pill pill-gray">Voter</span>');
        if (r.is_4ps === 'Yes')        badges.push('<span class="pill pill-purple">4Ps</span>');
        if (r.is_solo_parent === 'Yes') badges.push('<span class="pill pill-teal">Solo Parent</span>');
        if (r.has_hypertension === 'Yes') badges.push('<span class="pill pill-rose">HPN</span>');
        if (r.has_diabetes === 'Yes')  badges.push('<span class="pill" style="background:#fffbeb;color:#d97706">DM</span>');
        if (r.is_smoker === 'Yes')     badges.push('<span class="pill pill-dark">Smoker</span>');
        if (r.is_nhts === 'Yes')       badges.push('<span class="pill pill-purple">NHTS</span>');

        var imgBase = r.source === 'official' ? 'uploads/officials/' : 'uploads/residents/';
        var fullName = (r.surname || '') + ', ' + (r.first_name || '') + (r.middle_name ? ' ' + r.middle_name : '') + (r.suffix ? ' ' + r.suffix : '');

        return '<tr data-search="' + fullName.toLowerCase() + ' ' + (r.purok || '').toLowerCase() + '">' +
            '<td><img src="' + (r.image_path ? (imgBase + r.image_path) : (imgBase + ((r.sex||'').toLowerCase()==='female'?'default_photo_female.jpg':((r.sex||'').toLowerCase()==='lgbtq'?'default_photo_lgbtq.jpg':'default_photo_male.jpg')))) + '" class="resident-img" onerror="this.onerror=null; this.src=\'' + imgBase + 'default_photo_male.jpg\';" alt=""></td>' +
            '<td style="font-weight:600">' + fullName + '</td>' +
            '<td>' + (r.age || '') + '</td>' +
            '<td>' + (r.sex || '') + '</td>' +
            '<td>' + (r.purok || '—') + '</td>' +
            '<td>' + (badges.join(' ') || '—') + '</td>' +
            '</tr>';
    }).join('');
}

function filterModalTable() {
    var query = (document.getElementById('dmSearch') || {}).value || '';
    query = query.toLowerCase().trim();
    var tbody = document.getElementById('dmTableBody');
    if (!tbody) return;
    var rows = tbody.querySelectorAll('tr[data-search]');
    var visible = 0;
    rows.forEach(function(row) {
        var match = !query || (row.dataset.search || '').indexOf(query) !== -1;
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    var footer = document.getElementById('dmFooter');
    if (footer) footer.textContent = 'Showing ' + visible.toLocaleString() + ' of ' + _modalPeople.length.toLocaleString() + ' record' + (_modalPeople.length !== 1 ? 's' : '');
    var countEl = document.getElementById('dmCount');
    if (countEl) countEl.textContent = visible.toLocaleString();
}

function printModalData() {
    // Build a dedicated print window with just the modal table
    var title = _modalTitle || 'Dashboard List';
    var tbody = document.getElementById('dmTableBody');
    if (!tbody) return;

    // Collect only visible rows
    var rows = tbody.querySelectorAll('tr');
    var visibleRows = [];
    rows.forEach(function(row) {
        if (row.style.display !== 'none') visibleRows.push(row.outerHTML);
    });

    var now = new Date();
    var dateStr = now.toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });

    var html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' + title + '</title>' +
        '<style>' +
        'body{font-family:Arial,sans-serif;margin:20px;color:#1e293b;}' +
        '.print-hdr{text-align:center;margin-bottom:16px;}' +
        '.print-hdr h2{font-size:14pt;margin:0;}' +
        '.print-hdr h3{font-size:11pt;font-weight:600;margin:4px 0;}' +
        '.print-hdr p{font-size:8pt;color:#64748b;margin:2px 0;}' +
        'table{width:100%;border-collapse:collapse;font-size:9pt;}' +
        'th{background:#f1f5f9;font-size:7.5pt;text-transform:uppercase;letter-spacing:.5px;padding:6px 8px;border:1px solid #d1d5db;text-align:left;}' +
        'td{padding:5px 8px;border:1px solid #e2e8f0;vertical-align:middle;}' +
        'img{width:28px;height:28px;border-radius:50%;object-fit:cover;}' +
        '.pill{display:inline-block;padding:1px 6px;border-radius:10px;font-size:6.5pt;font-weight:700;margin:1px;white-space:nowrap;}' +
        '.pill-blue{background:#eff6ff;color:#1a56db;}' +
        '.pill-red{background:#fef2f2;color:#e02424;}' +
        '.pill-dark{background:#f3f4f6;color:#374151;}' +
        '.pill-orange{background:#fff7ed;color:#ff8a00;}' +
        '.pill-cyan{background:#ecfeff;color:#0891b2;}' +
        '.pill-gray{background:#f8fafc;color:#64748b;}' +
        '.pill-purple{background:#f5f3ff;color:#7c3aed;}' +
        '.pill-teal{background:#f0fdfa;color:#0d9488;}' +
        '.pill-rose{background:#fff1f2;color:#be185d;}' +
        '</style></head><body>' +
        '<div class="print-hdr">' +
        '<h2>BARANGAY STO. ROSARIO</h2>' +
        '<h3>' + title + '</h3>' +
        '<p>Printed: ' + dateStr + '</p>' +
        '</div>' +
        '<table><thead><tr><th>Photo</th><th>Full Name</th><th>Age</th><th>Sex</th><th>Purok</th><th>Category / Status</th></tr></thead>' +
        '<tbody>' + visibleRows.join('') + '</tbody></table>' +
        '<script>window.onload=function(){window.print();window.onafterprint=function(){window.close();}}</' + 'script>' +
        '</body></html>';

    var w = window.open('', '_blank');
    if (w) {
        w.document.write(html);
        w.document.close();
    }
}


/* ── Household dropdown updater ─────────────────────────────────────────── */
function updateHouseholdDropdown(hhNumbers) {
    var sel = document.getElementById('householdNoFilter');
    if (!sel) return;
    var current = sel.value;
    while (sel.options.length > 1) sel.remove(1);
    (hhNumbers || []).forEach(function(hh) {
        var opt = document.createElement('option');
        opt.value = hh;
        opt.textContent = 'HH ' + hh;
        if (hh === current) opt.selected = true;
        sel.appendChild(opt);
    });
}

/* ── Chart factory ──────────────────────────────────────────────────────── */
function makeChart(id, type, labels, datasets, extraOpts) {
    if (typeof Chart === 'undefined') return;
    if (_charts[id]) {
        _charts[id].destroy();
        delete _charts[id];
    }
    var canvas = document.getElementById(id);
    if (!canvas) return;
    var isPie = (type === 'pie' || type === 'doughnut');
    _charts[id] = new Chart(canvas.getContext('2d'), {
        type: type,
        data: { labels: labels, datasets: datasets },
        options: Object.assign({
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: isPie ? 'bottom' : 'bottom',
                    labels: { font: { size: 11 }, padding: 10, boxWidth: 12 }
                },
                tooltip: {
                    callbacks: {
                        label: function(c) {
                            var l = c.label || '';
                            if (l) l += ': ';
                            var v = (c.parsed.y !== undefined ? c.parsed.y : c.parsed) || 0;
                            l += v.toLocaleString();
                            return l;
                        }
                    }
                }
            },
            scales: isPie ? {} : {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 } }, beginAtZero: true }
            }
        }, extraOpts || {})
    });
}

/* ── Officials charts (static — PHP data) ───────────────────────────────── */
function renderOfficialsCharts() {
    var officials = window.DASHBOARD_OFFICIALS || [];
    if (!officials.length) return;

    var pos = {},
        pur = {},
        ch = {},
        sx = { Male: 0, Female: 0 };
    officials.forEach(function(o) {
        pos[o.position] = (pos[o.position] || 0) + 1;
        var p = o.purok || 'Unassigned';
        pur[p] = (pur[p] || 0) + 1;
        var c = o.chairmanship || 'None';
        ch[c] = (ch[c] || 0) + 1;
        if (o.sex === 'Male' || o.sex === 'Female') sx[o.sex]++;
    });

    var cnt = function(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; };
    cnt('officialsByPositionCount', officials.length);
    cnt('officialsByPurokCount', officials.length);
    cnt('officialsByChairmanshipCount', officials.length);
    cnt('officialsBySexCount', sx.Male + sx.Female);

    makeChart('officialsPositionChart', 'doughnut', Object.keys(pos), [{ data: Object.values(pos), backgroundColor: randClrs(Object.keys(pos).length) }]);
    makeChart('officialsPurokChart', 'bar', Object.keys(pur), [{ label: 'Officials', data: Object.values(pur), backgroundColor: '#1a56db', borderRadius: 6 }]);
    makeChart('officialsChairmanshipChart', 'pie', Object.keys(ch), [{ data: Object.values(ch), backgroundColor: randClrs(Object.keys(ch).length) }]);
    makeChart('officialsSexChart', 'bar', ['Male', 'Female'], [{ label: 'Count', data: [sx.Male, sx.Female], backgroundColor: ['#1a56db', '#be185d'], borderRadius: 6 }]);
}

/* ── Destroy a single chart by canvas ID ────────────────────────────────── */
function clearChart(id) {
    if (_charts[id]) { _charts[id].destroy(); delete _charts[id]; }
}

/* ── All other charts ───────────────────────────────────────────────────── */
function renderAllCharts(c) {

    // Destroy ALL existing charts first so stale data never persists
    Object.keys(_charts).forEach(function(id) { clearChart(id); });


    // ── Population per purok (total unified)
    makeChart('populationChart', 'bar',
        c.population.map(function(p) { return p.purok; }), [{ label: 'Total Population', data: c.population.map(function(p) { return p.count; }), backgroundColor: '#0e9f6e', borderRadius: 6 }]
    );

    // ── Population split: Residents vs Officials
    if (c.population_split && c.population_split.length) {
        makeChart('populationSplitChart', 'bar',
            c.population_split.map(function(p) { return p.purok; }), [
                { label: 'Residents', data: c.population_split.map(function(p) { return p.residents; }), backgroundColor: '#1a56db', borderRadius: 6 },
                { label: 'Officials', data: c.population_split.map(function(p) { return p.officials; }), backgroundColor: '#0e9f6e', borderRadius: 6 },
            ]
        );
    }

    // ── Socioeconomic Status distribution
    if (c.ses && c.ses.length) {
        var sesLabels = c.ses.map(function(s) { return s.label; });
        var sesBgColors = sesLabels.map(function(l) { return SES_COLORS[l] || '#94a3b8'; });
        makeChart('sesChart', 'doughnut',
            sesLabels, [{ data: c.ses.map(function(s) { return s.count; }), backgroundColor: sesBgColors }]
        );
    }

    // ── Occupation Type
    if (c.occupation_type && c.occupation_type.length) {
        makeChart('occupationTypeChart', 'bar',
            c.occupation_type.map(function(o) { return o.label; }), [{ label: 'Count', data: c.occupation_type.map(function(o) { return o.count; }), backgroundColor: randClrs(c.occupation_type.length), borderRadius: 6 }]
        );
    }

    // ── Income
    makeChart('incomeChart', 'bar',
        c.income.map(function(p) { return p.purok; }), [{ label: 'Monthly Income (₱)', data: c.income.map(function(p) { return p.total; }), backgroundColor: '#1a56db', borderRadius: 6 }]
    );

    // ── Sex distribution
    makeChart('sexChart', 'bar',
        c.sex.map(function(p) { return p.purok; }), [
            { label: 'Male', data: c.sex.map(function(p) { return p.male; }), backgroundColor: '#1a56db', borderRadius: 6 },
            { label: 'Female', data: c.sex.map(function(p) { return p.female; }), backgroundColor: '#be185d', borderRadius: 6 },
        ]
    );

    // ── Age groups
    makeChart('ageGroupsChart', 'bar',
        c.age_groups.map(function(p) { return p.purok; }), [
            { label: 'Children (0–14)', data: c.age_groups.map(function(p) { return p.children || 0; }), backgroundColor: '#fbbf24', borderRadius: 5 },
            { label: 'Youth (15–24)', data: c.age_groups.map(function(p) { return p.youth || 0; }), backgroundColor: '#3b82f6', borderRadius: 5 },
            { label: 'Adults (25–59)', data: c.age_groups.map(function(p) { return p.adults || 0; }), backgroundColor: '#10b981', borderRadius: 5 },
            { label: 'Seniors (60+)', data: c.age_groups.map(function(p) { return p.seniors || 0; }), backgroundColor: '#f97316', borderRadius: 5 },
        ]
    );

    // ── PWD per purok
    makeChart('pwdChart', 'bar',
        c.pwd.map(function(p) { return p.purok; }), [{ label: 'PWD Count', data: c.pwd.map(function(p) { return p.count; }), backgroundColor: '#e02424', borderRadius: 6 }]
    );

    // ── PWD Type breakdown
    if (c.pwd_types && c.pwd_types.length) {
        makeChart('pwdTypeChart', 'doughnut',
            c.pwd_types.map(function(p) { return p.label; }), [{ data: c.pwd_types.map(function(p) { return p.count; }), backgroundColor: randClrs(c.pwd_types.length) }]
        );
    }

    // ── Voter Status (donut)
    if (c.voter_status && c.voter_status.length) {
        makeChart('voterStatusChart', 'doughnut',
            c.voter_status.map(function(v) { return v.label; }), [{ data: c.voter_status.map(function(v) { return v.count; }), backgroundColor: ['#0e9f6e', '#e02424'] }]
        );
    }

    // ── Voters per purok (bar)
    makeChart('votersChart', 'bar',
        (c.voters || []).map(function(p) { return p.purok; }), [{ label: 'Registered Voters', data: (c.voters || []).map(function(p) { return p.count; }), backgroundColor: '#0891b2', borderRadius: 6 }]
    );

    // ── Seniors / Deceased / Newborns per purok
    makeChart('seniorChart', 'bar', c.seniors.map(function(p) { return p.purok; }), [{ label: 'Seniors', data: c.seniors.map(function(p) { return p.count; }), backgroundColor: '#ff8a00', borderRadius: 6 }]);
    makeChart('deceasedChart', 'bar', c.deceased.map(function(p) { return p.purok; }), [{ label: 'Deceased', data: c.deceased.map(function(p) { return p.count; }), backgroundColor: '#374151', borderRadius: 6 }]);
    makeChart('newbornChart', 'bar', c.newborns.map(function(p) { return p.purok; }), [{ label: 'Newborns', data: c.newborns.map(function(p) { return p.count; }), backgroundColor: '#0891b2', borderRadius: 6 }]);

    // ── Education
    var eduAtt = [],
        eduPur = [];
    c.education.forEach(function(e) { if (eduAtt.indexOf(e.attainment) === -1) eduAtt.push(e.attainment); });
    c.education.forEach(function(e) { if (eduPur.indexOf(e.purok) === -1) eduPur.push(e.purok); });
    makeChart('educationChart', 'bar', eduAtt,
        eduPur.map(function(pu, i) {
            return {
                label: pu,
                data: eduAtt.map(function(a) {
                    var found = c.education.find(function(e) { return e.purok === pu && e.attainment === a; });
                    return found ? found.count : 0;
                }),
                backgroundColor: clr(i),
                borderRadius: 4,
            };
        })
    );

    // ── Demographic: Religion & Ethnicity
    if (c.religion && c.religion.length) makeChart('religionChart', 'doughnut', c.religion.map(function(r) { return r.label; }), [{ data: c.religion.map(function(r) { return r.count; }), backgroundColor: randClrs(c.religion.length) }]);
    if (c.ethnicity && c.ethnicity.length) makeChart('ethnicityChart', 'bar', c.ethnicity.map(function(r) { return r.label; }), [{ label: 'Count', data: c.ethnicity.map(function(r) { return r.count; }), backgroundColor: randClrs(c.ethnicity.length), borderRadius: 6 }]);

    // ── Housing
    if (c.house_ownership && c.house_ownership.length) makeChart('houseOwnershipChart', 'doughnut', c.house_ownership.map(function(r) { return r.label; }), [{ data: c.house_ownership.map(function(r) { return r.count; }), backgroundColor: randClrs(c.house_ownership.length) }]);
    if (c.house_material && c.house_material.length) makeChart('houseMaterialChart', 'bar', c.house_material.map(function(r) { return r.label; }), [{ label: 'Households', data: c.house_material.map(function(r) { return r.count; }), backgroundColor: randClrs(c.house_material.length), borderRadius: 6 }]);
    if (c.water_source && c.water_source.length) makeChart('waterSourceChart', 'doughnut', c.water_source.map(function(r) { return r.label; }), [{ data: c.water_source.map(function(r) { return r.count; }), backgroundColor: randClrs(c.water_source.length) }]);
    if (c.toilet_type && c.toilet_type.length) makeChart('toiletTypeChart', 'pie', c.toilet_type.map(function(r) { return r.label; }), [{ data: c.toilet_type.map(function(r) { return r.count; }), backgroundColor: ['#0e9f6e', '#ff8a00', '#e02424', '#0891b2'] }]);

    // ── Health summary
    if (c.health_summary && c.health_summary.length) {
        var hs = c.health_summary.filter(function(h) { return h.count > 0; });
        if (hs.length) makeChart('healthSummaryChart', 'bar',
            hs.map(function(h) { return h.label; }), [{ label: 'Count', data: hs.map(function(h) { return h.count; }), backgroundColor: ['#e02424', '#d97706', '#0891b2', '#7c3aed', '#be185d', '#0d9488', '#374151', '#6b7280'], borderRadius: 6 }]
        );
    }

    // ── Health per purok
    if (c.health && c.health.length) {
        makeChart('healthChart', 'bar',
            c.health.map(function(h) { return h.purok; }), [
                { label: 'HPN', data: c.health.map(function(h) { return h.hpn; }), backgroundColor: '#e02424', borderRadius: 4 },
                { label: 'DM', data: c.health.map(function(h) { return h.dm; }), backgroundColor: '#d97706', borderRadius: 4 },
                { label: 'Asthma', data: c.health.map(function(h) { return h.asthma; }), backgroundColor: '#0891b2', borderRadius: 4 },
                { label: 'TB', data: c.health.map(function(h) { return h.tb; }), backgroundColor: '#7c3aed', borderRadius: 4 },
                { label: 'Cancer', data: c.health.map(function(h) { return h.cancer; }), backgroundColor: '#be185d', borderRadius: 4 },
                { label: 'Mental', data: c.health.map(function(h) { return h.mental; }), backgroundColor: '#0d9488', borderRadius: 4 },
            ]
        );
    }

    // ── Social programs
    if (c.social && c.social.length) {
        makeChart('socialChart', 'bar',
            c.social.map(function(s) { return s.purok; }), [
                { label: 'Family Planning', data: c.social.map(function(s) { return s.fp; }), backgroundColor: '#ff8a00', borderRadius: 4 },
                { label: 'Solo Parent', data: c.social.map(function(s) { return s.solo; }), backgroundColor: '#be185d', borderRadius: 4 },
                { label: 'NHTS', data: c.social.map(function(s) { return s.nhts; }), backgroundColor: '#0d9488', borderRadius: 4 },
                { label: '4Ps', data: c.social.map(function(s) { return s.fourps; }), backgroundColor: '#7c3aed', borderRadius: 4 },
            ]
        );
    }

    // ── PhilHealth membership
    if (c.membership && c.membership.length) makeChart('membershipChart', 'doughnut', c.membership.map(function(m) { return m.label; }), [{ data: c.membership.map(function(m) { return m.count; }), backgroundColor: randClrs(c.membership.length) }]);

    // ── Officials charts (from AJAX — respects all active filters)
    if (c.officials_position && c.officials_position.length) {
        var opTotal = c.officials_position.reduce(function(s, p) { return s + p.count; }, 0);
        var cnt = function(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; };
        cnt('officialsByPositionCount', opTotal);
        makeChart('officialsPositionChart', 'doughnut',
            c.officials_position.map(function(p) { return p.label; }),
            [{ data: c.officials_position.map(function(p) { return p.count; }), backgroundColor: randClrs(c.officials_position.length) }]
        );
    }
    if (c.officials_purok && c.officials_purok.length) {
        var opTotal2 = c.officials_purok.reduce(function(s, p) { return s + p.count; }, 0);
        var cnt2 = function(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; };
        cnt2('officialsByPurokCount', opTotal2);
        makeChart('officialsPurokChart', 'bar',
            c.officials_purok.map(function(p) { return p.label; }),
            [{ label: 'Officials', data: c.officials_purok.map(function(p) { return p.count; }), backgroundColor: '#1a56db', borderRadius: 6 }]
        );
    }
    if (c.officials_chairmanship && c.officials_chairmanship.length) {
        var cnt3 = function(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; };
        cnt3('officialsByChairmanshipCount', c.officials_chairmanship.reduce(function(s, p) { return s + p.count; }, 0));
        makeChart('officialsChairmanshipChart', 'pie',
            c.officials_chairmanship.map(function(p) { return p.label; }),
            [{ data: c.officials_chairmanship.map(function(p) { return p.count; }), backgroundColor: randClrs(c.officials_chairmanship.length) }]
        );
    }
    if (c.officials_sex && c.officials_sex.length) {
        var maleCount = 0, femaleCount = 0;
        c.officials_sex.forEach(function(s) {
            if (s.label === 'Male') maleCount = s.count;
            if (s.label === 'Female') femaleCount = s.count;
        });
        var cnt4 = function(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; };
        cnt4('officialsBySexCount', maleCount + femaleCount);
        makeChart('officialsSexChart', 'bar', ['Male', 'Female'],
            [{ label: 'Count', data: [maleCount, femaleCount], backgroundColor: ['#1a56db', '#be185d'], borderRadius: 6 }]
        );
    }

    // Re-inject CSV export buttons if available
    if (typeof injectExportButtons === 'function') setTimeout(injectExportButtons, 150);
}

/* ── Print tables ───────────────────────────────────────────────────────── */
function updatePrintTables(people, activeFilters) {
    _allPeople = people || [];
    var purokEl = document.getElementById('purokFilter');
    var purok = purokEl ? purokEl.value : 'all';
    var purokLabel = purok === 'all' ? 'All Puroks' : purok;

    var now = new Date();
    var dateStr = now.toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });

    ['printYear', 'printPurok', 'printDate'].forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        if (id === 'printYear') el.textContent = now.getFullYear();
        if (id === 'printPurok') el.textContent = purokLabel;
        if (id === 'printDate') el.textContent = dateStr;
    });
    document.querySelectorAll('.printYearP2').forEach(function(e) { e.textContent = now.getFullYear(); });
    document.querySelectorAll('.printPurokP2').forEach(function(e) { e.textContent = purokLabel; });

    // Sort by purok → surname
    var sorted = people.slice().sort(function(a, b) {
        if ((a.purok || '') < (b.purok || '')) return -1;
        if ((a.purok || '') > (b.purok || '')) return 1;
        if ((a.surname || '') < (b.surname || '')) return -1;
        if ((a.surname || '') > (b.surname || '')) return 1;
        return 0;
    });

    // Census table (page 1)
    var censusRows = '';
    var lastPurok = null;
    sorted.forEach(function(r) {
        if (r.purok !== lastPurok) {
            lastPurok = r.purok;
            censusRows += '<tr><td colspan="20" style="background:#0f3c6e;color:#fff;font-weight:bold;padding:4px 8px;font-size:8pt">📍 ' + (r.purok || 'Unknown') + '</td></tr>';
        }
        var sexLetter = r.sex === 'Male' ? 'M' : r.sex === 'Female' ? 'F' : '';
        var occ = r.occupation || '';
        var govKw = ['teacher', 'nurse', 'brgy', 'barangay', 'government', 'lgu', 'police', 'military', 'bhw', 'bhu'];
        var isGov = govKw.some(function(k) { return occ.toLowerCase().indexOf(k) !== -1; });
        censusRows += '<tr style="font-size:6.5pt">' +
            '<td style="text-align:center">' + (r.household_no || '') + '</td>' +
            '<td>' + (r.surname || '') + '</td>' +
            '<td>' + (r.first_name || '') + (r.suffix ? ' ' + r.suffix : '') + '</td>' +
            '<td>' + (r.middle_name || '') + '</td>' +
            '<td style="text-align:center">' + sexLetter + '</td>' +
            '<td style="text-align:center">' + (r.birthdate || '') + '</td>' +
            '<td>' + (r.birthplace || '') + '</td>' +
            '<td style="text-align:center">' + (r.age || '') + '</td>' +
            '<td style="text-align:center">' + ((r.civil_status || '').charAt(0)) + '</td>' +
            '<td>' + (r.household_position || '') + '</td>' +
            '<td style="font-size:6pt">' + (r.socioeconomic_status || '') + '</td>' +
            '<td style="font-size:6pt">' + (r.educational_attainment || '') + '</td>' +
            '<td></td>' +
            '<td style="font-size:6pt">' + (r.educational_attainment || '') + '</td>' +
            '<td style="font-size:6pt">' + (isGov ? '' : occ) + '</td>' +
            '<td style="font-size:6pt">' + (isGov ? occ : '') + '</td>' +
            '<td style="font-size:6pt">' + (r.religion || '') + '</td>' +
            '<td style="font-size:6pt">' + (r.ethnicity || '') + '</td>' +
            '<td style="font-size:6pt">' + (r.philhealth_no || '') + '</td>' +
            '<td style="font-size:6pt">' + (r.is_pwd === 'Yes' ? (r.pwd_type || 'PWD') : '') + '</td>' +
            '</tr>';
    });
    var cb = document.getElementById('censusTableBody');
    if (cb) cb.innerHTML = censusRows || '<tr><td colspan="20" style="text-align:center">No data</td></tr>';

    // Health table (page 2)
    var healthRows = '';
    var lastPurok2 = null;
    sorted.forEach(function(r) {
        if (r.purok !== lastPurok2) {
            lastPurok2 = r.purok;
            healthRows += '<tr><td colspan="23" style="background:#c8963e;color:#fff;font-weight:bold;padding:3px 6px;font-size:7pt">📍 ' + (r.purok || '') + '</td></tr>';
        }
        var chk = function(v) { return v === 'Yes' ? '✓' : ''; };
        var mpPrivate = r.membership_type === 'Private' ? '✓' : '';
        var mpGov = r.membership_type === 'Government' ? '✓' : '';
        var mpNhts = r.membership_type === 'NHTS' ? '✓' : chk(r.is_nhts);
        var tWith = r.toilet_type === 'With Flush' ? '✓' : '';
        var tWithout = r.toilet_type === 'Without Flush' ? '✓' : (r.toilet_type === 'None' ? '—' : '');

        healthRows += '<tr style="font-size:6.5pt">' +
            '<td style="text-align:center">' + (r.household_no || '') + '</td>' +
            '<td>' + (r.surname || '') + ', ' + (r.first_name || '') + ' ' + (r.middle_name || '') + '</td>' +
            '<td style="text-align:center;font-size:6pt">' + (r.philhealth_no || '') + '</td>' +
            '<td style="text-align:center">' + mpPrivate + '</td>' +
            '<td style="text-align:center">' + mpGov + '</td>' +
            '<td style="text-align:center">' + mpNhts + '</td>' +
            '<td style="text-align:center">' + chk(r.family_planning) + '</td>' +
            '<td style="text-align:center">' + tWith + '</td>' +
            '<td style="text-align:center">' + tWithout + '</td>' +
            '<td style="font-size:6pt">' + (r.water_source || '') + '</td>' +
            '<td style="text-align:center">' + chk(r.is_smoker) + '</td>' +
            '<td style="text-align:center">' + chk(r.is_binge_drinker) + '</td>' +
            '<td style="text-align:center">' + chk(r.has_hypertension) + '</td>' +
            '<td style="text-align:center">' + chk(r.has_diabetes) + '</td>' +
            '<td style="text-align:center">' + chk(r.has_asthma) + '</td>' +
            '<td style="text-align:center">' + chk(r.has_tb) + '</td>' +
            '<td style="text-align:center">' + chk(r.has_cancer) + '</td>' +
            '<td style="text-align:center">' + chk(r.has_mental_health) + '</td>' +
            '<td style="text-align:center">' + chk(r.is_pwd) + '</td>' +
            '<td style="text-align:center">' + chk(r.is_4ps) + '</td>' +
            '<td style="text-align:center">' + chk(r.is_solo_parent) + '</td>' +
            '<td style="text-align:center">' + chk(r.is_nhts) + '</td>' +
            '<td></td>' +
            '</tr>';
    });
    var hb = document.getElementById('healthTableBody');
    if (hb) hb.innerHTML = healthRows || '<tr><td colspan="23" style="text-align:center">No data</td></tr>';
}

function printResidentList() {
    if (!_allPeople.length) {
        alert('No data loaded yet. Please wait for data to load.');
        return;
    }
    window.print();
}




/* ── Main loader ────────────────────────────────────────────────────────── */
function loadDashboardData() {
    var purok = (document.getElementById('purokFilter') || {}).value || 'all';
    var barangay = (document.getElementById('barangayFilter') || {}).value || 'all';
    var category = (document.getElementById('categoryFilter') || {}).value || 'all';
    var ses = (document.getElementById('sesFilter') || {}).value || 'all';
    var pwdType = (document.getElementById('pwdTypeFilter') || {}).value || 'all';
    var voterStatus = (document.getElementById('voterStatusFilter') || {}).value || 'all';
    var householdNo = (document.getElementById('householdNoFilter') || {}).value || 'all';
    var gradCourse = (document.getElementById('gradCourseFilter') || {}).value || 'all';
    var gradYear = (document.getElementById('gradYearFilter') || {}).value || 'all';

    showLoading();

    var url = 'fetch_dashboard_data.php' +
        '?purok=' + encodeURIComponent(purok) +
        '&barangay=' + encodeURIComponent(barangay) +
        '&category=' + encodeURIComponent(category) +
        '&ses=' + encodeURIComponent(ses) +
        '&pwd_type=' + encodeURIComponent(pwdType) +
        '&voter_status=' + encodeURIComponent(voterStatus) +
        '&household_no=' + encodeURIComponent(householdNo) +
        '&grad_course=' + encodeURIComponent(gradCourse) +
        '&grad_year=' + encodeURIComponent(gradYear);

    fetch(url)
        .then(function(r) {
            var ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json'))
                throw new Error('Server returned non-JSON. Check MySQL/XAMPP.');
            return r.json();
        })
        .then(function(data) {
            hideLoading();
            if (!data.success) {
                showOfflineBanner('⚠ Database Error', data.message || 'Could not load data.');
                ['statPopulation', 'statResidents', 'statOfficials', 'statDeceased', 'statNewborns',
                    'statSeniors', 'statPwd', 'statVoters', 'stat4ps', 'statSoloParent',
                    'statHypertension', 'statDiabetes', 'statSmokers', 'statNhts'
                ].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) el.textContent = '—';
                });
                var tb = document.getElementById('peopleTableBody');
                if (tb) tb.innerHTML = '<tr><td colspan="13" class="no-data"><i class="fas fa-database"></i> Data unavailable</td></tr>';
                return;
            }

            hideOfflineBanner();
            updateStatistics(data.stats);
            updateHouseholdDropdown(data.hh_numbers);

            // Keep print census data accurate in memory
            updatePrintTables(data.people, data.active_filters);

            // Populate graduate sub-filter dropdowns
            if (data.grad_courses) {
                var cSel = document.getElementById('gradCourseFilter');
                if (cSel) {
                    var cv = cSel.value;
                    while (cSel.options.length > 1) cSel.remove(1);
                    data.grad_courses.forEach(function(c) {
                        var o = document.createElement('option');
                        o.value = c; o.textContent = c;
                        if (c === cv) o.selected = true;
                        cSel.appendChild(o);
                    });
                }
            }
            if (data.grad_years) {
                var ySel = document.getElementById('gradYearFilter');
                if (ySel) {
                    var yv = ySel.value;
                    while (ySel.options.length > 1) ySel.remove(1);
                    data.grad_years.forEach(function(y) {
                        var o = document.createElement('option');
                        o.value = y; o.textContent = y;
                        if (String(y) === yv) o.selected = true;
                        ySel.appendChild(o);
                    });
                }
            }

            waitForChart(function() { renderAllCharts(data.charts); });
        })
        .catch(function(err) {
            hideLoading();
            showOfflineBanner('⚠ Cannot Load Data', err.message || 'Network error.');
        });
}

/* ── Boot ───────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    waitForChart(renderOfficialsCharts);
});