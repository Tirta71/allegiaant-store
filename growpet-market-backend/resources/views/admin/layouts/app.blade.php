<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') - Growpet Market</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --ink: #17231b;
            --muted: #667064;
            --line: #dce4d5;
            --line-soft: #edf1e8;
            --surface: #ffffff;
            --surface-soft: #f8faf5;
            --canvas: #f1f4ec;
            --accent: #9bd348;
            --accent-soft: #eef8d8;
            --danger: #b42318;
            --shadow: 0 14px 36px rgba(20, 32, 25, .06);
            --shadow-soft: 0 8px 22px rgba(20, 32, 25, .04);
            color: var(--ink);
            background: var(--canvas);
        }

        * {
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(180deg, #f7f9f2 0%, var(--canvas) 34%, #eef2e8 100%);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        strong,
        b {
            font-weight: 500;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        .shell {
            min-height: 100vh;
        }

        .shell--admin {
            display: grid;
            grid-template-columns: 286px minmax(0, 1fr);
            background: transparent;
        }

        .shell--guest {
            display: grid;
            place-items: center;
            padding: 24px;
            background: linear-gradient(180deg, #f8faf4 0%, #eef2e8 100%);
        }

        .content {
            min-width: 0;
        }

        .sidebar {
            position: sticky;
            top: 0;
            align-self: start;
            display: flex;
            flex-direction: column;
            gap: 24px;
            height: 100vh;
            padding: 24px 20px;
            border-right: 1px solid rgba(255, 255, 255, .1);
            background: #111d16;
            color: #f6f8f3;
            box-shadow: 16px 0 34px rgba(20, 32, 25, .1);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            font-weight: 500;
        }

        .brand__mark {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            border-radius: 8px;
            background: var(--accent);
            color: var(--ink);
            box-shadow: inset 0 -2px 0 rgba(20, 32, 25, .12);
        }

        .brand strong {
            display: block;
            color: #fff;
            font-weight: 500;
        }

        .brand small {
            display: block;
            margin-top: 2px;
            color: #aebaaa;
            font-size: 12px;
            font-weight: 400;
        }

        .sidebar__section {
            display: grid;
            gap: 10px;
        }

        .sidebar__eyebrow,
        .content-bar__eyebrow {
            color: #8c9a8a;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .nav {
            display: grid;
            gap: 8px;
        }

        .nav a {
            display: flex;
            align-items: center;
            min-height: 46px;
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 10px 12px;
            color: #d8e0d4;
            font-weight: 400;
        }

        .nav a:hover {
            border-color: rgba(255, 255, 255, .12);
            background: rgba(255, 255, 255, .07);
            color: #fff;
        }

        .nav a.is-active {
            border-color: rgba(188, 231, 93, .48);
            background: #bce75d;
            color: #142019;
            box-shadow: 0 10px 22px rgba(188, 231, 93, .16);
        }

        .sidebar__footer {
            display: grid;
            gap: 12px;
            margin-top: auto;
            padding-top: 18px;
            border-top: 1px solid rgba(255, 255, 255, .1);
        }

        .admin-user {
            display: grid;
            grid-template-columns: 38px minmax(0, 1fr);
            align-items: center;
            gap: 10px;
        }

        .admin-user__avatar {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: #25362c;
            color: var(--accent);
            font-weight: 500;
        }

        .admin-user strong,
        .admin-user small {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .admin-user small {
            margin-top: 2px;
            color: #aebaaa;
            font-size: 12px;
            font-weight: 400;
        }

        .logout-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 42px;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 8px;
            padding: 9px 12px;
            background: rgba(255, 255, 255, .06);
            color: #f6f7f2;
            font-weight: 400;
            cursor: pointer;
        }

        .logout-button:hover {
            border-color: rgba(255, 255, 255, .22);
            background: rgba(255, 255, 255, .1);
        }

        .content-bar {
            position: sticky;
            top: 0;
            z-index: 8;
            display: grid;
            align-items: center;
            width: min(1160px, calc(100% - 48px));
            min-height: 72px;
            margin: 0 auto;
            padding-top: 8px;
            border-bottom: 1px solid rgba(220, 228, 213, .72);
            background: rgba(247, 249, 242, .9);
            backdrop-filter: blur(14px);
        }

        .content-bar strong {
            display: block;
            margin-top: 3px;
            font-size: 18px;
            font-weight: 500;
        }

        .page {
            width: min(1160px, calc(100% - 48px));
            margin: 0 auto;
            padding: 28px 0 56px;
        }

        .shell--guest .page {
            width: min(100%, 520px);
            padding: 0;
        }

        .login-card {
            width: min(100%, 460px);
            margin: 18px auto 0;
            padding: 26px;
            box-shadow: var(--shadow);
        }

        .login-card h1 {
            font-size: 28px;
        }

        .login-card p {
            margin: 8px 0 20px;
        }

        .section-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        h1,
        h2,
        h3 {
            margin: 0;
            line-height: 1.15;
            color: var(--ink);
            font-weight: 500;
        }

        h1 {
            font-size: clamp(28px, 4vw, 42px);
            letter-spacing: 0;
        }

        h2 {
            font-size: 20px;
            letter-spacing: 0;
        }

        h3 {
            font-size: 16px;
            letter-spacing: 0;
        }

        p {
            color: var(--muted);
            line-height: 1.6;
        }

        .panel,
        .card {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: var(--shadow);
        }

        .panel {
            padding: 20px;
        }

        .quick-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .quick-card {
            display: grid;
            gap: 10px;
            align-content: start;
            min-height: 138px;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }

        .quick-card:hover {
            transform: translateY(-2px);
            border-color: #b7c9a8;
            box-shadow: var(--shadow);
        }

        .quick-card span {
            display: inline-flex;
            width: fit-content;
            border-radius: 999px;
            padding: 4px 9px;
            background: var(--accent-soft);
            color: #35530f;
            font-size: 12px;
            font-weight: 400;
        }

        .quick-card p {
            margin: 0;
        }

        .grid {
            display: grid;
            gap: 16px;
        }

        .stats {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }

        .stat {
            padding: 18px;
        }

        .stat span {
            display: block;
            color: var(--muted);
            font-size: 13px;
        }

        .stat strong {
            display: block;
            margin-top: 8px;
            font-size: 27px;
            font-weight: 500;
        }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: flex-start;
            gap: 12px;
            margin-bottom: 16px;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            align-items: end;
            gap: 12px;
            width: 100%;
        }

        .toolbar>.filters {
            flex: 1 1 100%;
        }

        html.has-js form.is-auto-filter button[type="submit"] {
            display: none;
        }

        label {
            display: grid;
            gap: 7px;
            color: #4b5a4f;
            font-size: 13px;
            font-weight: 500;
        }

        .field-hint {
            color: #798473;
            font-size: 12px;
            font-weight: 400;
            line-height: 1.45;
        }

        input,
        select,
        textarea {
            width: 100%;
            min-width: 0;
            min-height: 42px;
            border: 1px solid #cbd8c1;
            border-radius: 8px;
            padding: 9px 12px;
            background: #fbfcf8;
            color: var(--ink);
            outline: none;
            transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #7c9b4a;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(155, 211, 72, .22);
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            align-items: start;
            gap: 16px;
        }

        .form-section {
            display: grid;
            gap: 14px;
            padding: 16px;
            border: 1px solid var(--line-soft);
            border-radius: 8px;
            background: var(--surface-soft);
        }

        .form-section h2 {
            font-size: 18px;
        }

        .form-grid .full {
            grid-column: 1 / -1;
        }

        .form-grid>.actions,
        .form-grid>.check-row {
            align-self: end;
            min-height: 42px;
        }

        .notice {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 11px 12px;
            background: var(--surface);
            color: #4b5a4f;
            font-size: 13px;
            font-weight: 500;
        }

        .check-row {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .check-row input {
            width: auto;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
            border: 1px solid var(--ink);
            border-radius: 8px;
            padding: 9px 14px;
            background: var(--ink);
            color: #fff;
            line-height: 1.15;
            text-align: center;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            transition: transform .16s ease, filter .16s ease, border-color .16s ease, background .16s ease;
        }

        .button:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
        }

        .button.secondary {
            border-color: #cbd8c1;
            background: #fff;
            color: var(--ink);
        }

        .button.danger {
            border-color: var(--danger);
            background: var(--danger);
        }

        .button.success {
            border-color: #3d6f1d;
            background: #3d6f1d;
            color: #fff;
        }

        .button.soft {
            border-color: var(--line);
            background: var(--accent-soft);
            color: #35530f;
        }

        .button.small {
            min-height: 34px;
            padding: 7px 10px;
            font-size: 13px;
        }

        table {
            width: 100%;
            min-width: 760px;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: auto;
        }

        th,
        td {
            border-bottom: 1px solid var(--line-soft);
            padding: 13px 14px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            color: var(--muted);
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .04em;
            background: var(--surface-soft);
            white-space: nowrap;
        }

        th:first-child {
            border-top-left-radius: 8px;
        }

        th:last-child {
            border-top-right-radius: 8px;
        }

        td:last-child,
        th:last-child {
            text-align: right;
        }

        td:last-child .actions {
            justify-content: flex-end;
        }

        td p:first-child {
            margin-top: 0;
        }

        td p:last-child {
            margin-bottom: 0;
        }

        tbody tr:hover {
            background: var(--surface-soft);
        }

        .table-wrap {
            overflow-x: auto;
            padding: 0;
        }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 240px;
        }

        .product-thumb {
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            border: 1px solid var(--line);
            border-radius: 8px;
            object-fit: cover;
            background: #fbfcf8;
        }

        .product-thumb--empty {
            display: grid;
            place-items: center;
            background: var(--accent-soft);
            color: #35530f;
            font-weight: 400;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 14px;
        }

        .product-card {
            display: grid;
            grid-template-rows: 180px minmax(0, 1fr);
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
            transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease;
        }

        .product-card:hover {
            transform: translateY(-2px);
            border-color: #c2d5b2;
            box-shadow: var(--shadow);
        }

        .product-card.is-muted {
            background: #fbfcf8;
        }

        .product-card__media {
            display: grid;
            place-items: center;
            overflow: hidden;
            border-bottom: 1px solid var(--line-soft);
            background:
                radial-gradient(circle at 24% 18%, rgba(155, 211, 72, .24), transparent 32%),
                linear-gradient(135deg, #fbfcf8, #eef4e7);
        }

        .product-card__media img {
            display: block;
            width: auto;
            height: auto;
            max-width: calc(100% - 24px);
            max-height: calc(100% - 24px);
            object-fit: contain;
            object-position: center;
        }

        .product-card__media span {
            display: grid;
            place-items: center;
            width: 64px;
            height: 64px;
            border: 1px solid #d8e7c8;
            border-radius: 8px;
            background: var(--accent-soft);
            color: #35530f;
            font-size: 24px;
            font-weight: 500;
        }

        .product-card__content {
            display: grid;
            align-content: start;
            gap: 13px;
            padding: 14px;
        }

        .product-card__head {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: start;
        }

        .product-card__head h2 {
            overflow: hidden;
            font-size: 18px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-card__head p {
            overflow: hidden;
            margin: 5px 0 0;
            color: #667064;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 12px;
            line-height: 1.35;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-card__badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            min-height: 27px;
            align-items: center;
        }

        .product-card__stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .product-card__stats div {
            min-width: 0;
            border: 1px solid var(--line-soft);
            border-radius: 8px;
            padding: 9px;
            background: var(--surface-soft);
        }

        .product-card__stats span {
            display: block;
            overflow: hidden;
            color: #667064;
            font-size: 11px;
            line-height: 1.2;
            text-overflow: ellipsis;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .product-card__stats strong {
            display: block;
            margin-top: 5px;
            color: #142019;
            font-size: 17px;
            font-weight: 500;
            line-height: 1.1;
        }

        .product-card__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            padding-top: 2px;
        }

        .product-card__actions form {
            margin: 0;
        }

        .product-empty {
            grid-column: 1 / -1;
        }

        .record-list {
            display: grid;
            gap: 12px;
        }

        .record-card {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
            transition: border-color .16s ease, box-shadow .16s ease;
        }

        .record-card:hover {
            border-color: #cbd8c1;
            box-shadow: var(--shadow);
        }

        .record-card summary {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 16px;
            cursor: pointer;
            list-style: none;
        }

        .record-card summary::-webkit-details-marker {
            display: none;
        }

        .record-card summary::after {
            content: "Edit";
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 6px 10px;
            background: #edf1e7;
            color: #4b5a4f;
            font-size: 12px;
            font-weight: 400;
        }

        .record-card[open] summary::after {
            content: "Tutup";
        }

        .record-card[open] {
            border-color: #cbd8c1;
        }

        .record-card__body {
            border-top: 1px solid var(--line-soft);
            padding: 16px;
        }

        .record-title {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 6px;
        }

        .record-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 14px;
            color: var(--muted);
            font-size: 13px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 9px;
            background: var(--accent-soft);
            color: #35530f;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
        }

        .badge.muted {
            background: #edf1e7;
            color: var(--muted);
        }

        .badge.warning {
            background: #fff4d6;
            color: #7a4d00;
        }

        .badge.success {
            background: #e8f8dd;
            color: #24520d;
        }

        .badge.danger {
            background: #fff0ee;
            color: #8a1f14;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .actions form {
            margin: 0;
        }

        .admin-alert,
        .errors {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
            gap: 12px;
            margin-bottom: 16px;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 13px 14px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
        }

        .admin-alert {
            border: 1px solid var(--accent);
            background: #f4fadf;
        }

        .admin-alert--success {
            border-color: rgba(61, 111, 29, .26);
            background: linear-gradient(135deg, #f4fadf, #fbfef5);
            color: #24520d;
        }

        .admin-alert--warning {
            border-color: rgba(151, 93, 35, .28);
            background: linear-gradient(135deg, #fff8de, #fffdf5);
            color: #734510;
        }

        .admin-alert--error {
            border-color: #f5b5ad;
            background: linear-gradient(135deg, #fff0ee, #fff8f7);
            color: #8a1f14;
        }

        .admin-alert__body {
            display: grid;
            gap: 3px;
            min-width: 0;
        }

        .admin-alert__title {
            color: currentColor;
            font-weight: 600;
        }

        .admin-alert__message {
            color: currentColor;
            line-height: 1.45;
        }

        .admin-alert__close {
            display: inline-grid;
            place-items: center;
            width: 28px;
            height: 28px;
            border: 0;
            border-radius: 999px;
            background: rgba(255, 255, 255, .64);
            color: currentColor;
            cursor: pointer;
        }

        .errors {
            border: 1px solid #f5b5ad;
            background: #fff0ee;
            color: #8a1f14;
        }

        .pagination {
            margin-top: 18px;
        }

        .pagination nav>div:first-child {
            display: none;
        }

        .pagination--clean {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        .pagination__button,
        .pagination__meta {
            display: inline-flex;
            align-items: center;
            min-height: 38px;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 13px;
            line-height: 1.2;
        }

        .pagination__button {
            border: 1px solid #cbd8c1;
            background: #fff;
            color: #17231b;
        }

        .pagination__button:hover {
            border-color: #aebfa1;
            background: var(--surface-soft);
        }

        .pagination__button.is-disabled {
            color: #9aa59a;
            cursor: not-allowed;
        }

        .pagination__meta {
            color: var(--muted);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 16px;
        }

        .two-column {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 16px;
            align-items: start;
        }

        .empty-state {
            padding: 28px;
            text-align: center;
            border: 1px dashed #cbd8c1;
            border-radius: 8px;
            background: var(--surface-soft);
        }

        .empty-state p {
            margin-bottom: 0;
        }

        .mini-list {
            display: grid;
            gap: 10px;
            margin-top: 12px;
        }

        .mini-list a,
        .mini-list div {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border: 1px solid var(--line-soft);
            border-radius: 8px;
            padding: 11px 12px;
            background: var(--surface);
        }

        .filter-panel {
            margin-bottom: 16px;
            padding: 14px;
            box-shadow: none;
        }

        .filter-panel .filters {
            grid-template-columns: minmax(260px, 1.6fr) minmax(170px, .75fr) minmax(170px, .75fr);
        }

        .filter-panel label {
            gap: 6px;
            color: var(--muted);
            font-size: 12px;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .order-table td {
            vertical-align: middle;
        }

        .order-table {
            min-width: 980px;
        }

        .order-table--history {
            min-width: 980px;
        }

        .order-history-list {
            display: grid;
            gap: 12px;
        }

        .order-history-card {
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
            transition: border-color .16s ease, box-shadow .16s ease;
        }

        .order-history-card:hover {
            border-color: #cbd8c1;
            box-shadow: var(--shadow);
        }

        .order-history-card__top {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            align-items: start;
            padding: 16px;
            border-bottom: 1px solid var(--line-soft);
        }

        .order-history-id {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .order-history-time,
        .order-history-muted {
            color: #667064;
            font-size: 12px;
            line-height: 1.35;
        }

        .order-history-total {
            display: grid;
            justify-items: end;
            gap: 3px;
            white-space: nowrap;
        }

        .order-history-total span,
        .order-history-label {
            color: #798473;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .order-history-total strong {
            color: #142019;
            font-size: 18px;
            font-weight: 500;
            line-height: 1.15;
        }

        .order-history-card__body {
            display: grid;
            grid-template-columns: 170px 140px minmax(0, 1fr) 150px 150px;
            gap: 18px;
            padding: 16px;
            border-bottom: 1px solid var(--line-soft);
        }

        .order-history-block {
            display: grid;
            align-content: start;
            min-width: 0;
            gap: 7px;
        }

        .order-history-block--items {
            gap: 9px;
        }

        .order-history-buyer-name {
            overflow: hidden;
            width: fit-content;
            max-width: 100%;
            border-radius: 8px;
            padding: 6px 8px;
            background: #eef8d8;
            color: #203a10;
            font-size: 15px;
            font-weight: 500;
            line-height: 1.2;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .order-history-contact {
            color: #667064;
            font-size: 13px;
            line-height: 1.35;
            word-break: break-word;
        }

        .order-history-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            padding: 12px 16px 16px;
        }

        .order-history-actions form {
            margin: 0;
        }

        .order-overlay-actions {
            margin: -4px 0 16px;
        }

        .order-table--history th:nth-child(3),
        .order-table--history td:nth-child(3) {
            width: 38%;
        }

        .order-table--history th:nth-child(4),
        .order-table--history td:nth-child(4) {
            min-width: 150px;
        }

        .order-table--history th:last-child,
        .order-table--history td:last-child {
            min-width: 150px;
        }

        .order-table tbody tr:hover {
            background: var(--surface-soft);
        }

        .order-cell {
            display: grid;
            justify-items: start;
            gap: 7px;
            min-width: 155px;
        }

        .order-code {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            min-height: 30px;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 5px 9px;
            background: #fff;
            font-weight: 500;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 13px;
            color: #142019;
        }

        .order-code:hover {
            border-color: #b7c9a8;
            background: var(--surface-soft);
            color: #35530f;
        }

        .order-total {
            color: #142019;
            font-weight: 500;
            white-space: nowrap;
        }

        .order-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .type-pill {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 4px 10px;
            background: #f8faf5;
            color: #4b5a4f;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
        }

        .type-pill.pet {
            border-color: #d2e2bd;
            background: #f5faeb;
            color: #35530f;
        }

        .type-pill.token {
            border-color: #cfdce7;
            background: #f4f8fb;
            color: #28536a;
        }

        .order-buyer {
            display: grid;
            min-width: 185px;
        }

        .order-buyer__content {
            display: grid;
            min-width: 0;
            width: fit-content;
            max-width: 100%;
            gap: 7px;
            border: 1px solid #d8e7c8;
            border-radius: 8px;
            padding: 9px 10px;
            background: linear-gradient(180deg, #fbfef5, #f4faea);
        }

        .order-buyer__name {
            overflow: hidden;
            color: #203a10;
            font-size: 15px;
            font-weight: 500;
            line-height: 1.2;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .order-buyer__contact {
            display: inline-flex;
            width: fit-content;
            max-width: 100%;
            min-height: 24px;
            align-items: center;
            border: 1px solid var(--line-soft);
            border-radius: 999px;
            padding: 3px 8px;
            background: var(--surface-soft);
            color: var(--muted);
            font-size: 12px;
            line-height: 1.2;
            white-space: nowrap;
        }

        .order-items-list {
            display: grid;
            gap: 7px;
            min-width: 0;
        }

        .order-item-line {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            align-items: start;
            padding-bottom: 7px;
            border-bottom: 1px solid var(--line-soft);
        }

        .order-item-line:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .order-item-copy {
            display: grid;
            min-width: 0;
            gap: 3px;
        }

        .order-item-title {
            overflow: hidden;
            color: #142019;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.3;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .order-item-meta {
            color: #667064;
            font-size: 12px;
            line-height: 1.35;
        }

        .order-more-text {
            color: #667064;
            font-size: 12px;
        }

        .order-payment-summary {
            display: grid;
            justify-items: start;
            gap: 6px;
            min-width: 150px;
        }

        .order-payment-summary .muted {
            font-size: 12px;
            white-space: nowrap;
        }

        .proof-link {
            width: fit-content;
            border: 0;
            padding: 0;
            background: transparent;
            color: #35530f;
            font-size: 13px;
            font-weight: 500;
            text-decoration: underline;
            text-underline-offset: 3px;
            cursor: pointer;
            text-align: left;
        }

        .proof-link:hover {
            color: #203a10;
        }

        .is-modal-open {
            overflow: hidden;
        }

        .proof-modal {
            position: fixed;
            inset: 0;
            z-index: 30;
            display: grid;
            place-items: center;
            padding: 18px;
            opacity: 0;
            pointer-events: none;
            transition: opacity .16s ease;
        }

        .proof-modal[aria-hidden="false"] {
            opacity: 1;
            pointer-events: auto;
        }

        .proof-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(17, 29, 22, .54);
            backdrop-filter: blur(5px);
        }

        .proof-modal__panel {
            position: relative;
            display: grid;
            width: min(100%, 720px);
            max-height: min(760px, calc(100vh - 36px));
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 26px 70px rgba(20, 32, 25, .24);
        }

        .proof-modal__head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 14px;
            padding: 16px;
            border-bottom: 1px solid var(--line-soft);
        }

        .proof-modal__head h2 {
            margin-top: 3px;
            font-size: 19px;
        }

        .proof-modal__head p {
            margin: 4px 0 0;
            color: #667064;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 13px;
            line-height: 1.35;
        }

        .proof-modal__body {
            display: grid;
            place-items: center;
            min-height: 280px;
            overflow: auto;
            padding: 16px;
            background: #f8faf5;
        }

        .proof-modal__body img {
            display: block;
            max-width: 100%;
            max-height: calc(100vh - 190px);
            border-radius: 8px;
            object-fit: contain;
            background: #fff;
            box-shadow: var(--shadow-soft);
        }

        .quick-actions {
            display: grid;
            justify-content: flex-end;
            gap: 7px;
            min-width: 150px;
        }

        .quick-actions form {
            margin: 0;
            text-align: right;
        }

        .quick-actions .button {
            width: 100%;
        }

        .quick-actions__hint {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            border-radius: 8px;
            padding: 7px 9px;
            background: #f5f7f2;
            color: #667064;
            font-size: 12px;
            line-height: 1.15;
            white-space: nowrap;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 4px 10px;
            background: #fff;
            color: #4b5a4f;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
        }

        .status-pill.success {
            border-color: #b9ddb2;
            background: #f2faec;
            color: #24520d;
        }

        .status-pill.warning {
            border-color: #ecd893;
            background: #fff9e8;
            color: #7a4d00;
        }

        .status-pill.info {
            border-color: #b7cee3;
            background: #eef7ff;
            color: #1f4e72;
        }

        .status-pill.danger {
            border-color: #efb4ac;
            background: #fff5f3;
            color: #8a1f14;
        }

        .status-pill.muted {
            background: #f5f7f2;
            color: #667064;
        }

        .order-detail-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(180px, auto);
            align-items: end;
            gap: 16px;
            margin-bottom: 16px;
            box-shadow: none;
        }

        .order-detail-code {
            display: inline-flex;
            width: fit-content;
            min-height: 42px;
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 7px 12px;
            background: #fff;
            margin-top: 2px;
            color: #142019;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 24px;
            font-weight: 500;
            line-height: 1.05;
        }

        .order-detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 12px;
            margin-top: 9px;
            color: #667064;
            font-size: 13px;
            font-weight: 400;
        }

        .order-detail-meta span+span::before {
            content: "/";
            margin-right: 12px;
            color: #a9b5a4;
        }

        .order-detail-total {
            display: grid;
            justify-items: end;
            gap: 6px;
            min-width: 190px;
        }

        .order-detail-total strong {
            color: #142019;
            font-size: 24px;
            line-height: 1;
            white-space: nowrap;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .detail-list {
            display: grid;
            gap: 12px;
            margin: 14px 0 0;
        }

        .detail-list div {
            display: grid;
            grid-template-columns: 130px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--line-soft);
        }

        .detail-list div:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .detail-list dt {
            color: #667064;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .detail-list dd {
            margin: 0;
            color: #142019;
            font-weight: 400;
        }

        .order-section {
            margin-top: 16px;
        }

        .order-section .section-head {
            margin-bottom: 14px;
        }

        .order-section .section-head p {
            margin: 4px 0 0;
        }

        .order-panel-clean {
            box-shadow: none;
        }

        .delivery-proof-preview,
        .delivery-proof-empty,
        .delivery-proof-form,
        .delivery-proof-dropzone {
            margin-top: 14px;
        }

        .delivery-proof-preview {
            display: block;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #f8faf5;
        }

        .delivery-proof-preview img {
            display: block;
            width: 100%;
            max-height: 280px;
            object-fit: contain;
            background: #fff;
        }

        .delivery-proof-empty {
            display: grid;
            gap: 4px;
            border: 1px dashed #cbd8c1;
            border-radius: 8px;
            padding: 14px;
            background: #f8faf5;
            color: #667064;
        }

        .delivery-proof-empty strong {
            color: #142019;
        }

        .delivery-proof-form {
            display: grid;
            gap: 12px;
        }

        .delivery-proof-form--compact {
            gap: 6px;
            margin-top: 0;
        }

        .delivery-proof-form--compact .button {
            width: 100%;
            min-height: 32px;
            padding: 6px 8px;
            font-size: 12px;
        }

        .delivery-proof-form--side {
            grid-template-columns: minmax(220px, .9fr) minmax(240px, 1fr) max-content;
            align-items: stretch;
            gap: 10px;
            margin: 0;
            padding: 12px 16px;
            border-bottom: 1px solid var(--line-soft);
            background: #fbfcf8;
        }

        .delivery-proof-form--side .button {
            align-self: center;
            min-height: 40px;
            white-space: nowrap;
        }

        .delivery-proof-dropzone {
            display: grid;
            place-items: center;
            gap: 5px;
            min-height: 134px;
            border: 1px dashed #aebfa1;
            border-radius: 8px;
            padding: 16px;
            background:
                linear-gradient(135deg, rgba(245, 247, 242, .95), rgba(231, 242, 211, .68)),
                #f8faf5;
            color: #667064;
            cursor: pointer;
            text-align: center;
            transition: border-color .16s ease, background .16s ease, box-shadow .16s ease;
        }

        .delivery-proof-dropzone--compact {
            min-height: 46px;
            margin-top: 0;
            align-content: center;
            place-items: start;
            gap: 1px;
            padding: 7px 8px;
            border: 1px solid #d8e7c8;
            background: linear-gradient(180deg, #fbfef5, #f5faeb);
            text-align: left;
        }

        .delivery-proof-dropzone--side {
            min-height: 86px;
            margin-top: 0;
            align-content: center;
            place-items: start;
            padding: 14px;
            border: 1px solid #d8e7c8;
            background: #fff;
            text-align: left;
        }

        .delivery-proof-dropzone:hover,
        .delivery-proof-dropzone:focus,
        .delivery-proof-dropzone.is-dragging,
        .delivery-proof-dropzone.is-filled {
            border-color: #7c9b4a;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(155, 211, 72, .18);
            outline: 0;
        }

        .delivery-proof-dropzone input {
            max-width: 300px;
            min-height: auto;
            border: 0;
            padding: 0;
            background: transparent;
            font-size: 12px;
        }

        .delivery-proof-dropzone--compact input {
            display: none;
        }

        .delivery-proof-dropzone--side input {
            display: none;
        }

        .delivery-proof-dropzone__title {
            color: #142019;
            font-weight: 600;
        }

        .delivery-proof-dropzone__hint {
            max-width: 340px;
            font-size: 12px;
            line-height: 1.45;
        }

        .delivery-proof-dropzone--compact .delivery-proof-dropzone__title {
            font-size: 12px;
            line-height: 1.2;
        }

        .delivery-proof-dropzone--compact .delivery-proof-dropzone__hint {
            font-size: 11px;
            line-height: 1.2;
        }

        .delivery-proof-preview-box {
            display: grid;
            place-items: center;
            min-height: 86px;
            overflow: hidden;
            border: 1px solid var(--line-soft);
            border-radius: 8px;
            background: #fff;
            color: #667064;
            font-size: 12px;
            line-height: 1.35;
            text-align: center;
        }

        .delivery-proof-preview-box span[hidden] {
            display: none;
        }

        .delivery-proof-paste-preview[hidden] {
            display: none;
        }

        .delivery-proof-paste-preview {
            display: block;
            width: 100%;
            max-height: 240px;
            border: 1px solid var(--line);
            border-radius: 8px;
            object-fit: contain;
            background: #fff;
        }

        .delivery-proof-paste-preview--compact {
            max-height: 80px;
            margin-top: 0;
        }

        .delivery-proof-paste-preview--side {
            width: 100%;
            height: 100%;
            max-height: 140px;
            margin-top: 0;
            border: 0;
            border-radius: 0;
        }

        .compact-table {
            min-width: 0;
            table-layout: auto;
        }

        .timeline {
            display: grid;
            gap: 0;
            margin-top: 14px;
        }

        .timeline-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
        }

        .timeline-content {
            padding-bottom: 12px;
            border-bottom: 1px solid var(--line-soft);
        }

        .timeline-item+.timeline-item .timeline-content {
            padding-top: 12px;
        }

        .timeline-item:last-child .timeline-content {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .timeline-content strong {
            display: block;
            color: #142019;
        }

        .timeline-content span {
            display: block;
            margin-top: 3px;
        }

        .timeline-content p {
            margin: 6px 0 0;
        }

        .mobile-help {
            display: none;
        }

        .muted {
            color: #667064;
        }

        @media (max-width: 1040px) {
            .shell--admin {
                grid-template-columns: 246px minmax(0, 1fr);
            }

            .sidebar {
                padding: 20px 16px;
            }

            .content-bar,
            .page {
                width: min(100% - 32px, 1160px);
            }

            .filter-panel .filters {
                grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            }

            .delivery-proof-form--side {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            }

            .delivery-proof-form--side .button {
                justify-self: end;
                width: fit-content;
                grid-column: 1 / -1;
            }

        }

        @media (max-width: 820px) {
            .shell--admin {
                display: block;
            }

            .sidebar {
                position: relative;
                height: auto;
                min-height: 0;
                border-right: 0;
                border-bottom: 1px solid rgba(255, 255, 255, .1);
                box-shadow: 0 12px 34px rgba(20, 32, 25, .12);
            }

            .nav {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .sidebar__footer {
                margin-top: 0;
            }

            .content-bar {
                position: static;
                width: min(100% - 20px, 1160px);
                min-height: auto;
                padding: 18px 0 0;
            }

            .page {
                width: min(100% - 20px, 1160px);
                padding: 18px 0 42px;
            }

            .section-head {
                align-items: stretch;
                flex-direction: column;
            }

            .form-grid,
            .detail-grid,
            .two-column,
            .info-grid,
            .order-detail-hero {
                grid-template-columns: 1fr;
            }

            .order-detail-total {
                justify-items: start;
            }

            .record-card summary {
                grid-template-columns: 1fr;
            }

            .order-history-card__top,
            .order-history-card__body {
                grid-template-columns: 1fr;
            }

            .order-history-total {
                justify-items: start;
            }

            .order-history-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 560px) {
            .shell--guest {
                padding: 14px;
            }

            .sidebar {
                padding: 16px;
            }

            .brand {
                align-items: flex-start;
            }

            .nav {
                grid-template-columns: 1fr;
            }

            .filters {
                grid-template-columns: 1fr;
                align-items: stretch;
            }

            .toolbar {
                display: grid;
                align-items: stretch;
            }

            .button {
                width: 100%;
            }

            .table-wrap .button,
            .actions .button.small {
                width: auto;
            }

            .pagination--clean,
            .order-history-actions,
            .order-history-actions form {
                display: grid;
                width: 100%;
            }

            .delivery-proof-form--side {
                grid-template-columns: 1fr;
                padding: 12px;
            }

            .delivery-proof-form--side .button {
                width: 100%;
            }

            .product-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .product-card {
                grid-template-rows: 150px minmax(0, 1fr);
            }

            .product-card__content {
                gap: 11px;
                padding: 12px;
            }

            .product-card__actions {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .product-card__actions form,
            .product-card__actions .button {
                width: 100%;
            }

            th,
            td {
                padding: 10px;
            }

            .detail-list div {
                grid-template-columns: 1fr;
                gap: 5px;
            }
        }
    </style>
</head>

<body>
    <div class="shell {{ auth()->check() ? 'shell--admin' : 'shell--guest' }}">
        @auth
            @php
                $adminLinks = [
                    ['label' => 'Dashboard', 'match' => 'admin.dashboard', 'href' => route('admin.dashboard')],
                    ['label' => 'Produk', 'match' => 'admin.products.*', 'href' => route('admin.products.index')],
                    [
                        'label' => 'Harga Pet',
                        'match' => 'admin.product-variants.*',
                        'href' => route('admin.product-variants.index'),
                    ],
                    [
                        'label' => 'Rate Token',
                        'match' => 'admin.token-rates.*',
                        'href' => route('admin.token-rates.index'),
                    ],
                    ['label' => 'Mutasi', 'match' => 'admin.mutations.*', 'href' => route('admin.mutations.index')],
                    ['label' => 'Riwayat Pesanan', 'match' => 'admin.orders.*', 'href' => route('admin.orders.index')],
                ];
            @endphp

            <aside class="sidebar" aria-label="Admin sidebar">
                <a href="{{ route('admin.dashboard') }}" class="brand">
                    <span class="brand__mark">A</span>
                    <div>
                        <strong>Allegiaant Admin</strong>
                        <small>Growpet Market</small>
                    </div>
                </a>

                <div class="sidebar__section">
                    <span class="sidebar__eyebrow">Menu Admin</span>
                    <nav class="nav" aria-label="Admin navigation">
                        @foreach ($adminLinks as $item)
                            <a class="{{ request()->routeIs($item['match']) ? 'is-active' : '' }}"
                                href="{{ $item['href'] }}">
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>

                <div class="sidebar__footer">
                    <div class="admin-user">
                        <span class="admin-user__avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
                        <div>
                            <strong>{{ auth()->user()->name ?? 'Admin' }}</strong>
                            <small>{{ auth()->user()->email }}</small>
                        </div>
                    </div>
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button class="logout-button" type="submit">Logout</button>
                    </form>
                </div>
            </aside>
        @endauth

        <div class="content">
            @auth
                <header class="content-bar">
                    <div>
                        <span class="content-bar__eyebrow">Admin Panel</span>
                        <strong>@yield('title', 'Admin')</strong>
                    </div>
                </header>
            @endauth

            <main class="page">
                @php
                    $alertMessage = session('status') ?? session('success') ?? session('warning') ?? session('error');
                    $alertTone = session('error') ? 'error' : (session('warning') ? 'warning' : 'success');
                    $alertTitle = match ($alertTone) {
                        'error' => 'Gagal',
                        'warning' => 'Perhatian',
                        default => 'Berhasil',
                    };
                @endphp

                @if ($alertMessage)
                    <div class="admin-alert admin-alert--{{ $alertTone }}" role="alert" data-admin-alert>
                        <div class="admin-alert__body">
                            <strong class="admin-alert__title">{{ $alertTitle }}</strong>
                            <span class="admin-alert__message">{{ $alertMessage }}</span>
                        </div>
                        <button class="admin-alert__close" type="button" aria-label="Tutup alert" data-admin-alert-close>
                            &times;
                        </button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="errors admin-alert admin-alert--error" role="alert" data-admin-alert>
                        <div class="admin-alert__body">
                            <strong class="admin-alert__title">Validasi gagal.</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button class="admin-alert__close" type="button" aria-label="Tutup alert" data-admin-alert-close>
                            &times;
                        </button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    <script>
        (() => {
            document.documentElement.classList.add('has-js')

            const autoFilterForms = document.querySelectorAll('form[method="GET"], form[method="get"]')
            const adminAlerts = document.querySelectorAll('[data-admin-alert]')

            adminAlerts.forEach((alert) => {
                const close = alert.querySelector('[data-admin-alert-close]')

                close?.addEventListener('click', () => {
                    alert.remove()
                })

                window.setTimeout(() => {
                    alert.remove()
                }, 5200)
            })

            autoFilterForms.forEach((form) => {
                const fields = form.querySelectorAll(
                    'input[type="search"], input[data-auto-submit], select[data-auto-submit], select[name]'
                )

                if (!fields.length) {
                    return
                }

                let timeoutId
                let isComposing = false
                form.classList.add('is-auto-filter')

                const submit = (delay = 0) => {
                    window.clearTimeout(timeoutId)
                    timeoutId = window.setTimeout(() => {
                        if (isComposing) {
                            return
                        }

                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit()
                            return
                        }

                        form.submit()
                    }, delay)
                }

                fields.forEach((field) => {
                    if (field.tagName === 'SELECT') {
                        field.addEventListener('change', () => submit())
                        return
                    }

                    field.addEventListener('compositionstart', () => {
                        isComposing = true
                    })

                    field.addEventListener('compositionend', () => {
                        isComposing = false
                        submit(360)
                    })

                    field.addEventListener('input', () => submit(420))
                })
            })
        })()
    </script>
</body>

</html>
