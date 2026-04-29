document.addEventListener('DOMContentLoaded', function () {
    var open = document.getElementById('openHeroQuickBrief');
    var modal = document.getElementById('heroQuickBriefModal');
    var close = document.getElementById('heroModalClose');

    function showModal() {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.documentElement.classList.add('overflow-hidden');
    }

    function hideModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.documentElement.classList.remove('overflow-hidden');
    }

    if (open) open.addEventListener('click', function (e) { e.preventDefault(); showModal(); });
    if (close) close.addEventListener('click', function (e) { e.preventDefault(); hideModal(); });

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) hideModal();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') hideModal();
    });
});
