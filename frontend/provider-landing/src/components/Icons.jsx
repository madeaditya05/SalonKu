const paths = {
    users: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
    grid: 'M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z',
    shield: 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10M9 12l2 2 4-5',
    spark: 'M12 2l1.6 5.2L19 9l-5.4 1.8L12 16l-1.6-5.2L5 9l5.4-1.8zM19 15l.8 2.6 2.2.8-2.2.8L19 21l-.8-2.6-2.2-.8 2.2-.8z',
    chart: 'M4 19V5M4 19h16M8 16v-5M12 16V8M16 16v-9M20 16v-3',
    headset: 'M4 13a8 8 0 0 1 16 0v4a3 3 0 0 1-3 3h-2v-6h5M4 17v-4h5v6H7a3 3 0 0 1-3-3M13 21h3',
    calendar: 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2',
    file: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M9 15l2 2 4-4',
    store: 'M4 10h16l-1-6H5zM6 10v10h12V10M9 20v-6h6v6M4 10c0 2 4 2 4 0M8 10c0 2 4 2 4 0M12 10c0 2 4 2 4 0M16 10c0 2 4 2 4 0',
    star: 'M12 2l3 6.5 7 .8-5.2 4.8 1.4 6.9L12 17.4 5.8 21l1.4-6.9L2 9.3l7-.8L12 2Z',
    arrow: 'M5 12h14M13 5l7 7-7 7',
    close: 'M18 6L6 18M6 6l12 12',
    check: 'M20 6L9 17l-5-5',
};

export function Icon({ name, className = '', size = 24 }) {
    return (
        <svg className={className} width={size} height={size} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path
                d={paths[name] || paths.check}
                stroke="currentColor"
                strokeWidth="1.8"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
