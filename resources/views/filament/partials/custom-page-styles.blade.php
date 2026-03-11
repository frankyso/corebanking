<style>
    /* Custom page Tailwind utilities - supplements Filament's CSS */

    /* Layout */
    .grid { display: grid; }
    .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .flex { display: flex; }
    .flex-col { flex-direction: column; }
    .flex-wrap { flex-wrap: wrap; }
    .block { display: block; }
    .items-center { align-items: center; }
    .items-end { align-items: flex-end; }
    .justify-between { justify-content: space-between; }
    .shrink-0 { flex-shrink: 0; }

    /* Gap */
    .gap-1 { gap: 0.25rem; }
    .gap-2 { gap: 0.5rem; }
    .gap-3 { gap: 0.75rem; }
    .gap-4 { gap: 1rem; }
    .gap-6 { gap: 1.5rem; }

    /* Space */
    .space-y-6 > * + * { margin-top: 1.5rem; }

    /* Typography */
    .text-xs { font-size: 0.75rem; line-height: 1rem; }
    .text-sm { font-size: 0.875rem; line-height: 1.25rem; }
    .text-lg { font-size: 1.125rem; line-height: 1.75rem; }
    .text-xl { font-size: 1.25rem; line-height: 1.75rem; }
    .text-2xl { font-size: 1.5rem; line-height: 2rem; }
    .font-medium { font-weight: 500; }
    .font-semibold { font-weight: 600; }
    .font-bold { font-weight: 700; }
    .font-mono { font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, 'Liberation Mono', monospace; }
    .uppercase { text-transform: uppercase; }
    .tracking-wider { letter-spacing: 0.05em; }
    .tabular-nums { font-variant-numeric: tabular-nums; }
    .text-left { text-align: left; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    /* Sizing */
    .w-full { width: 100%; }
    .w-fit { width: fit-content; }
    .w-12 { width: 3rem; }
    .w-36 { width: 9rem; }
    .w-44 { width: 11rem; }
    .w-56 { width: 14rem; }
    .h-2\.5 { height: 0.625rem; }
    .h-fit { height: fit-content; }
    .max-w-xs { max-width: 20rem; }

    /* Padding */
    .p-2 { padding: 0.5rem; }
    .p-3 { padding: 0.75rem; }
    .px-4 { padding-left: 1rem; padding-right: 1rem; }
    .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
    .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
    .py-2\.5 { padding-top: 0.625rem; padding-bottom: 0.625rem; }
    .py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
    .py-4 { padding-top: 1rem; padding-bottom: 1rem; }
    .py-8 { padding-top: 2rem; padding-bottom: 2rem; }

    /* Margin */
    .mb-1 { margin-bottom: 0.25rem; }
    .mb-1\.5 { margin-bottom: 0.375rem; }
    .mb-4 { margin-bottom: 1rem; }
    .mt-0\.5 { margin-top: 0.125rem; }
    .mt-1 { margin-top: 0.25rem; }
    .mt-4 { margin-top: 1rem; }
    .mx-auto { margin-left: auto; margin-right: auto; }
    .-mx-6 { margin-left: -1.5rem; margin-right: -1.5rem; }
    .-mb-6 { margin-bottom: -1.5rem; }

    /* Background colors */
    .bg-white { background-color: #fff; }
    .bg-gray-50 { background-color: rgb(249 250 251); }
    .bg-gray-100 { background-color: rgb(243 244 246); }
    .bg-gray-200 { background-color: rgb(229 231 235); }
    .bg-primary-50 { background-color: rgb(239 246 255); }
    .bg-success-50 { background-color: rgb(240 253 244); }
    .bg-danger-50 { background-color: rgb(254 242 242); }

    /* Text colors */
    .text-gray-400 { color: rgb(156 163 175); }
    .text-gray-500 { color: rgb(107 114 128); }
    .text-gray-950 { color: rgb(3 7 18); }
    .text-primary-500 { color: rgb(59 130 246); }
    .text-primary-600 { color: rgb(37 99 235); }
    .text-primary-700 { color: rgb(29 78 216); }
    .text-success-500 { color: rgb(34 197 94); }
    .text-success-600 { color: rgb(22 163 74); }
    .text-success-700 { color: rgb(21 128 61); }
    .text-danger-500 { color: rgb(239 68 68); }
    .text-danger-600 { color: rgb(220 38 38); }
    .text-danger-700 { color: rgb(185 28 28); }
    .text-danger-800 { color: rgb(153 27 27); }

    /* Borders */
    .border-t { border-top-width: 1px; }
    .border-gray-300 { border-color: rgb(209 213 219); }
    .border-danger-200 { border-color: rgb(254 202 202); }
    .divide-y > * + * { border-top-width: 1px; border-top-style: solid; }
    .divide-gray-100 > * + * { border-top-color: rgb(243 244 246); }
    .rounded-lg { border-radius: 0.5rem; }
    .rounded-xl { border-radius: 0.75rem; }
    .rounded-full { border-radius: 9999px; }
    .ring-1 { box-shadow: 0 0 0 1px var(--tw-ring-color, rgb(3 7 18 / 0.05)); }
    .ring-gray-950\/5 { --tw-ring-color: rgb(3 7 18 / 0.05); }

    /* Overflow */
    .overflow-x-auto { overflow-x: auto; }
    .overflow-hidden { overflow: hidden; }

    /* Effects */
    .shadow-sm { box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
    .transition { transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms; }
    .duration-75 { transition-duration: 75ms; }
    .cursor-pointer { cursor: pointer; }

    /* Hover */
    .hover\:bg-gray-50:hover { background-color: rgb(249 250 251); }

    /* Focus */
    .focus\:border-primary-500:focus { border-color: rgb(59 130 246); }
    .focus\:ring-1:focus { box-shadow: 0 0 0 1px rgb(59 130 246); }
    .focus\:ring-inset:focus { box-shadow: inset 0 0 0 1px rgb(59 130 246); }
    .focus\:ring-primary-500:focus { --tw-ring-color: rgb(59 130 246); }

    /* Responsive */
    @media (min-width: 640px) {
        .sm\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sm\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .sm\:text-sm { font-size: 0.875rem; line-height: 1.25rem; }
    }
    @media (min-width: 1024px) {
        .lg\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .lg\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }

    /* Dark mode */
    .dark .dark\:bg-white\/5 { background-color: rgb(255 255 255 / 0.05); }
    .dark .dark\:bg-white\/10 { background-color: rgb(255 255 255 / 0.1); }
    .dark .dark\:bg-primary-400\/10 { background-color: rgb(96 165 250 / 0.1); }
    .dark .dark\:bg-success-400\/10 { background-color: rgb(74 222 128 / 0.1); }
    .dark .dark\:bg-danger-400\/10 { background-color: rgb(248 113 113 / 0.1); }
    .dark .dark\:text-white { color: #fff; }
    .dark .dark\:text-gray-400 { color: rgb(156 163 175); }
    .dark .dark\:text-gray-500 { color: rgb(107 114 128); }
    .dark .dark\:text-primary-300 { color: rgb(147 197 253); }
    .dark .dark\:text-primary-400 { color: rgb(96 165 250); }
    .dark .dark\:text-success-300 { color: rgb(134 239 172); }
    .dark .dark\:text-success-400 { color: rgb(74 222 128); }
    .dark .dark\:text-danger-200 { color: rgb(254 202 202); }
    .dark .dark\:text-danger-300 { color: rgb(252 165 165); }
    .dark .dark\:text-danger-400 { color: rgb(248 113 113); }
    .dark .dark\:border-white\/10 { border-color: rgb(255 255 255 / 0.1); }
    .dark .dark\:border-danger-400\/20 { border-color: rgb(248 113 113 / 0.2); }
    .dark .dark\:ring-white\/10 { --tw-ring-color: rgb(255 255 255 / 0.1); }
    .dark .dark\:divide-white\/5 > * + * { border-top-color: rgb(255 255 255 / 0.05); }
    .dark .dark\:hover\:bg-white\/5:hover { background-color: rgb(255 255 255 / 0.05); }
</style>
