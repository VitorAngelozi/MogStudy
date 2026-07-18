const pad = (value) => String(value).padStart(2, '0');

const formatElapsed = (totalSeconds) => {
    const safeSeconds = Math.max(0, Math.floor(totalSeconds));
    const hours = Math.floor(safeSeconds / 3600);
    const minutes = Math.floor((safeSeconds % 3600) / 60);
    const seconds = safeSeconds % 60;

    return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
};

const timers = document.querySelectorAll('[data-study-timer]');

timers.forEach((widget) => {
    const valueNode = widget.querySelector('[data-timer-value]');
    const pauseButton = widget.querySelector('[data-timer-pause]');
    const startedAtRaw = widget.dataset.startedAt;
    const elapsedSeconds = Number(widget.dataset.elapsedSeconds || 0);

    if (!valueNode) {
        return;
    }

    if (!startedAtRaw || startedAtRaw === 'null') {
        valueNode.textContent = valueNode.textContent || formatElapsed(elapsedSeconds || 5143);
        if (pauseButton) {
            pauseButton.disabled = true;
        }
        return;
    }

    let baseTime = new Date(startedAtRaw).getTime();
    let pausedAt = null;
    let paused = false;
    let intervalId = null;

    const render = () => {
        const currentBase = paused && pausedAt ? pausedAt : Date.now();
        const elapsed = (currentBase - baseTime) / 1000;
        valueNode.textContent = formatElapsed(elapsed);
    };

    const start = () => {
        render();
        intervalId = window.setInterval(render, 1000);
    };

    const stop = () => {
        if (intervalId) {
            window.clearInterval(intervalId);
            intervalId = null;
        }
    };

    if (pauseButton) {
        pauseButton.addEventListener('click', () => {
            paused = !paused;

            if (paused) {
                pausedAt = Date.now();
                pauseButton.textContent = 'Retomar';
                widget.classList.add('is-paused');
            } else {
                if (pausedAt) {
                    baseTime += Date.now() - pausedAt;
                }
                pausedAt = null;
                pauseButton.textContent = 'Pausar';
                widget.classList.remove('is-paused');
            }

            render();
        });
    }

    const observer = new MutationObserver(() => {
        if (widget.dataset.state !== 'running') {
            stop();
        }
    });

    observer.observe(widget, { attributes: true, attributeFilter: ['data-state'] });

    start();
});
