<?php
/**
 * Plugin Name: Analisis Kinerja Perpustakaan (PAKPI)
 * Plugin URI: https://github.com/adeism/slims-analisis-kinerja-perpustakaan
 * Description: Plugin analisis kinerja perpustakaan berdasarkan standar SNI ISO 2789:2013 dan Pedoman Analisis Kinerja Perpustakaan Indonesia (PAKPI).
 * Version: 1.1.0
 * Author: Ade Ismail Siregar
 * Author URI: https://github.com/adeism
 */

use SLiMS\Plugins;

// Register admin menus under reporting module
$plugin = Plugins::getInstance();
$plugin->registerMenu('reporting', 'Analisis Kinerja Perpustakaan (PAKPI)', __DIR__ . '/index.php');
$plugin->registerMenu('reporting', 'Eksplorasi Analisis Kinerja', __DIR__ . '/eksplorasi.php');
