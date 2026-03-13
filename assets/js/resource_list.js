(function () {

    // ── Favoris ───────────────────────────────────────────────────────────────
    const favoritesList = document.getElementById('favorites-list');
    if (!favoritesList) return;

    function loadFavorites() {
        fetch('/favorites/list')
            .then(r => r.json())
            .then(favorites => {
                if (favorites.length === 0) {
                    favoritesList.innerHTML = '<p class="text-base-content/50 text-sm">Vous n\'avez pas encore de favoris.</p>';
                    return;
                }
                favoritesList.innerHTML = favorites.map(f => `
                    <div class="card bg-base-100 shadow-sm" id="fav-card-${f.id}">
                        <div class="card-body">
                            <h3 class="card-title text-base">${f.title}</h3>
                            <p class="text-xs text-base-content/50">Ajouté le ${f.createdAt}</p>
                            <div class="card-actions justify-end mt-3 gap-2">
                                <a href="/resources/${f.id}" class="btn btn-primary btn-sm">Consulter</a>
                                <button onclick="removeFavorite(${f.id})" class="btn btn-error btn-outline btn-sm">Retirer</button>
                            </div>
                        </div>
                    </div>
                `).join('');
            });
    }

    window.removeFavorite = function (articleId) {
        fetch(`/favorites/toggle/${articleId}`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.status !== 'removed') return;

            document.getElementById(`fav-card-${articleId}`)?.remove();

            const flash = document.getElementById('flash-favorites');
            flash.innerHTML = '<div role="alert" class="alert alert-success text-sm">Ressource retirée des favoris.</div>';
            setTimeout(() => flash.innerHTML = '', 3000);

            if (favoritesList.querySelectorAll('[id^="fav-card-"]').length === 0) {
                favoritesList.innerHTML = '<p class="text-base-content/50 text-sm">Vous n\'avez pas encore de favoris.</p>';
            }
        });
    };

    loadFavorites();

})();
