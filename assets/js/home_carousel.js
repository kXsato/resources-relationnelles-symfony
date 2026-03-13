(function () {
    const track = document.getElementById('carousel-track');
    if (!track) return;

    const SPEED = 0.5; // px par frame
    let paused = false;
    let pos = 0;

    // Duplique les items pour un défilement infini
    track.innerHTML += track.innerHTML;

    const halfWidth = track.scrollWidth / 2;

    function tick() {
        if (!paused) {
            pos += SPEED;
            if (pos >= halfWidth) pos = 0;
            track.style.transform = `translateX(-${pos}px)`;
        }
        requestAnimationFrame(tick);
    }

    // Pause au survol
    track.addEventListener('mouseenter', () => paused = true);
    track.addEventListener('mouseleave', () => paused = false);

    // Pause au focus (accessibilité)
    track.addEventListener('focusin', () => paused = true);
    track.addEventListener('focusout', () => paused = false);

    requestAnimationFrame(tick);
})();
