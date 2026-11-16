<style>
    :root {
        --cc-navy: #0b2545;
        --cc-navy-2: #13315c;
        --cc-teal: #12a594;
        --cc-teal-soft: #e3f6f3;
        --cc-bg: #f4f7fa;
        --cc-border: #e2e8f0;
        --bs-primary: #13315c;
        --bs-primary-rgb: 19, 49, 92;
        --bs-link-color: #13315c;
    }
    body { background: var(--cc-bg); font-family: 'Segoe UI', system-ui, -apple-system, Roboto, sans-serif; color: #1f2a37; }
    .btn-primary { --bs-btn-bg: var(--cc-navy-2); --bs-btn-border-color: var(--cc-navy-2); --bs-btn-hover-bg: var(--cc-navy); --bs-btn-hover-border-color: var(--cc-navy); --bs-btn-active-bg: var(--cc-navy); }
    .btn-teal { --bs-btn-color: #fff; --bs-btn-bg: var(--cc-teal); --bs-btn-border-color: var(--cc-teal); --bs-btn-hover-color: #fff; --bs-btn-hover-bg: #0e8c7d; --bs-btn-hover-border-color: #0e8c7d; }
    .btn-outline-teal { --bs-btn-color: var(--cc-teal); --bs-btn-border-color: var(--cc-teal); --bs-btn-hover-bg: var(--cc-teal); --bs-btn-hover-border-color: var(--cc-teal); --bs-btn-hover-color: #fff; }
    .app-shell { display: flex; min-height: 100vh; }
    .sidebar { width: 250px; background: var(--cc-navy); color: #cbd5e1; position: fixed; inset: 0 auto 0 0; display: flex; flex-direction: column; z-index: 1030; transition: transform .2s; }
    .sidebar .brand { display: flex; align-items: center; gap: .75rem; padding: 1.1rem 1.25rem; color: #fff; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,.08); }
    .sidebar .brand small { display: block; font-size: .7rem; color: #94a3b8; }
    .brand-mark { width: 38px; height: 38px; border-radius: 10px; background: var(--cc-teal); display: grid; place-items: center; font-size: 1.2rem; }
    .sidebar nav { padding: .75rem; gap: .15rem; flex: 1; overflow-y: auto; }
    .sidebar .nav-link { color: #cbd5e1; border-radius: 8px; padding: .6rem .85rem; display: flex; align-items: center; gap: .75rem; font-size: .93rem; }
    .sidebar .nav-link:hover { background: rgba(255,255,255,.07); color: #fff; }
    .sidebar .nav-link.active { background: var(--cc-teal); color: #fff; font-weight: 600; }
    .sidebar-footer { padding: .9rem 1.25rem; font-size: .8rem; border-top: 1px solid rgba(255,255,255,.08); color: #94a3b8; }
    .main { flex: 1; margin-left: 250px; min-width: 0; display: flex; flex-direction: column; }
    .topbar { background: #fff; border-bottom: 1px solid var(--cc-border); padding: .6rem 1.5rem; display: flex; align-items: center; gap: .75rem; position: sticky; top: 0; z-index: 1020; }
    .content { padding: 1.5rem; max-width: 1400px; width: 100%; }
    .avatar { width: 26px; height: 26px; border-radius: 50%; background: var(--cc-teal); color: #fff; display: inline-grid; place-items: center; font-size: .75rem; font-weight: 700; }
    .notif-badge { position: absolute; top: -6px; right: -6px; font-size: .62rem; }
    .card { border: 1px solid var(--cc-border); border-radius: 12px; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
    .card-header { background: #fff; border-bottom: 1px solid var(--cc-border); font-weight: 600; border-radius: 12px 12px 0 0 !important; }
    .stat-card .icon { width: 44px; height: 44px; border-radius: 10px; display: grid; place-items: center; font-size: 1.3rem; }
    .stat-card .value { font-size: 1.6rem; font-weight: 700; line-height: 1.1; }
    .table > :not(caption) > * > * { padding: .7rem .75rem; }
    .table thead th { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; background: #f8fafc; white-space: nowrap; }
    .page-title { font-size: 1.4rem; font-weight: 700; color: var(--cc-navy); }
    .form-label { font-weight: 600; font-size: .85rem; }
    .slot-btn { min-width: 74px; }
    .empty-state { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; }
    .empty-state i { font-size: 2.2rem; display: block; margin-bottom: .5rem; }
    .sidebar-open .sidebar { transform: translateX(0) !important; }
    @media (max-width: 991.98px) {
        .sidebar { transform: translateX(-100%); }
        .main { margin-left: 0; }
        .content { padding: 1rem; }
    }
    @media print {
        .no-print { display: none !important; }
        .main { margin: 0 !important; }
        body { background: #fff; }
        .content { padding: 0; }
        .card { box-shadow: none; border: 1px solid #999; }
    }
</style>
