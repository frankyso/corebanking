<style>
    /* Custom page Tailwind utilities - supplements Filament's CSS */

    /* Layout */
    .grid { display: grid; }
    .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .flex { display: flex; }
    .flex-col { flex-direction: column; }
    .flex-wrap { flex-wrap: wrap; }
    .flex-1 { flex: 1 1 0%; }
    .block { display: block; }
    .inline-flex { display: inline-flex; }
    .items-center { align-items: center; }
    .items-start { align-items: flex-start; }
    .items-end { align-items: flex-end; }
    .justify-between { justify-content: space-between; }
    .justify-center { justify-content: center; }
    .shrink-0 { flex-shrink: 0; }
    .relative { position: relative; }
    .absolute { position: absolute; }
    .inset-0 { inset: 0; }
    .top-0 { top: 0; }
    .left-0 { left: 0; }

    /* Gap */
    .gap-1 { gap: 0.25rem; }
    .gap-1\.5 { gap: 0.375rem; }
    .gap-2 { gap: 0.5rem; }
    .gap-3 { gap: 0.75rem; }
    .gap-4 { gap: 1rem; }
    .gap-5 { gap: 1.25rem; }
    .gap-6 { gap: 1.5rem; }
    .gap-8 { gap: 2rem; }

    /* Space */
    .space-y-1 > * + * { margin-top: 0.25rem; }
    .space-y-2 > * + * { margin-top: 0.5rem; }
    .space-y-3 > * + * { margin-top: 0.75rem; }
    .space-y-4 > * + * { margin-top: 1rem; }
    .space-y-6 > * + * { margin-top: 1.5rem; }
    .space-x-2 > * + :not([hidden]) { margin-left: 0.5rem; }

    /* Typography */
    .text-\[10px\] { font-size: 10px; line-height: 14px; }
    .text-xs { font-size: 0.75rem; line-height: 1rem; }
    .text-sm { font-size: 0.875rem; line-height: 1.25rem; }
    .text-base { font-size: 1rem; line-height: 1.5rem; }
    .text-lg { font-size: 1.125rem; line-height: 1.75rem; }
    .text-xl { font-size: 1.25rem; line-height: 1.75rem; }
    .text-2xl { font-size: 1.5rem; line-height: 2rem; }
    .text-3xl { font-size: 1.875rem; line-height: 2.25rem; }
    .font-normal { font-weight: 400; }
    .font-medium { font-weight: 500; }
    .font-semibold { font-weight: 600; }
    .font-bold { font-weight: 700; }
    .font-extrabold { font-weight: 800; }
    .font-mono { font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, 'Liberation Mono', monospace; }
    .uppercase { text-transform: uppercase; }
    .lowercase { text-transform: lowercase; }
    .tracking-wider { letter-spacing: 0.05em; }
    .tracking-wide { letter-spacing: 0.025em; }
    .tabular-nums { font-variant-numeric: tabular-nums; }
    .text-left { text-align: left; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .whitespace-nowrap { white-space: nowrap; }
    .leading-none { line-height: 1; }
    .leading-tight { line-height: 1.25; }
    .leading-snug { line-height: 1.375; }
    .leading-relaxed { line-height: 1.625; }

    /* Sizing */
    .w-full { width: 100%; }
    .w-fit { width: fit-content; }
    .w-8 { width: 2rem; }
    .w-10 { width: 2.5rem; }
    .w-12 { width: 3rem; }
    .w-16 { width: 4rem; }
    .w-20 { width: 5rem; }
    .w-24 { width: 6rem; }
    .w-36 { width: 9rem; }
    .w-44 { width: 11rem; }
    .w-56 { width: 14rem; }
    .h-1 { height: 0.25rem; }
    .h-1\.5 { height: 0.375rem; }
    .h-2 { height: 0.5rem; }
    .h-2\.5 { height: 0.625rem; }
    .h-3 { height: 0.75rem; }
    .h-4 { height: 1rem; }
    .h-5 { height: 1.25rem; }
    .h-6 { height: 1.5rem; }
    .h-8 { height: 2rem; }
    .h-10 { height: 2.5rem; }
    .h-12 { height: 3rem; }
    .h-full { height: 100%; }
    .h-fit { height: fit-content; }
    .min-w-0 { min-width: 0; }
    .min-h-\[2rem\] { min-height: 2rem; }
    .max-w-xs { max-width: 20rem; }
    .max-w-sm { max-width: 24rem; }
    .max-w-md { max-width: 28rem; }
    .max-w-\[120px\] { max-width: 120px; }

    /* Padding */
    .p-1 { padding: 0.25rem; }
    .p-1\.5 { padding: 0.375rem; }
    .p-2 { padding: 0.5rem; }
    .p-2\.5 { padding: 0.625rem; }
    .p-3 { padding: 0.75rem; }
    .p-4 { padding: 1rem; }
    .p-5 { padding: 1.25rem; }
    .p-6 { padding: 1.5rem; }
    .px-2 { padding-left: 0.5rem; padding-right: 0.5rem; }
    .px-2\.5 { padding-left: 0.625rem; padding-right: 0.625rem; }
    .px-3 { padding-left: 0.75rem; padding-right: 0.75rem; }
    .px-4 { padding-left: 1rem; padding-right: 1rem; }
    .px-5 { padding-left: 1.25rem; padding-right: 1.25rem; }
    .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
    .py-1 { padding-top: 0.25rem; padding-bottom: 0.25rem; }
    .py-1\.5 { padding-top: 0.375rem; padding-bottom: 0.375rem; }
    .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
    .py-2\.5 { padding-top: 0.625rem; padding-bottom: 0.625rem; }
    .py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
    .py-4 { padding-top: 1rem; padding-bottom: 1rem; }
    .py-5 { padding-top: 1.25rem; padding-bottom: 1.25rem; }
    .py-6 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
    .py-8 { padding-top: 2rem; padding-bottom: 2rem; }
    .pt-2 { padding-top: 0.5rem; }
    .pt-3 { padding-top: 0.75rem; }
    .pt-4 { padding-top: 1rem; }
    .pb-1 { padding-bottom: 0.25rem; }

    /* Margin */
    .mb-0\.5 { margin-bottom: 0.125rem; }
    .mb-1 { margin-bottom: 0.25rem; }
    .mb-1\.5 { margin-bottom: 0.375rem; }
    .mb-2 { margin-bottom: 0.5rem; }
    .mb-3 { margin-bottom: 0.75rem; }
    .mb-4 { margin-bottom: 1rem; }
    .mt-0\.5 { margin-top: 0.125rem; }
    .mt-1 { margin-top: 0.25rem; }
    .mt-2 { margin-top: 0.5rem; }
    .mt-3 { margin-top: 0.75rem; }
    .mt-4 { margin-top: 1rem; }
    .ml-1 { margin-left: 0.25rem; }
    .ml-2 { margin-left: 0.5rem; }
    .ml-auto { margin-left: auto; }
    .mr-1 { margin-right: 0.25rem; }
    .mr-2 { margin-right: 0.5rem; }
    .mx-auto { margin-left: auto; margin-right: auto; }
    .-mx-6 { margin-left: -1.5rem; margin-right: -1.5rem; }
    .-mb-6 { margin-bottom: -1.5rem; }
    .-mt-1 { margin-top: -0.25rem; }

    /* Background colors */
    .bg-white { background-color: #fff; }
    .bg-gray-50 { background-color: rgb(249 250 251); }
    .bg-gray-100 { background-color: rgb(243 244 246); }
    .bg-gray-200 { background-color: rgb(229 231 235); }
    .bg-gray-300 { background-color: rgb(209 213 219); }
    .bg-primary-50 { background-color: rgb(239 246 255); }
    .bg-primary-100 { background-color: rgb(219 234 254); }
    .bg-primary-500 { background-color: rgb(59 130 246); }
    .bg-primary-600 { background-color: rgb(37 99 235); }
    .bg-success-50 { background-color: rgb(240 253 244); }
    .bg-success-100 { background-color: rgb(220 252 231); }
    .bg-success-500 { background-color: rgb(34 197 94); }
    .bg-success-600 { background-color: rgb(22 163 74); }
    .bg-warning-50 { background-color: rgb(255 251 235); }
    .bg-warning-100 { background-color: rgb(254 243 199); }
    .bg-warning-500 { background-color: rgb(234 179 8); }
    .bg-danger-50 { background-color: rgb(254 242 242); }
    .bg-danger-100 { background-color: rgb(254 226 226); }
    .bg-danger-500 { background-color: rgb(239 68 68); }
    .bg-info-50 { background-color: rgb(239 246 255); }
    .bg-info-500 { background-color: rgb(59 130 246); }
    .bg-transparent { background-color: transparent; }

    /* Text colors */
    .text-white { color: #fff; }
    .text-gray-300 { color: rgb(209 213 219); }
    .text-gray-400 { color: rgb(156 163 175); }
    .text-gray-500 { color: rgb(107 114 128); }
    .text-gray-600 { color: rgb(75 85 99); }
    .text-gray-700 { color: rgb(55 65 81); }
    .text-gray-800 { color: rgb(31 41 55); }
    .text-gray-950 { color: rgb(3 7 18); }
    .text-primary-500 { color: rgb(59 130 246); }
    .text-primary-600 { color: rgb(37 99 235); }
    .text-primary-700 { color: rgb(29 78 216); }
    .text-success-500 { color: rgb(34 197 94); }
    .text-success-600 { color: rgb(22 163 74); }
    .text-success-700 { color: rgb(21 128 61); }
    .text-warning-500 { color: rgb(234 179 8); }
    .text-warning-600 { color: rgb(202 138 4); }
    .text-warning-700 { color: rgb(161 98 7); }
    .text-danger-500 { color: rgb(239 68 68); }
    .text-danger-600 { color: rgb(220 38 38); }
    .text-danger-700 { color: rgb(185 28 28); }
    .text-danger-800 { color: rgb(153 27 27); }
    .text-info-600 { color: rgb(37 99 235); }

    /* Opacity */
    .opacity-0 { opacity: 0; }
    .opacity-50 { opacity: 0.5; }
    .opacity-60 { opacity: 0.6; }
    .opacity-75 { opacity: 0.75; }

    /* Borders */
    .border { border-width: 1px; }
    .border-t { border-top-width: 1px; }
    .border-b { border-bottom-width: 1px; }
    .border-l { border-left-width: 1px; }
    .border-l-2 { border-left-width: 2px; }
    .border-l-4 { border-left-width: 4px; }
    .border-transparent { border-color: transparent; }
    .border-gray-100 { border-color: rgb(243 244 246); }
    .border-gray-200 { border-color: rgb(229 231 235); }
    .border-gray-300 { border-color: rgb(209 213 219); }
    .border-primary-200 { border-color: rgb(191 219 254); }
    .border-primary-300 { border-color: rgb(147 197 253); }
    .border-primary-500 { border-color: rgb(59 130 246); }
    .border-success-200 { border-color: rgb(187 247 208); }
    .border-success-300 { border-color: rgb(134 239 172); }
    .border-success-500 { border-color: rgb(34 197 94); }
    .border-warning-200 { border-color: rgb(253 230 138); }
    .border-warning-300 { border-color: rgb(252 211 77); }
    .border-danger-200 { border-color: rgb(254 202 202); }
    .border-danger-300 { border-color: rgb(252 165 165); }
    .border-danger-500 { border-color: rgb(239 68 68); }
    .divide-y > * + * { border-top-width: 1px; border-top-style: solid; }
    .divide-gray-100 > * + * { border-top-color: rgb(243 244 246); }
    .divide-gray-200 > * + * { border-top-color: rgb(229 231 235); }
    .rounded { border-radius: 0.25rem; }
    .rounded-md { border-radius: 0.375rem; }
    .rounded-lg { border-radius: 0.5rem; }
    .rounded-xl { border-radius: 0.75rem; }
    .rounded-2xl { border-radius: 1rem; }
    .rounded-full { border-radius: 9999px; }
    .ring-1 { box-shadow: 0 0 0 1px var(--tw-ring-color, rgb(3 7 18 / 0.05)); }
    .ring-2 { box-shadow: 0 0 0 2px var(--tw-ring-color, rgb(3 7 18 / 0.05)); }
    .ring-gray-950\/5 { --tw-ring-color: rgb(3 7 18 / 0.05); }
    .ring-success-300 { --tw-ring-color: rgb(134 239 172); }
    .ring-danger-300 { --tw-ring-color: rgb(252 165 165); }

    /* Overflow */
    .overflow-x-auto { overflow-x: auto; }
    .overflow-hidden { overflow: hidden; }
    .overflow-y-auto { overflow-y: auto; }

    /* Effects */
    .shadow-sm { box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
    .shadow { box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1); }
    .transition { transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms; }
    .transition-all { transition-property: all; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms; }
    .duration-75 { transition-duration: 75ms; }
    .duration-300 { transition-duration: 300ms; }
    .duration-500 { transition-duration: 500ms; }
    .cursor-pointer { cursor: pointer; }

    /* Transform */
    .scale-100 { transform: scale(1); }

    /* Z-index */
    .z-10 { z-index: 10; }

    /* Hover */
    .hover\:bg-gray-50:hover { background-color: rgb(249 250 251); }
    .hover\:bg-gray-100:hover { background-color: rgb(243 244 246); }

    /* Focus */
    .focus\:border-primary-500:focus { border-color: rgb(59 130 246); }
    .focus\:ring-1:focus { box-shadow: 0 0 0 1px rgb(59 130 246); }
    .focus\:ring-inset:focus { box-shadow: inset 0 0 0 1px rgb(59 130 246); }
    .focus\:ring-primary-500:focus { --tw-ring-color: rgb(59 130 246); }

    /* Responsive */
    @media (min-width: 640px) {
        .sm\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sm\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .sm\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .sm\:text-sm { font-size: 0.875rem; line-height: 1.25rem; }
        .sm\:flex-row { flex-direction: row; }
    }
    @media (min-width: 768px) {
        .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .md\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .md\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .md\:grid-cols-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    }
    @media (min-width: 1024px) {
        .lg\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .lg\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .lg\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .lg\:grid-cols-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    }

    /* Dark mode */
    .dark .dark\:bg-white\/5 { background-color: rgb(255 255 255 / 0.05); }
    .dark .dark\:bg-white\/10 { background-color: rgb(255 255 255 / 0.1); }
    .dark .dark\:bg-gray-800 { background-color: rgb(31 41 55); }
    .dark .dark\:bg-gray-900 { background-color: rgb(17 24 39); }
    .dark .dark\:bg-primary-400\/10 { background-color: rgb(96 165 250 / 0.1); }
    .dark .dark\:bg-primary-500\/20 { background-color: rgb(59 130 246 / 0.2); }
    .dark .dark\:bg-success-400\/10 { background-color: rgb(74 222 128 / 0.1); }
    .dark .dark\:bg-success-500\/20 { background-color: rgb(34 197 94 / 0.2); }
    .dark .dark\:bg-warning-400\/10 { background-color: rgb(250 204 21 / 0.1); }
    .dark .dark\:bg-warning-500\/20 { background-color: rgb(234 179 8 / 0.2); }
    .dark .dark\:bg-danger-400\/10 { background-color: rgb(248 113 113 / 0.1); }
    .dark .dark\:bg-danger-500\/20 { background-color: rgb(239 68 68 / 0.2); }
    .dark .dark\:bg-transparent { background-color: transparent; }
    .dark .dark\:text-white { color: #fff; }
    .dark .dark\:text-gray-300 { color: rgb(209 213 219); }
    .dark .dark\:text-gray-400 { color: rgb(156 163 175); }
    .dark .dark\:text-gray-500 { color: rgb(107 114 128); }
    .dark .dark\:text-primary-300 { color: rgb(147 197 253); }
    .dark .dark\:text-primary-400 { color: rgb(96 165 250); }
    .dark .dark\:text-success-300 { color: rgb(134 239 172); }
    .dark .dark\:text-success-400 { color: rgb(74 222 128); }
    .dark .dark\:text-warning-300 { color: rgb(252 211 77); }
    .dark .dark\:text-warning-400 { color: rgb(250 204 21); }
    .dark .dark\:text-danger-200 { color: rgb(254 202 202); }
    .dark .dark\:text-danger-300 { color: rgb(252 165 165); }
    .dark .dark\:text-danger-400 { color: rgb(248 113 113); }
    .dark .dark\:border-white\/5 { border-color: rgb(255 255 255 / 0.05); }
    .dark .dark\:border-white\/10 { border-color: rgb(255 255 255 / 0.1); }
    .dark .dark\:border-gray-700 { border-color: rgb(55 65 81); }
    .dark .dark\:border-primary-400\/20 { border-color: rgb(96 165 250 / 0.2); }
    .dark .dark\:border-primary-500\/30 { border-color: rgb(59 130 246 / 0.3); }
    .dark .dark\:border-success-400\/20 { border-color: rgb(74 222 128 / 0.2); }
    .dark .dark\:border-success-500\/30 { border-color: rgb(34 197 94 / 0.3); }
    .dark .dark\:border-warning-400\/20 { border-color: rgb(250 204 21 / 0.2); }
    .dark .dark\:border-danger-400\/20 { border-color: rgb(248 113 113 / 0.2); }
    .dark .dark\:border-danger-500\/30 { border-color: rgb(239 68 68 / 0.3); }
    .dark .dark\:ring-white\/10 { --tw-ring-color: rgb(255 255 255 / 0.1); }
    .dark .dark\:ring-success-400\/30 { --tw-ring-color: rgb(74 222 128 / 0.3); }
    .dark .dark\:ring-danger-400\/30 { --tw-ring-color: rgb(248 113 113 / 0.3); }
    .dark .dark\:divide-white\/5 > * + * { border-top-color: rgb(255 255 255 / 0.05); }
    .dark .dark\:divide-white\/10 > * + * { border-top-color: rgb(255 255 255 / 0.1); }
    .dark .dark\:hover\:bg-white\/5:hover { background-color: rgb(255 255 255 / 0.05); }
    .dark .dark\:hover\:bg-white\/10:hover { background-color: rgb(255 255 255 / 0.1); }

    /* ===== CUSTOM COMPONENTS ===== */

    /* Stat Card */
    .stat-card {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.25rem;
    }
    .stat-card-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.75rem;
        flex-shrink: 0;
    }
    .stat-card-content {
        flex: 1;
        min-width: 0;
    }
    .stat-card-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: rgb(107 114 128);
        margin-bottom: 0.25rem;
    }
    .stat-card-value {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.25;
        font-variant-numeric: tabular-nums;
    }
    .stat-card-meta {
        font-size: 0.6875rem;
        color: rgb(107 114 128);
        margin-top: 0.25rem;
    }
    .dark .stat-card-label { color: rgb(156 163 175); }
    .dark .stat-card-meta { color: rgb(156 163 175); }

    /* Progress Bar */
    .progress-bar-track {
        width: 100%;
        height: 0.5rem;
        background-color: rgb(229 231 235);
        border-radius: 9999px;
        overflow: hidden;
    }
    .progress-bar-fill {
        height: 100%;
        border-radius: 9999px;
        transition: width 500ms cubic-bezier(0.4, 0, 0.2, 1);
    }
    .dark .progress-bar-track {
        background-color: rgb(255 255 255 / 0.1);
    }

    /* Inline Percentage Bar */
    .pct-bar {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .pct-bar-track {
        flex: 1;
        height: 0.375rem;
        background-color: rgb(229 231 235);
        border-radius: 9999px;
        overflow: hidden;
        min-width: 3rem;
    }
    .pct-bar-fill {
        height: 100%;
        border-radius: 9999px;
        transition: width 300ms ease;
    }
    .pct-bar-label {
        font-size: 0.75rem;
        font-variant-numeric: tabular-nums;
        color: rgb(107 114 128);
        white-space: nowrap;
        min-width: 2.5rem;
        text-align: right;
    }
    .dark .pct-bar-track { background-color: rgb(255 255 255 / 0.1); }
    .dark .pct-bar-label { color: rgb(156 163 175); }

    /* Composition Bar (horizontal stacked) */
    .composition-bar {
        display: flex;
        width: 100%;
        height: 0.75rem;
        border-radius: 9999px;
        overflow: hidden;
        background-color: rgb(243 244 246);
    }
    .composition-bar-segment {
        height: 100%;
        transition: width 500ms ease;
    }
    .dark .composition-bar { background-color: rgb(255 255 255 / 0.05); }

    /* Status Dot */
    .status-dot {
        width: 0.5rem;
        height: 0.5rem;
        border-radius: 9999px;
        flex-shrink: 0;
    }

    /* Step Timeline */
    .step-timeline {
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    .step-timeline-item {
        display: flex;
        gap: 0.75rem;
        position: relative;
        padding-bottom: 1.25rem;
    }
    .step-timeline-item:last-child {
        padding-bottom: 0;
    }
    .step-timeline-line {
        position: absolute;
        left: 0.9375rem;
        top: 2rem;
        bottom: 0;
        width: 2px;
        background-color: rgb(229 231 235);
    }
    .step-timeline-item:last-child .step-timeline-line {
        display: none;
    }
    .step-timeline-dot {
        width: 1.875rem;
        height: 1.875rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        z-index: 10;
        border: 2px solid transparent;
    }
    .step-timeline-content {
        flex: 1;
        min-width: 0;
        padding-top: 0.25rem;
    }
    .dark .step-timeline-line { background-color: rgb(255 255 255 / 0.1); }

    /* Verification Banner */
    .verification-banner {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
    }
    .verification-banner-balanced {
        background-color: rgb(240 253 244);
        color: rgb(21 128 61);
        border: 1px solid rgb(187 247 208);
    }
    .verification-banner-unbalanced {
        background-color: rgb(254 242 242);
        color: rgb(185 28 28);
        border: 1px solid rgb(254 202 202);
    }
    .dark .verification-banner-balanced {
        background-color: rgb(34 197 94 / 0.1);
        color: rgb(134 239 172);
        border-color: rgb(34 197 94 / 0.2);
    }
    .dark .verification-banner-unbalanced {
        background-color: rgb(239 68 68 / 0.1);
        color: rgb(252 165 165);
        border-color: rgb(239 68 68 / 0.2);
    }

    /* Table enhancements */
    .table-row-highlight {
        border-left: 3px solid transparent;
        transition: border-color 150ms ease, background-color 150ms ease;
    }
    .table-row-highlight:hover {
        border-left-color: rgb(59 130 246);
    }

    /* Animated pulse for live indicators */
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }
    .animate-pulse-dot {
        animation: pulse-dot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* Gradient text utility */
    .text-gradient-primary {
        background: linear-gradient(135deg, rgb(59 130 246), rgb(99 102 241));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* ===== FORM INPUTS ===== */
    .fi-custom-input {
        display: block;
        width: 100%;
        height: 2.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: rgb(3 7 18);
        background-color: #fff;
        border: 1px solid rgb(209 213 219);
        border-radius: 0.5rem;
        box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        transition: border-color 75ms, box-shadow 75ms;
        outline: none;
        -webkit-appearance: none;
        appearance: none;
    }
    .fi-custom-input:focus {
        border-color: rgb(59 130 246);
        box-shadow: 0 0 0 1px rgb(59 130 246) inset;
    }
    .fi-custom-input::-webkit-calendar-picker-indicator {
        cursor: pointer;
        opacity: 0.6;
    }
    .fi-custom-input::-webkit-calendar-picker-indicator:hover {
        opacity: 1;
    }
    .dark .fi-custom-input {
        color: #fff;
        background-color: rgb(255 255 255 / 0.05);
        border-color: rgb(255 255 255 / 0.1);
    }
    .dark .fi-custom-input:focus {
        border-color: rgb(96 165 250);
        box-shadow: 0 0 0 1px rgb(96 165 250) inset;
    }
    .dark .fi-custom-input::-webkit-calendar-picker-indicator {
        filter: invert(1);
    }

    .fi-custom-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: rgb(3 7 18);
        margin-bottom: 0.375rem;
    }
    .dark .fi-custom-label {
        color: #fff;
    }

    .fi-custom-field {
        min-width: 0;
    }
</style>
