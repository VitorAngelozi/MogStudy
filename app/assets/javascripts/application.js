const pad = (value) => String(value).padStart(2, '0');

const formatElapsed = (totalSeconds) => {
    const safeSeconds = Math.max(0, Math.floor(totalSeconds));
    const hours = Math.floor(safeSeconds / 3600);
    const minutes = Math.floor((safeSeconds % 3600) / 60);
    const seconds = safeSeconds % 60;

    return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
};

const timers = document.querySelectorAll('[data-study-timer]');
const root = document.documentElement;
const themeToggle = document.querySelector('[data-theme-toggle]');

const applyTheme = (theme) => {
    root.dataset.theme = theme;
    localStorage.setItem('mogstudy-theme', theme);

    if (themeToggle) {
        const isLight = theme === 'light';
        themeToggle.setAttribute('aria-pressed', String(isLight));
        themeToggle.setAttribute('aria-label', isLight ? 'Ativar modo escuro' : 'Ativar modo claro');
    }
};

if (themeToggle) {
    applyTheme(root.dataset.theme || 'dark');

    themeToggle.addEventListener('click', () => {
        applyTheme(root.dataset.theme === 'light' ? 'dark' : 'light');
    });
}

timers.forEach((widget) => {
    const valueNode = widget.querySelector('[data-timer-value]');
    const pauseButton = widget.querySelector('[data-timer-pause]');
    const startedAtRaw = widget.dataset.startedAt;
    const renderedAtRaw = widget.dataset.renderedAt;
    const baseSeconds = Number(widget.dataset.baseSeconds || 0);
    const elapsedSeconds = Number(widget.dataset.elapsedSeconds || 0);
    const state = widget.dataset.state || 'idle';

    if (!valueNode) {
        return;
    }

    if (state === 'paused') {
        valueNode.textContent = formatElapsed(elapsedSeconds);
        widget.classList.add('is-paused');
        return;
    }

    if (state !== 'running' || !startedAtRaw || startedAtRaw === 'null') {
        valueNode.textContent = valueNode.textContent || formatElapsed(elapsedSeconds);
        if (pauseButton) {
            pauseButton.disabled = true;
        }
        return;
    }

    let baseTime = new Date(renderedAtRaw || startedAtRaw).getTime();
    const liveBaseSeconds = renderedAtRaw ? elapsedSeconds : baseSeconds;
    let pausedAt = null;
    let paused = false;
    let intervalId = null;

    const render = () => {
        const currentBase = paused && pausedAt ? pausedAt : Date.now();
        const elapsed = liveBaseSeconds + ((currentBase - baseTime) / 1000);
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

document.querySelectorAll('[data-subject-combobox]').forEach((combobox) => {
    const input = combobox.querySelector('[data-subject-search]');
    const hiddenId = combobox.querySelector('[data-subject-id]');
    const optionsPanel = combobox.querySelector('[data-subject-options]');
    const optionButtons = [...combobox.querySelectorAll('[data-subject-option]')];

    if (!input || !hiddenId || !optionsPanel || optionButtons.length === 0) {
        return;
    }

    const open = () => {
        optionsPanel.hidden = false;
        combobox.classList.add('is-open');
    };

    const close = () => {
        optionsPanel.hidden = true;
        combobox.classList.remove('is-open');
    };

    const normalize = (value) => value.trim().toLocaleLowerCase();

    const syncSelection = () => {
        const selected = optionButtons.find((button) => normalize(button.dataset.subjectName || '') === normalize(input.value));
        hiddenId.value = selected ? selected.dataset.subjectId : '';
    };

    const filterOptions = () => {
        const query = normalize(input.value);
        let visibleCount = 0;

        optionButtons.forEach((button) => {
            const matches = normalize(button.dataset.subjectName || '').includes(query);
            button.hidden = !matches;

            if (matches) {
                visibleCount += 1;
            }
        });

        optionsPanel.hidden = visibleCount === 0;
        combobox.classList.toggle('is-open', visibleCount > 0);
    };

    optionButtons.forEach((button) => {
        button.addEventListener('click', () => {
            input.value = button.dataset.subjectName || '';
            hiddenId.value = button.dataset.subjectId || '';
            close();
            input.focus();
        });
    });

    input.addEventListener('focus', () => {
        filterOptions();
    });

    input.addEventListener('input', () => {
        hiddenId.value = '';
        filterOptions();
        syncSelection();
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });

    document.addEventListener('click', (event) => {
        if (!combobox.contains(event.target)) {
            close();
        }
    });

    syncSelection();
    close();
});

document.querySelectorAll('[data-friend-search]').forEach((widget) => {
    const form = widget.querySelector('[data-friend-search-form]');
    const input = widget.querySelector('[data-friend-search-input]');
    const resultsPanel = widget.querySelector('[data-friend-search-results]');
    const searchUrl = widget.dataset.friendSearchUrl;
    const emptyHint = widget.dataset.friendSearchEmpty || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let debounceId = null;
    let controller = null;

    if (!form || !input || !resultsPanel || !searchUrl) {
        return;
    }

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const renderState = (message, tone = '') => {
        resultsPanel.innerHTML = `<p class="friend-search-state ${tone}">${escapeHtml(message)}</p>`;
    };

    const methodField = (method) => method
        ? `<input type="hidden" name="_method" value="${escapeHtml(method)}">`
        : '';

    const csrfField = csrfToken
        ? `<input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">`
        : '';

    const renderAction = (result) => {
        const friendship = result.friendship || {};

        if (friendship.state === 'none') {
            return `
                <form action="${escapeHtml(friendship.store_url)}" method="POST">
                    ${csrfField}
                    <button type="submit" class="mini-button">Adicionar</button>
                </form>
            `;
        }

        if (friendship.state === 'sent') {
            return `
                <span class="status-pill">Pedido enviado</span>
                <form action="${escapeHtml(friendship.destroy_url)}" method="POST">
                    ${csrfField}
                    ${methodField('DELETE')}
                    <button type="submit" class="ghost-button">Cancelar</button>
                </form>
            `;
        }

        if (friendship.state === 'received') {
            return `
                <form action="${escapeHtml(friendship.accept_url)}" method="POST">
                    ${csrfField}
                    <button type="submit" class="mini-button">Aceitar</button>
                </form>
            `;
        }

        return `
            <span class="status-pill status-pill-live">Amigos</span>
            <form action="${escapeHtml(friendship.destroy_url)}" method="POST">
                ${csrfField}
                ${methodField('DELETE')}
                <button type="submit" class="ghost-button">Remover</button>
            </form>
        `;
    };

    const renderResult = (result) => {
        const avatar = result.photo_url
            ? `<img class="friend-avatar friend-avatar-image" src="${escapeHtml(result.photo_url)}" alt="Foto de ${escapeHtml(result.display_name)}">`
            : `<span class="friend-avatar">${escapeHtml(result.avatar)}</span>`;

        return `
            <article class="friend-search-result">
                <a href="${escapeHtml(result.profile_url)}" class="friend-search-person">
                    ${avatar}
                    <span class="friend-search-copy">
                        <strong>${escapeHtml(result.display_name)}</strong>
                        <small>@${escapeHtml(result.username)}</small>
                    </span>
                </a>

                <div class="friend-search-action">
                    ${renderAction(result)}
                </div>
            </article>
        `;
    };

    const renderResults = (payload) => {
        if (!payload.has_search) {
            resultsPanel.innerHTML = `<p class="friend-search-hint">${escapeHtml(emptyHint)}</p>`;
            return;
        }

        if (!payload.results || payload.results.length === 0) {
            renderState('Nenhuma pessoa encontrada com essa busca.');
            return;
        }

        resultsPanel.innerHTML = payload.results.map(renderResult).join('');
    };

    const runSearch = async () => {
        const query = input.value.trim();

        if (!query) {
            if (controller) {
                controller.abort();
                controller = null;
            }
            renderResults({ has_search: false, results: [] });
            return;
        }

        if (controller) {
            controller.abort();
        }

        controller = new AbortController();
        renderState('Buscando...', 'is-loading');

        const url = new URL(searchUrl, window.location.origin);
        url.searchParams.set('friend_search', query);

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error('Search failed');
            }

            renderResults(await response.json());
        } catch (error) {
            if (error.name !== 'AbortError') {
                renderState('Nao foi possivel buscar agora. Tente novamente.', 'is-error');
            }
        }
    };

    const scheduleSearch = () => {
        window.clearTimeout(debounceId);
        debounceId = window.setTimeout(runSearch, 400);
    };

    input.addEventListener('input', scheduleSearch);

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        window.clearTimeout(debounceId);
        runSearch();
    });
});

document.querySelectorAll('[data-study-group-presence]').forEach((widget) => {
    const presenceUrl = widget.dataset.presenceUrl;
    const activeCount = widget.querySelector('[data-presence-active-count]');
    const secondsToday = widget.querySelector('[data-presence-seconds-today]');

    if (!presenceUrl || !activeCount || !secondsToday) {
        return;
    }

    const renderPresence = async () => {
        try {
            const response = await fetch(presenceUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            activeCount.textContent = `${payload.active_count} estudando agora`;
            secondsToday.textContent = `${formatElapsed(payload.seconds_today)} hoje`;
        } catch (error) {
            // Polling is best-effort; the server remains the source of truth.
        }
    };

    window.setInterval(renderPresence, 15000);
});

document.querySelectorAll('[data-focus-room-workspace]').forEach((workspace) => {
    workspace.querySelectorAll('[data-focus-room-select]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const roomId = link.dataset.focusRoomId;
            const card = workspace.querySelector(`[data-focus-room-card][data-focus-room-id="${roomId}"]`);
            const details = card?.querySelector('[data-focus-room-details]');

            if (!roomId || !card || !details) {
                return;
            }

            event.preventDefault();

            const shouldExpand = details.hidden;

            workspace.querySelectorAll('[data-focus-room-card]').forEach((item) => {
                const isSelected = item === card && shouldExpand;
                item.classList.toggle('is-expanded', isSelected);
                item.querySelector('[data-focus-room-details]')?.toggleAttribute('hidden', !isSelected);
            });

            const nextUrl = shouldExpand
                ? link.href
                : new URL(window.location.href);

            if (!shouldExpand) {
                nextUrl.searchParams.delete('room');
            }

            window.history.replaceState({}, '', nextUrl.toString());
        });
    });
});

document.querySelectorAll('[data-character-counter]').forEach((field) => {
    const counter = document.getElementById(field.dataset.characterCounter);
    const maxLength = Number(field.getAttribute('maxlength') || 0);

    if (!counter || !maxLength) {
        return;
    }

    const renderCount = () => {
        counter.textContent = `${field.value.length}/${maxLength} caracteres`;
    };

    field.addEventListener('input', renderCount);
    renderCount();
});

document.querySelectorAll('[data-subject-delete]').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.stopPropagation();

        const confirmed = window.confirm('Excluir esta materia? O historico de sessoes sera mantido.');

        if (!confirmed) {
            event.preventDefault();
        }
    });
});
