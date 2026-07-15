(function () {
    var configEl = document.getElementById('log-list-config');
    if (!configEl) {
        return;
    }

    var config;
    try {
        config = JSON.parse(configEl.textContent || '{}');
    } catch (err) {
        return;
    }

    var menu = document.createElement('div');
    menu.className = 'log-event-type-menu';
    menu.hidden = true;
    menu.setAttribute('role', 'menu');
    document.body.appendChild(menu);

    function hideMenu() {
        menu.hidden = true;
    }

    function buildExcludeUrl(eventType) {
        var excluded = Array.isArray(config.excludedEventTypes) ? config.excludedEventTypes.slice() : [];
        if (excluded.indexOf(eventType) === -1) {
            excluded.push(eventType);
        }
        excluded.sort();

        var params = new URLSearchParams();
        if (config.fullView) {
            params.set('full', '1');
        }
        excluded.forEach(function (type) {
            params.append('excludeEventType[]', type);
        });

        var query = params.toString();
        return 'listLogs.php' + (query ? '?' + query : '');
    }

    function showMenu(event, eventType) {
        menu.innerHTML = '';

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'log-event-type-menu__item';
        button.setAttribute('role', 'menuitem');
        button.textContent = 'Exclude ' + eventType;
        button.addEventListener('click', function () {
            window.location.href = buildExcludeUrl(eventType);
        });
        menu.appendChild(button);

        menu.hidden = false;
        menu.style.left = event.clientX + 'px';
        menu.style.top = event.clientY + 'px';

        var rect = menu.getBoundingClientRect();
        var maxLeft = window.innerWidth - rect.width - 8;
        var maxTop = window.innerHeight - rect.height - 8;
        if (rect.left > maxLeft) {
            menu.style.left = Math.max(8, maxLeft) + 'px';
        }
        if (rect.top > maxTop) {
            menu.style.top = Math.max(8, maxTop) + 'px';
        }
    }

    document.addEventListener('contextmenu', function (event) {
        var target = event.target;
        if (!target || !target.closest) {
            return;
        }

        var eventTypeEl = target.closest('.log-event-type');
        if (!eventTypeEl) {
            return;
        }

        var eventType = eventTypeEl.getAttribute('data-event-type');
        if (!eventType) {
            return;
        }

        event.preventDefault();
        showMenu(event, eventType);
    });

    document.addEventListener('click', function (event) {
        if (menu.hidden) {
            return;
        }
        if (event.target && menu.contains(event.target)) {
            return;
        }
        hideMenu();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            hideMenu();
        }
    });

    window.addEventListener('scroll', hideMenu, true);
    window.addEventListener('resize', hideMenu);
})();
