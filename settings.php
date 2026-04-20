<?php
/**
 * Файл конфигурации модуля
 * 
 * Настройки базы данных загружаются из storage/cache/sessions/db.php
 * Здесь только настройки отображения и кеширования
*/

if (!defined('IN_LR')) {
    die('Доступ запрещен');
}

return [
    'display' => [
        'default_map' => 'surf_boreas',
        'records_per_page' => 50,
        'map_division' => true,
        'default_tab' => 'surf'
    ],
    
    'cache' => [
        'enabled' => true,
        'time' => 1800,  // По умолчанию: 30 минут
        'maps_cache_time' => 3600,  // Карты: 1 час (редко изменяются)
        'stats_cache_time' => 900,  // Статистика: 15 минут
        'records_cache_time' => 300  // Рекорды: 5 минут (более частые обновления)
    ]
];