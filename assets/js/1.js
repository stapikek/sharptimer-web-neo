const SurfState = {
    currentMap: 'surf_boreas',
    currentCategory: 'surf',
    loading: false,
    
    reset() {
        this.currentMap = 'surf_boreas';
        this.currentCategory = 'surf';
        this.loading = false;
    }
};

window.escapeHtml = function(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
};

window.encodeURL = function(url) {
    return encodeURIComponent(String(url || ''));
};

const SurfDOM = {
    sidebar: null,
    leaderboardBody: null,
    leaderboardTitle: null,
    leaderboardCount: null,
    searchInput: null,
    
    get(selector) {
        const parts = selector.split('.');
        const key = parts[0];
        
        if (!this[key]) {
            const selector_map = {
                'sidebar': '.surf-maps-sidebar',
                'leaderboardBody': '.leaderboard-table-body',
                'leaderboardTitle': '.leaderboard-title',
                'leaderboardCount': '.leaderboard-count',
                'searchInput': '#map-search-input'
            };
            const actualSelector = selector_map[key] || selector;
            this[key] = $(actualSelector);
        }
        return this[key];
    },
    
    clear() {
        this.sidebar = null;
        this.leaderboardBody = null;
        this.leaderboardTitle = null;
        this.leaderboardCount = null;
        this.searchInput = null;
    }
};

function getSurfTranslation(key, fallback) {
    if (typeof get_translate_module_phrase !== 'undefined') {
        const translated = get_translate_module_phrase('module_page_surf', '_' + key);
        if (translated && translated !== ('_' + key)) return translated;
    }
    return fallback || key;
}

function surfNotify(message, type) {
    if (typeof noty !== 'undefined') {
        noty(message, type || 'info');
    }
}

window.toggleMapsSidebar = function() {
    const sidebar = SurfDOM.get('sidebar');
    sidebar.toggleClass('active');
};

window.toggleMobileMaps = function() {
    const content = $('.mobile-maps-content');
    if (content.length) {
        content.toggleClass('active');
    }
};

window.openMapTab = function(event, tabName) {
    event.preventDefault();
    event.stopPropagation();
    
    $('.maps-content').removeClass('active');
    $('button[onclick*="openMapTab"]').removeClass('active');
    
    $('#maps-' + tabName).addClass('active');
    if (event.currentTarget) {
        $(event.currentTarget).addClass('active');
    }
    
    SurfState.currentCategory = tabName;
};

window.openMobileMapTab = function(event, tabName) {
    event.preventDefault();
    event.stopPropagation();
    
    $('.mobile-maps-list-content').removeClass('active');
    $('button[onclick*="openMobileMapTab"]').removeClass('active');
    
    $('#mobile-maps-' + tabName).addClass('active');
    if (event.currentTarget) {
        $(event.currentTarget).addClass('active');
    }
};

window.selectMap = function(mapName) {
    if (!mapName || typeof mapName !== 'string') {
        console.warn('selectMap: Invalid map name');
        return;
    }
    
    if (!/^[a-zA-Z0-9_-]+$/.test(mapName) || mapName.length > 64) {
        console.warn('selectMap: Map name contains invalid characters');
        return;
    }
    
    SurfState.currentMap = mapName;
    loadMapRecords(mapName);
    updateActiveMap(mapName);
    updateURL(mapName);
};

window.selectMobileMap = function(mapName) {
    selectMap(mapName);
    const mobileContent = $('.mobile-maps-content');
    if (mobileContent.length) {
        mobileContent.removeClass('active');
    }
};

window.debounce = function(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

const debouncedFilterMaps = window.debounce(function() {
    const input = SurfDOM.get('searchInput');
    if (!input.length) return;
    
    const filter = input.val().toUpperCase();
    const mapItems = $('.map-item');
    
    mapItems.each(function() {
        const mapName = $(this).data('map');
        if (mapName && String(mapName).toUpperCase().indexOf(filter) > -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}, 150);

window.filterMaps = function() {
    debouncedFilterMaps();
};

function updateActiveMap(mapName) {
    $('.map-item').removeClass('active');
    $('.map-item[data-map="' + mapName + '"]').addClass('active');
    
    const activeItem = $('.map-item[data-map="' + mapName + '"]');
    if (activeItem.length) {
        activeItem[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function updateURL(mapName) {
    if (window.history && window.history.pushState) {
        const url = window.location.pathname + '?map=' + encodeURIComponent(mapName);
        window.history.pushState(null, '', url);
    }
}

function loadMapRecords(mapName) {
    if (!mapName || !/^[a-zA-Z0-9_-]+$/.test(mapName) || mapName.length > 64) {
        return;
    }
    
    const leaderboardBody = SurfDOM.get('leaderboardBody');
    const leaderboardTitle = SurfDOM.get('leaderboardTitle');
    const leaderboardCount = SurfDOM.get('leaderboardCount');
    
    if (!leaderboardBody.length || !leaderboardTitle.length || !leaderboardCount.length) return;
    
    SurfState.loading = true;
    leaderboardBody.addClass('updating');
    showLoadingIndicator(leaderboardBody);
    
    $.ajax({
        type: 'GET',
        url: '/app/modules/module_page_surf/api/index.php',
        data: {
            endpoint: 'records',
            map: mapName
        },
        dataType: 'json',
        timeout: 10000,
        success: function(data) {
            if (data && data.success && data.data) {
                renderLeaderboard(leaderboardBody, data.data.records || [], mapName);
                leaderboardTitle.text(mapName);
                leaderboardCount.find('.count-number').text(data.data.count || 0);
            } else {
                const errorMsg = data.error || getSurfTranslation('loading_records_error', 'Error loading records');
                showErrorMessage(leaderboardBody, errorMsg);
            }
        },
        error: function(xhr, status, error) {
            if (status !== 'abort') {
                showErrorMessage(leaderboardBody, getSurfTranslation('loading_records_error', 'Error loading records'));
            }
        },
        complete: function() {
            SurfState.loading = false;
            leaderboardBody.removeClass('updating');
        }
    });
}

function renderLeaderboard(container, records, mapName) {
    if (!records || records.length === 0) {
        showNoRecords(container);
        return;
    }
    
    const template = document.querySelector('#template-leaderboard-row');
    
    if (!template) {
        showErrorMessage(container, 'Template not found');
        return;
    }
    
    const containerElement = container[0];
    containerElement.innerHTML = ''; 
    const fragment = document.createDocumentFragment();
    
    records.forEach((record, index) => {
        const place = index + 1;
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('.leaderboard-row');
        
        if (place <= 3) {
            row.classList.add('top-' + place);
        }
        
        clone.querySelector('.place-number').textContent = '#' + place;
        clone.querySelector('.place-number').className = 'place-number place-' + place;
        
        const playerLink = clone.querySelector('.player-name-link');
        playerLink.href = '/profiles/' + encodeURL(record.SteamID) + '/?search=1';
        playerLink.textContent = record.PlayerName;
        
        clone.querySelector('.time-value').textContent = record.FormattedTime || record.Time;
        clone.querySelector('.table-col.col-actions a').href = 'https://steamcommunity.com/profiles/' + record.SteamID;
        
        fragment.appendChild(clone);
    });
    
    containerElement.appendChild(fragment);
}

function showLoadingIndicator(container) {
    const template = document.querySelector('#template-loading');
    if (template) {
        container.empty();
        container[0].appendChild(template.content.cloneNode(true));
    }
}

function showErrorMessage(container, message) {
    const template = document.querySelector('#template-error');
    if (template) {
        const clone = template.content.cloneNode(true);
        const errorText = clone.querySelector('.error-text');
        if (errorText) {
            errorText.textContent = message;
        }
        container.empty();
        container[0].appendChild(clone);
    }
}

function showNoRecords(container) {
    const template = document.querySelector('#template-no-records');
    if (template) {
        container.empty();
        container[0].appendChild(template.content.cloneNode(true));
    }
}

$(document).ready(function() {
    SurfDOM.get('leaderboardBody');
    SurfDOM.get('leaderboardTitle');
    SurfDOM.get('leaderboardCount');
    SurfDOM.get('sidebar');
    SurfDOM.get('searchInput');
    
    const urlParams = new URLSearchParams(window.location.search);
    const mapParam = urlParams.get('map');
    
    if (mapParam && /^[a-zA-Z0-9_-]+$/.test(mapParam)) {
        SurfState.currentMap = mapParam;
    }
    
    loadMapRecords(SurfState.currentMap);
    updateActiveMap(SurfState.currentMap);
    
    $(document).on('click', '.map-item', function(e) {
        if (window.innerWidth <= 768) {
            const sidebar = SurfDOM.get('sidebar');
            setTimeout(() => sidebar.removeClass('active'), 100);
        }
    });
    
    let closeTimeout;
    $(document).on('click', function(e) {
        if (window.innerWidth > 768) return;
        
        const sidebar = SurfDOM.get('sidebar');
        const toggleBtn = $('.maps-sidebar-sticky > button');
        
        if (sidebar.length && toggleBtn.length) {
            if (!sidebar.is(e.target) && sidebar.has(e.target).length === 0 &&
                !toggleBtn.is(e.target) && toggleBtn.has(e.target).length === 0 &&
                sidebar.hasClass('active')) {
                clearTimeout(closeTimeout);
                closeTimeout = setTimeout(() => sidebar.removeClass('active'), 50);
            }
        }
    });
    
    let lastEscapeTime = 0;
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            const now = Date.now();
            if (now - lastEscapeTime < 100) return;
            lastEscapeTime = now;
            
            const sidebar = SurfDOM.get('sidebar');
            if (sidebar.hasClass('active')) {
                sidebar.removeClass('active');
            }
        }
        
        if (e.key === '/' && !e.ctrlKey && !e.metaKey && $(document.activeElement).attr('id') !== 'map-search-input') {
            e.preventDefault();
            SurfDOM.get('searchInput').focus();
        }
    });
});