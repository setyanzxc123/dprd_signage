(() => {
    const stored = localStorage.getItem('dprd-admin-theme');
    const theme = stored === 'dark' || stored === 'light'
        ? stored
        : 'light';

    document.documentElement.classList.toggle('dark', theme === 'dark');
    document.documentElement.setAttribute('data-theme', theme);
})();
