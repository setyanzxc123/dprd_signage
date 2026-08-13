(() => {
    const stored = localStorage.getItem('dprd-admin-theme');
    const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
    const theme = stored === 'dark' || stored === 'light'
        ? stored
        : (prefersDark ? 'dark' : 'light');

    document.documentElement.classList.toggle('dark', theme === 'dark');
    document.documentElement.setAttribute('data-theme', theme);

})();
