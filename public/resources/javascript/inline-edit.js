window.lanlistInlineEditToggle = window.lanlistInlineEditToggle || function () {
    return false;
};

(function () {
    function qs(el, sel) {
        return el.querySelector(sel);
    }

    function eventTargetElement(event) {
        var el = event.target;
        if (!el) {
            return null;
        }
        if (el.nodeType === 3) {
            el = el.parentElement;
        }
        return el;
    }

    function setRowClass(container, rowClass) {
        var row = container.closest('[data-inline-row-class-target]');
        if (!row) {
            return;
        }
        row.classList.remove('bad', 'warn');
        if (rowClass) {
            row.classList.add(rowClass);
        }
    }

    function renderReadValue(container, value, display) {
        var valueEl = qs(container, '.inline-edit__value');
        var hintEl = qs(container, '.inline-edit__hint');
        if (!valueEl) {
            return;
        }

        valueEl.textContent = '';
        if (value) {
            var link = document.createElement('a');
            link.className = 'inline-edit__link';
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.href = display.discordInviteHref || value;
            link.textContent = value;
            valueEl.appendChild(link);
        } else {
            var empty = document.createElement('em');
            empty.className = 'inline-edit__empty';
            empty.textContent = 'Not set';
            valueEl.appendChild(empty);
        }

        if (hintEl) {
            hintEl.hidden = display.discordInviteRowClass !== 'warn';
        } else if (display.discordInviteRowClass === 'warn') {
            var hint = document.createElement('em');
            hint.className = 'inline-edit__hint';
            hint.textContent = ' (Does not match typical Discord URLs — verify manually.)';
            qs(container, '.inline-edit__read').appendChild(hint);
        }

        setRowClass(container, display.discordInviteRowClass || '');
    }

    function renderPublishedValue(container, display) {
        var valueEl = qs(container, '.inline-edit__value');
        var readEl = qs(container, '.inline-edit__read');
        var publishBtn = qs(container, '.inline-edit__publish');
        var unpublishBtn = qs(container, '.inline-edit__unpublish');
        var published = !!display.published;

        if (valueEl) {
            valueEl.textContent = '';
            if (published) {
                valueEl.textContent = 'yes';
            } else {
                var bad = document.createElement('span');
                bad.className = 'bad';
                bad.textContent = 'no';
                valueEl.appendChild(bad);
            }
        }

        if (published) {
            if (publishBtn) {
                publishBtn.hidden = true;
                publishBtn.disabled = false;
            }
            if (!unpublishBtn && readEl && !container.classList.contains('inline-edit--compact')) {
                unpublishBtn = document.createElement('button');
                unpublishBtn.type = 'button';
                unpublishBtn.className = 'inline-edit__unpublish';
                unpublishBtn.textContent = 'Unpublish';
                unpublishBtn.setAttribute('onclick', 'return window.lanlistInlineEditToggle(this, 0)');
                readEl.appendChild(unpublishBtn);
            }
            if (unpublishBtn) {
                unpublishBtn.hidden = false;
                unpublishBtn.disabled = false;
            }
        } else {
            if (publishBtn) {
                publishBtn.hidden = false;
                publishBtn.disabled = false;
            }
            if (unpublishBtn) {
                unpublishBtn.hidden = true;
                unpublishBtn.disabled = false;
            }
        }
    }

    function inlineEditUrl() {
        return new URL('json/inlineEdit.php', window.location.href).href;
    }

    function showError(container, message) {
        var err = qs(container, '.inline-edit__error');
        if (!err) {
            return;
        }
        if (message) {
            err.textContent = message;
            err.hidden = false;
        } else {
            err.textContent = '';
            err.hidden = true;
        }
    }

    function postInlineEdit(container, value, triggerBtn) {
        var entity = container.getAttribute('data-inline-entity');
        var id = parseInt(container.getAttribute('data-inline-id'), 10);
        var field = container.getAttribute('data-inline-field');
        var mode = container.getAttribute('data-inline-mode') || 'text';

        if (triggerBtn) {
            triggerBtn.disabled = true;
        }

        return fetch(inlineEditUrl(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ entity: entity, id: id, field: field, value: value }),
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    return { ok: res.ok, status: res.status, body: body };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.body.ok) {
                    var msg = (result.body && result.body.error) ? result.body.error : 'Save failed.';
                    showError(container, msg);
                    return null;
                }

                var savedValue = result.body.value || '';
                var display = result.body.display || {};
                container.setAttribute('data-inline-value', savedValue);

                if (mode === 'toggle') {
                    renderPublishedValue(container, display);
                    if (container.classList.contains('inline-edit--compact') && display.published) {
                        var row = container.closest('tr');
                        if (row) {
                            row.remove();
                        }
                    }
                } else {
                    renderReadValue(container, savedValue, display);
                }

                showError(container, '');
                return result.body;
            })
            .catch(function () {
                showError(container, 'Save failed.');
                return null;
            })
            .finally(function () {
                if (triggerBtn) {
                    triggerBtn.disabled = false;
                }
            });
    }

    function enterEdit(container) {
        var read = qs(container, '.inline-edit__read');
        var form = qs(container, '.inline-edit__form');
        var input = qs(container, '.inline-edit__input');
        if (!read || !form || !input) {
            return;
        }
        input.value = container.getAttribute('data-inline-value') || '';
        read.hidden = true;
        form.hidden = false;
        showError(container, '');
        input.focus();
        input.select();
    }

    function leaveEdit(container) {
        var read = qs(container, '.inline-edit__read');
        var form = qs(container, '.inline-edit__form');
        if (!read || !form) {
            return;
        }
        read.hidden = false;
        form.hidden = true;
        showError(container, '');
    }

    function save(container) {
        var input = qs(container, '.inline-edit__input');
        var saveBtn = qs(container, '.inline-edit__save');
        if (!input || !saveBtn) {
            return;
        }

        postInlineEdit(container, input.value, saveBtn).then(function (body) {
            if (body) {
                leaveEdit(container);
            }
        });
    }

    function initText(container) {
        var editBtn = qs(container, '.inline-edit__edit');
        var cancelBtn = qs(container, '.inline-edit__cancel');
        var saveBtn = qs(container, '.inline-edit__save');
        var input = qs(container, '.inline-edit__input');

        if (editBtn) {
            editBtn.addEventListener('click', function () {
                enterEdit(container);
            });
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                leaveEdit(container);
            });
        }
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                save(container);
            });
        }
        if (input) {
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    save(container);
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    leaveEdit(container);
                }
            });
        }
    }

    function init(container) {
        var mode = container.getAttribute('data-inline-mode') || 'text';
        if (mode !== 'toggle') {
            initText(container);
        }
    }

    function toggleFromButton(btn, value) {
        if (!btn || !btn.closest) {
            return false;
        }
        var container = btn.closest('.inline-edit');
        if (!container || container.getAttribute('data-inline-mode') !== 'toggle') {
            return false;
        }
        postInlineEdit(container, String(value), btn);
        return false;
    }

    window.lanlistInlineEditToggle = toggleFromButton;

    function handleToggleClick(event) {
        var el = eventTargetElement(event);
        if (!el || !el.closest) {
            return;
        }
        var btn = el.closest('.inline-edit__publish, .inline-edit__unpublish');
        if (!btn) {
            return;
        }
        event.preventDefault();
        var value = btn.classList.contains('inline-edit__publish') ? '1' : '0';
        toggleFromButton(btn, value);
    }

    function boot() {
        var nodes = document.querySelectorAll('.inline-edit');
        for (var i = 0; i < nodes.length; i++) {
            init(nodes[i]);
        }
    }

    if (!window.lanlistInlineEditBooted) {
        window.lanlistInlineEditBooted = true;
        document.addEventListener('click', handleToggleClick);
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }
    }
})();
