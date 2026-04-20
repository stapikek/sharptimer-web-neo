<?php

if (!defined('IN_LR')) {
    die('Access denied');
}

require_once(__DIR__ . '/data.php');
$SurfRecords = new SurfRecordsModule($General, $Translate);

if (!$SurfRecords->isConnected()) {
    echo '<div class="alert alert-danger">Database connection failed. Please try again later.</div>';
    return;
}

$t = function($key) use ($Translate) {
    return $Translate->get_translate_module_phrase('module_page_surf', '_' . $key);
};

$current_map = $SurfRecords->getConfig()['display']['default_map'];
if (isset($_GET['map']) && is_string($_GET['map'])) {
    $map_param = trim($_GET['map']);
    if (preg_match('/^[a-zA-Z0-9_-]+$/', $map_param) && strlen($map_param) > 0 && strlen($map_param) <= 64) {
        $current_map = $map_param;
    } else {
        error_log('SurfRecordsModule: Invalid map name in URL - ' . htmlspecialchars($map_param, ENT_QUOTES, 'UTF-8'));
    }
}

$maps = $SurfRecords->getMaps();
$records = $SurfRecords->getMapRecords($current_map);
$stats = $SurfRecords->getStatistics();
$config = $SurfRecords->getConfig();
?>

<div class="surf-records-module">
    <div class="surf-header">
        <div class="surf-header-content">
        <h1 class="surf-title"><?= $t('title') ?></h1>
        <p class="surf-description"><?= $t('description') ?></p>
        </div>

        <div class="surf-stats-cards">
            <div class="surf-stat-card">
                <div class="stat-icon">
                <svg><use href="/resources/img/sprite.svg#star-fill"></use></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($stats['total_records']) ?></div>
                    <div class="stat-label"><?= $t('total_records') ?></div>
                </div>
            </div>
            
            <div class="surf-stat-card">
                <div class="stat-icon">
                <svg><use href="/resources/img/sprite.svg#three-users"></use></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($stats['total_players']) ?></div>
                    <div class="stat-label"><?= $t('total_players') ?></div>
                </div>
            </div>
            
            <div class="surf-stat-card">
                <div class="stat-icon">
                <svg><use href="/resources/img/sprite.svg#play-triangle"></use></svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($stats['total_maps']) ?></div>
                    <div class="stat-label"><?= $t('total_maps') ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mobile-maps-block">
        <div class="mobile-maps-header">
            <button onclick="toggleMobileMaps()">
                <i class="fa-solid fa-bars"></i> <?= $t('map') ?>
            </button>
        </div>
        
        <div class="mobile-maps-content" id="mobile-maps-content">
            <?php if ($config['display']['map_division']): ?>
            <div class="mobile-maps-tabs">
                <?php if (!empty($maps['surf'])): ?>
                <button onclick="openMobileMapTab(event, 'surf')">
                    <?= $t('map_surf') ?> (<?= count($maps['surf']) ?>)
                </button>
                <?php endif; ?>
                
                <?php if (!empty($maps['kz'])): ?>
                <button onclick="openMobileMapTab(event, 'kz')">
                    <?= $t('map_kz') ?> (<?= count($maps['kz']) ?>)
                </button>
                <?php endif; ?>
                
                <?php if (!empty($maps['bhop'])): ?>
                <button onclick="openMobileMapTab(event, 'bhop')">
                    <?= $t('map_bhop') ?> (<?= count($maps['bhop']) ?>)
                </button>
                <?php endif; ?>
                
                <?php if (!empty($maps['other'])): ?>
                <button onclick="openMobileMapTab(event, 'other')">
                    <?= $t('map_other') ?> (<?= count($maps['other']) ?>)
                </button>
                <?php endif; ?>
            </div>
            <?php foreach (['surf', 'kz', 'bhop', 'other'] as $type): ?>
                <?php if (!empty($maps[$type])): ?>
                <div id="mobile-maps-<?= $type ?>" class="mobile-maps-list-content <?= $type === 'surf' ? 'active' : '' ?>">
                    <ul class="mobile-maps-list">
                        <?php foreach ($maps[$type] as $map): ?>
                        <li class="mobile-map-item <?= $map === $current_map ? 'active' : '' ?>" 
                            data-map="<?= htmlspecialchars($map, ENT_QUOTES, 'UTF-8') ?>"
                            onclick="selectMobileMap('<?= htmlspecialchars($map, ENT_QUOTES, 'UTF-8') ?>')">
                            <?= htmlspecialchars($map, ENT_QUOTES, 'UTF-8') ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="surf-main-content">
        <div class="surf-maps-sidebar">
            <div class="maps-sidebar-sticky">
                <button onclick="toggleMapsSidebar()">
                    <i class="fa-solid fa-bars"></i> <?= $t('map') ?>
                </button>
                
                <div class="inputs-inline">
                    <label for="map-search-input"><?= $t('search_placeholder') ?></label>
                    <input id="map-search-input" type="text" value="" name="map-search" placeholder="<?= htmlspecialchars($t('search_placeholder'), ENT_QUOTES, 'UTF-8') ?>" onkeyup="filterMaps()">
                </div>
                
                <?php if ($config['display']['map_division']): ?>
                <div class="maps-tabs">
                    <?php if (!empty($maps['surf'])): ?>
                    <button onclick="openMapTab(event, 'surf')">
                        <?= $t('map_surf') ?> (<?= count($maps['surf']) ?>)
                    </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($maps['kz'])): ?>
                    <button onclick="openMapTab(event, 'kz')">
                        <?= $t('map_kz') ?> (<?= count($maps['kz']) ?>)
                    </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($maps['bhop'])): ?>
                    <button onclick="openMapTab(event, 'bhop')">
                        <?= $t('map_bhop') ?> (<?= count($maps['bhop']) ?>)
                    </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($maps['other'])): ?>
                    <button onclick="openMapTab(event, 'other')">
                        <?= $t('map_other') ?> (<?= count($maps['other']) ?>)
                    </button>
                    <?php endif; ?>
                </div>
                <?php foreach (['surf', 'kz', 'bhop', 'other'] as $type): ?>
                    <?php if (!empty($maps[$type])): ?>
                    <div id="maps-<?= $type ?>" class="maps-content <?= $type === 'surf' ? 'active' : '' ?>">
                        <ul class="maps-list">
                            <?php foreach ($maps[$type] as $map): ?>
                            <li class="map-item <?= $map === $current_map ? 'active' : '' ?>" 
                                data-map="<?= htmlspecialchars($map, ENT_QUOTES, 'UTF-8') ?>"
                                onclick="selectMap('<?= htmlspecialchars($map, ENT_QUOTES, 'UTF-8') ?>')">
                                <?= htmlspecialchars($map, ENT_QUOTES, 'UTF-8') ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <?php else: ?>
                <div class="maps-content active">
                    <ul class="maps-list">
                        <?php 
                        $all_maps = array_merge($maps['surf'], $maps['kz'], $maps['bhop'], $maps['other']);
                        foreach ($all_maps as $map): 
                        ?>
                        <li class="map-item <?= $map === $current_map ? 'active' : '' ?>" 
                            data-map="<?= htmlspecialchars($map, ENT_QUOTES, 'UTF-8') ?>"
                            onclick="selectMap('<?= htmlspecialchars($map, ENT_QUOTES, 'UTF-8') ?>')">
                            <?= htmlspecialchars($map, ENT_QUOTES, 'UTF-8') ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="surf-leaderboard">
            <div class="leaderboard-header">
                <h2 class="leaderboard-title">
                    <i class="fa-solid fa-ranking-star"></i>
                    <?= htmlspecialchars($current_map, ENT_QUOTES, 'UTF-8') ?>
                </h2>
                <div class="leaderboard-count">
                 <span class="count-label"><?= $t('total_records') ?></span>
                 <span class="count-number"><?= count($records) ?></span>
                </div>
            </div>
            
            <div class="leaderboard-table">
                <div class="leaderboard-table-header">
                    <div class="table-col col-place"><?= $t('place') ?></div>
                    <div class="table-col col-player"><?= $t('player') ?></div>
                    <div class="table-col col-time"><?= $t('time') ?></div>
                    <div class="table-col col-actions"></div>
                </div>
                
                <div class="leaderboard-table-body">
                    <?php if (empty($records)): ?>
                    <div class="no-records">
                        <i class="fa-solid fa-inbox"></i>
                        <p><?= $t('no_records') ?></p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($records as $record): ?>
                        <div class="leaderboard-row <?= $record['place'] <= 3 ? 'top-' . $record['place'] : '' ?>">
                            <div class="table-col col-place" data-label="<?= htmlspecialchars($t('place'), ENT_QUOTES, 'UTF-8') ?>: ">
                                <span class="place-number place-<?= $record['place'] ?>">
                                    #<?= $record['place'] ?>
                                </span>
                            </div>
                            <div class="table-col col-player" data-label="<?= htmlspecialchars($t('player'), ENT_QUOTES, 'UTF-8') ?>: ">
                                <a href="/profiles/<?= htmlspecialchars($record['SteamID'], ENT_QUOTES, 'UTF-8') ?>/?search=1" 
                                   class="player-name-link"
                                   title="<?= htmlspecialchars($t('view_profile'), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($record['PlayerName'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </div>
                            <div class="table-col col-time" data-label="<?= htmlspecialchars($t('time'), ENT_QUOTES, 'UTF-8') ?>: ">
                                <span class="time-value"><?= htmlspecialchars($record['FormattedTime'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="table-col col-actions">
                                <a href="https://steamcommunity.com/profiles/<?= htmlspecialchars($record['SteamID'], ENT_QUOTES, 'UTF-8') ?>" 
                                   target="_blank" 
                                   title="Steam">
                                   <svg><use href="/resources/img/sprite.svg#steam"></use></svg>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<template id="template-loading">
    <div class="loading-indicator">
        <div class="loading-spinner"></div>
        <p><?= $t('loading_records') ?></p>
    </div>
</template>

<template id="template-error">
    <div class="error-message">
        <i class="fa-solid fa-exclamation-triangle"></i>
        <p class="error-text"></p>
    </div>
</template>

<template id="template-no-records">
    <div class="no-records">
        <i class="fa-solid fa-inbox"></i>
        <p><?= $t('no_records') ?></p>
    </div>
</template>

<template id="template-leaderboard-row">
    <div class="leaderboard-row">
        <div class="table-col col-place" data-label="<?= htmlspecialchars($t('place'), ENT_QUOTES, 'UTF-8') ?>: ">
            <span class="place-number"></span>
        </div>
        <div class="table-col col-player" data-label="<?= htmlspecialchars($t('player'), ENT_QUOTES, 'UTF-8') ?>: ">
            <a class="player-name-link" title="<?= htmlspecialchars($t('view_profile'), ENT_QUOTES, 'UTF-8') ?>"></a>
        </div>
        <div class="table-col col-time" data-label="<?= htmlspecialchars($t('time'), ENT_QUOTES, 'UTF-8') ?>: ">
            <span class="time-value"></span>
        </div>
        <div class="table-col col-actions">
            <a target="_blank" title="Steam">
               <svg><use href="/resources/img/sprite.svg#steam"></use></svg>
            </a>
        </div>
    </div>
</template>