(function () {
    console.log("[Favoris] *** SCRIPT EXÉCUTÉ ***");
    // ── Bouton Favori ─────────────────────────────────────────────────────────
    const btn = document.getElementById("btn-favorite");

    if (btn) {
        const articleId = btn.dataset.articleId;
        const label = document.getElementById("btn-favorite-label");

        const svg = btn.querySelector("svg");

        function updateFavoriteUI(isFav) {
            label.textContent = isFav
                ? "Retirer des favoris"
                : "Mettre en favoris";
            btn.setAttribute("aria-pressed", isFav ? "true" : "false");
            btn.setAttribute(
                "aria-label",
                isFav
                    ? "Retirer des favoris"
                    : "Ajouter cet article aux favoris",
            );
            btn.classList.toggle("btn-outline", !isFav);
            if (svg) svg.setAttribute("fill", isFav ? "currentColor" : "none");
        }

        console.log("[Favoris] Init — articleId:", articleId);

        fetch("/favorites/list")
            .then((r) => {
                console.log(
                    "[Favoris] /favorites/list — HTTP status:",
                    r.status,
                );
                return r.json();
            })
            .then((favorites) => {
                console.log("[Favoris] /favorites/list — réponse:", favorites);
                const isFav = favorites.some((f) => f.id == articleId);
                console.log("[Favoris] État initial isFav:", isFav);
                updateFavoriteUI(isFav);
            });

        if (btn.dataset.listenerAttached) {
            console.warn(
                "[Favoris] Listener déjà attaché, on skip — cause probable : Turbo Drive a re-exécuté le script.",
            );
        } else {
            btn.dataset.listenerAttached = "true";
            console.log("[Favoris] Ajout du listener click");
            btn.addEventListener("click", function () {
                console.log("[Favoris] Clic sur le bouton");
                fetch(`/favorites/toggle/${articleId}`, {
                    method: "POST",
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                })
                    .then((r) => {
                        console.log(
                            "[Favoris] /favorites/toggle — HTTP status:",
                            r.status,
                        );
                        return r.json();
                    })
                    .then((data) => {
                        console.log(
                            "[Favoris] /favorites/toggle — réponse:",
                            data,
                        );
                        const added = data.status === "added";
                        console.log("[Favoris] Nouvel état added:", added);
                        updateFavoriteUI(added);
                    })
                    .catch((err) =>
                        console.error("[Favoris] Erreur toggle:", err),
                    );
            });
        }

        // Détecte si Turbo rechargement la page après le clic
        document.addEventListener("turbo:load", () =>
            console.warn(
                "[Favoris] turbo:load déclenché — la page a été rechargée par Turbo !",
            ),
        );
        document.addEventListener("turbo:render", () =>
            console.warn(
                "[Favoris] turbo:render déclenché — Turbo a re-rendu la page !",
            ),
        );
    }

    // ── Progression de lecture ────────────────────────────────────────────────
    const widget = document.getElementById("reading-progress-widget");

    if (widget) {
        const PROGRESS_ID = widget.dataset.progressId || null; // null si non connecté
        let lastSavedPct = parseInt(widget.dataset.readPercentage, 10) || 0;
        let saveTimer = null;

        const radial = document.getElementById("radial-progress");

        function updateRadial(pct) {
            pct = Math.min(100, Math.max(0, Math.round(pct)));
            radial.style.setProperty("--value", pct);
            radial.setAttribute("aria-valuenow", pct);
            radial.setAttribute(
                "aria-label",
                `Progression de lecture : ${pct}%`,
            );
            radial.textContent = pct + "%";
        }

        function getScrollPct() {
            const scrollable =
                document.documentElement.scrollHeight - window.innerHeight;
            if (scrollable <= 50) return null;
            return Math.min(
                100,
                Math.round((window.scrollY / scrollable) * 100),
            );
        }

        async function saveProgress(pct) {
            if (!PROGRESS_ID) return; // pas connecté, pas de sauvegarde
            if (Math.abs(pct - lastSavedPct) < 5) return; // seuil 5%
            try {
                const res = await fetch("/api/progress/" + PROGRESS_ID, {
                    method: "PATCH",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ readPercentage: pct }),
                });
                if (res.ok) {
                    lastSavedPct = pct;
                }
            } catch (e) {
                console.error("Erreur sauvegarde progression:", e);
            }
        }

        function scheduleSave(pct) {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(() => saveProgress(pct), 3000); // debounce 3s
        }

        window.addEventListener(
            "scroll",
            function () {
                const pct = getScrollPct();
                if (pct === null) return;
                updateRadial(pct);
                scheduleSave(pct);
            },
            { passive: true },
        );

        // Suivi temporel pour les articles courts (pas de scroll)
        const scrollable =
            document.documentElement.scrollHeight - window.innerHeight;
        if (scrollable <= 50) {
            const WORDS = document
                .getElementById("article-content")
                .innerText.trim()
                .split(/\s+/).length;
            const READING_TIME_MS = Math.max(
                10000,
                Math.round((WORDS / 200) * 60 * 1000),
            );
            let elapsed = Math.round((lastSavedPct / 100) * READING_TIME_MS);
            let lastTick = Date.now();

            const timer = setInterval(function () {
                if (!document.hidden) {
                    elapsed += Date.now() - lastTick;
                }
                lastTick = Date.now();
                const pct = Math.min(
                    100,
                    Math.round((elapsed / READING_TIME_MS) * 100),
                );
                updateRadial(pct);
                scheduleSave(pct);
                if (pct >= 100) clearInterval(timer);
            }, 1000);
        }

        updateRadial(Math.max(lastSavedPct, getScrollPct() ?? 0));
    }
})();
