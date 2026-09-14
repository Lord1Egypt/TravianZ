<?php
use App\Utils\AccessLogger;

#################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 ##
## --------------------------------------------------------------------------- ##
##  Filename       : index.php                      	                       ##
##  Type           : In Game Index Page                                        ##
## --------------------------------------------------------------------------- ##
##  Developed by   : Dzoki 						                               ##
##  Refactored by  : Shadow                                                    ##
##  Redesign by    : Shadow                                                    ##
## --------------------------------------------------------------------------- ##
##  Contact        : cata7007@gmail.com                                        ##
##  Project        : TravianZ                                                  ##
##  URLs:          : https://travianz.org                                      ##
##  GitHub         : https://github.com/Shadowss/TravianZ                      ##
## --------------------------------------------------------------------------- ##
##  License        : TravianZ Project                                          ##
##  Copyright      : TravianZ (c) 2010-2026. All rights reserved.              ##
## --------------------------------------------------------------------------- ##
#################################################################################

if(!file_exists('var/installed') && @opendir('install')) {
    header("Location: install/");
    exit;
}

include_once("GameEngine/config.php");
/*
if($_SERVER['HTTP_HOST'] != '.SERVER.')
{
    header('location: '.SERVER.'');
    exit;
}
*/

// delete the /* and the */ if you not use localhost.

error_reporting(E_ALL || E_NOTICE);

if(file_exists('Security/Security.class.php'))
{
    require 'Security/Security.class.php';
    Security::instance();
}
else
{
    die('Security: Please activate security class!');
}

include_once "GameEngine/Database.php";
require_once __DIR__ . "/GameEngine/Lang/loader.php";
tz_load_language(LANG);

AccessLogger::logRequest();

/*
 * =========================================================================
 * MULTI INSTANCE SERVER LIST
 * =========================================================================
 * Fiecare intrare de mai jos e o instalare TravianZ complet independenta:
 * propriul subdomeniu, propriul config.php, propriu DB, propriul Admin
 * Panel. Acest fisier NU partajeaza conturi/DB intre worlds - doar citeste
 * si afiseaza contorul public de jucatori + rezumatul de setari (fostul
 * Newsbox1 din joc) al fiecarui world, si trimite Login/Register catre
 * subdomeniul propriu al acelui world.
 *
 * Pentru a adauga un world nou: il instalezi manual pe subdomeniul lui,
 * apoi adaugi o intrare aici cu login/signup/status/newsbox care se
 * potrivesc. Nu mai trebuie modificat niciun alt fisier.
 *
 * ANOMALIE semnalata (nu am modificat comportamentul): world-ul curent isi
 * cere si el statisticile prin HTTP catre propriile server_status /
 * server_newsbox, exact ca pentru worlds-urile straine, desi ar putea
 * calcula local aceleasi query-uri fara round-trip de retea. Am lasat asa
 * ca sa nu presupun cum identifici in mod sigur "acest world" in array
 * (ar necesita potrivire pe domeniu/HTTP_HOST, fragil in spatele unui
 * proxy/cPanel) - dar e un candidat clar de optimizat daca vrei.
 */
$servers = array(
    1 => array(
        'name' => 'Server 1',
        'url' => 'https://travianz.org/',
        'login' => 'https://travianz.org/login.php',
        'signup' => 'https://travianz.org/anmelden.php',
        'image' => 'en1_big.jpg',
        'status' => 'https://travianz.org/index.php?server_status=1',
        'newsbox' => 'https://travianz.org/index.php?server_newsbox=1'
    ),
    2 => array(
        'name' => 'Server 2',
        'url' => 'https://s2.travianz.org/',
        'login' => 'https://s2.travianz.org/login.php',
        'signup' => 'https://s2.travianz.org/anmelden.php',
        'image' => 'en2_big.jpg',
        'status' => 'https://s2.travianz.org/index.php?server_status=1',
        'newsbox' => 'https://s2.travianz.org/index.php?server_newsbox=1'
    ),
    3 => array(
        'name' => 'Speed 3x',
        'url' => 'https://speed.travianz.org/',
        'login' => 'https://speed.travianz.org/login.php',
        'signup' => 'https://speed.travianz.org/anmelden.php',
        'image' => 'enx_big.jpg',
        'status' => 'https://speed.travianz.org/index.php?server_status=1',
        'newsbox' => 'https://speed.travianz.org/index.php?server_newsbox=1'
    )
    // Adauga aici worlds noi, ex:
    // 4 => array(
    //     'name'    => 'Server 3',
    //     'url'     => 'https://s3.travianz.org/',
    //     'login'   => 'https://s3.travianz.org/login.php',
    //     'signup'  => 'https://s3.travianz.org/anmelden.php',
    //     'image'   => 'en3_big.jpg',
    //     'status'  => 'https://s3.travianz.org/index.php?server_status=1',
    //     'newsbox' => 'https://s3.travianz.org/index.php?server_newsbox=1'
    // ),
);

/*
 * Fiecare instanta de joc isi expune propriile statistici prin endpoint-ul
 * ?server_status=1 de mai jos - ruleaza pe DB-ul instantei CURENTE, deci
 * Server 1 nu poate afla niciodata contoarele de la Server 2/3.
 */
if (isset($_GET['server_status'])) {
    header('Content-Type: application/json; charset=utf-8');

    $tribes = '1, 2, 3, 6, 7, 8, 9';

    $users = 0;
    $active = 0;
    $online = 0;

    $return = mysqli_query(
        $link,
        "SELECT COUNT(*) AS Total FROM " . TB_PREFIX . "users WHERE tribe IN(" . $tribes . ")"
    );

    if ($return) {
        $row = mysqli_fetch_assoc($return);
        $users = isset($row['Total']) ? (int)$row['Total'] : 0;
    }

    $return = mysqli_query(
        $link,
        "SELECT COUNT(*) AS Total FROM " . TB_PREFIX . "users WHERE timestamp > " . (time() - (3600 * 24)) . " AND tribe IN(" . $tribes . ")"
    );

    if ($return) {
        $row = mysqli_fetch_assoc($return);
        $active = isset($row['Total']) ? (int)$row['Total'] : 0;
    }

    $return = mysqli_query(
        $link,
        "SELECT COUNT(*) AS Total FROM " . TB_PREFIX . "users WHERE timestamp > " . (time() - (60 * 10)) . " AND tribe IN(" . $tribes . ")"
    );

    if ($return) {
        $row = mysqli_fetch_assoc($return);
        $online = isset($row['Total']) ? (int)$row['Total'] : 0;
    }

    echo json_encode(array(
        'status' => 'online',
        'players' => $users,
        'active' => $active,
        'online' => $online
    ));

    exit;
}

/*
 * Endpoint public nou: ?server_newsbox=1
 * Expune, in JSON, exact aceleasi date pe care in-game le arata deja
 * Templates/News/newsbox1.tpl (jucatori online, viteza, harta, protectie,
 * top player etc.) - aceleasi query-uri/conditii, doar output JSON in loc
 * de HTML, ca sa poata fi citit de lobby-ul altui world (sau de acest
 * index.php insusi, pentru propriile date).
 */
if (isset($_GET['server_newsbox'])) {
    header('Content-Type: application/json; charset=utf-8');

    $online_total = 0;
    $return = mysqli_query(
        $link,
        "SELECT COUNT(*) AS Total FROM " . TB_PREFIX . "users WHERE timestamp > " . (time() - (60 * 10)) . " AND tribe != 0 AND tribe != 4 AND tribe != 5"
    );
    if ($return) {
        $row = mysqli_fetch_assoc($return);
        if ($row && isset($row['Total'])) {
            $online_total = (int)$row['Total'];
        }
    }

    $top_username = '-';
    $top_query = mysqli_query(
        $link,
        "SELECT username FROM " . TB_PREFIX . "users WHERE " .
        (INCLUDE_ADMIN ? 'access > 0 AND ' : 'access > 0 AND access < 8 AND ') .
        "id > 5 AND tribe IN (1,2,3,6,7,8,9) AND tribe > 0 ORDER BY oldrank ASC LIMIT 1"
    );
    if ($top_query) {
        $row = mysqli_fetch_assoc($top_query);
        if ($row && !empty($row['username'])) {
            $top_username = $row['username'];
        }
    }

    if (CP == 0) {
        $village_exp = 'Fast';
    } else if (CP == 1) {
        $village_exp = 'Slow';
    } else {
        $village_exp = '-';
    }

    if (MEDALINTERVAL >= 86400) {
        $medal_interval = (MEDALINTERVAL / 86400) . ' Days';
    } else {
        $medal_interval = (MEDALINTERVAL / 3600) . ' Hours';
    }

    $peaceTypes = array('None', 'Normal', 'Christmas', 'New Year', 'Easter');
    $peace_system = isset($peaceTypes[PEACE]) ? $peaceTypes[PEACE] : 'Unknown';

    echo json_encode(array(
        'online_users' => $online_total,
        'speed' => SPEED,
        'troop_speed' => INCREASE_SPEED,
        'evasion_speed' => EVASION_SPEED,
        'map_size' => WORLD_MAX,
        'village_exp' => $village_exp,
        'protection_hours' => (int)(PROTECTION / 3600),
        'medal_interval' => $medal_interval,
        'start_date' => START_DATE,
        'peace_system' => $peace_system,
        'best_player' => $top_username
    ));

    exit;
}

/*
 * Fetch generic, in PARALEL (curl_multi), de JSON de la o lista de
 * URL-uri indexate dupa id de world. Folosit atat pentru server_status
 * cat si pentru server_newsbox - un singur loc care stie sa vorbeasca
 * HTTP catre celelalte worlds.
 */
define('MULTI_STATUS_CONNECT_TIMEOUT', 2);
define('MULTI_STATUS_TIMEOUT', 3);

function fetchJsonFromServers(array $urlsById)
{
    $results = array();

    if (!function_exists('curl_multi_init')) {
        // cURL indisponibil - toate worlds-urile raman fara date live.
        foreach ($urlsById as $id => $url) {
            $results[$id] = null;
        }
        return $results;
    }

    $multiHandle = curl_multi_init();
    $handles = array();

    foreach ($urlsById as $id => $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, MULTI_STATUS_CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, MULTI_STATUS_TIMEOUT);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        curl_multi_add_handle($multiHandle, $ch);
        $handles[$id] = $ch;
    }

    $running = null;
    do {
        curl_multi_exec($multiHandle, $running);
        curl_multi_select($multiHandle);
    } while ($running > 0);

    foreach ($handles as $id => $ch) {
        $decoded = null;
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response = curl_multi_getcontent($ch);

        if ($response !== false && $response !== null && $httpCode === 200) {
            $data = json_decode($response, true);
            if (is_array($data)) {
                $decoded = $data;
            }
        }

        curl_multi_remove_handle($multiHandle, $ch);
        curl_close($ch);

        $results[$id] = $decoded;
    }

    curl_multi_close($multiHandle);

    return $results;
}

function normalizeServerStatus($data)
{
    $default = array('status' => 'offline', 'players' => 0, 'active' => 0, 'online' => 0);

    if (!is_array($data) || !isset($data['players'])) {
        return $default;
    }

    return array(
        'status' => isset($data['status']) ? $data['status'] : 'online',
        'players' => (int)$data['players'],
        'active' => isset($data['active']) ? (int)$data['active'] : 0,
        'online' => isset($data['online']) ? (int)$data['online'] : 0
    );
}

function normalizeServerNewsbox($data)
{
    $default = array(
        'online_users' => 0,
        'speed' => '-',
        'troop_speed' => '-',
        'evasion_speed' => '-',
        'map_size' => '-',
        'village_exp' => '-',
        'protection_hours' => '-',
        'medal_interval' => '-',
        'start_date' => '-',
        'peace_system' => '-',
        'best_player' => '-'
    );

    if (!is_array($data)) {
        return $default;
    }

    $result = $default;
    foreach ($default as $key => $unused) {
        if (isset($data[$key]) && $data[$key] !== '') {
            $result[$key] = $data[$key];
        }
    }

    return $result;
}

/*
 * Helpere de afisare pentru cardurile de newsbox: cand un world nu a
 * raspuns (ex. inca nu are endpoint-ul ?server_newsbox=1 activ), toate
 * campurile raman pe placeholder-ul '-' din normalizeServerNewsbox(). Fara
 * aceste helpere, sufixele (x / ore) s-ar lipi de placeholder si ar iesi
 * afisari confuze de genul "-x" in loc de un simplu "-".
 */
function mi_newsbox_value($value, $suffix = '')
{
    $value = (string)$value;
    if ($value === '-' || $value === '') {
        return '-';
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . $suffix;
}

function mi_newsbox_map_size($value)
{
    $value = (string)$value;
    if ($value === '-' || $value === '') {
        return '-';
    }
    $escaped = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    return $escaped . 'x' . $escaped;
}

/*
 * Cache scurt pentru fetch-urile intre worlds (status + newsbox).
 * Fara asta, FIECARE vizitator pe pagina asta declanseaza N cereri HTTP
 * catre celelalte worlds - inutil pentru date cvasi-statice si pune
 * sarcina degeaba pe serverele celorlalte worlds.
 */
function getCachedJson($cacheFile, $ttlSeconds, callable $fetcher)
{
    if (is_readable($cacheFile) && (time() - filemtime($cacheFile)) < $ttlSeconds) {
        $cached = json_decode(file_get_contents($cacheFile), true);

        if (is_array($cached)) {
            return $cached;
        }
        // Anomalie: fisierul de cache exista dar nu e JSON valid - trecem
        // peste el si reluam fetch-ul live in loc sa incredem date corupte.
    }

    $data = $fetcher();

    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    // Best-effort: daca var/cache nu e writable, pur si simplu nu se
    // cacheaza si fiecare request face fetch live.
    @file_put_contents($cacheFile, json_encode($data));

    return $data;
}

define('MULTI_STATUS_CACHE_TTL', 30);
define('MULTI_NEWSBOX_CACHE_TTL', 60);

$serverStats = getCachedJson(__DIR__ . '/var/cache/server_status.json', MULTI_STATUS_CACHE_TTL, function () use ($servers) {
    $urls = array();
    foreach ($servers as $id => $server) {
        $urls[$id] = $server['status'];
    }

    $raw = fetchJsonFromServers($urls);

    $stats = array();
    foreach ($raw as $id => $data) {
        $stats[$id] = normalizeServerStatus($data);
    }

    return $stats;
});

$serverNewsboxes = getCachedJson(__DIR__ . '/var/cache/server_newsbox.json', MULTI_NEWSBOX_CACHE_TTL, function () use ($servers) {
    $urls = array();
    foreach ($servers as $id => $server) {
        $urls[$id] = $server['newsbox'];
    }

    $raw = fetchJsonFromServers($urls);

    $newsboxes = array();
    foreach ($raw as $id => $data) {
        $newsboxes[$id] = normalizeServerNewsbox($data);
    }

    return $newsboxes;
});

foreach ($servers as $serverId => $server) {
    $servers[$serverId]['stats'] = isset($serverStats[$serverId])
        ? $serverStats[$serverId]
        : normalizeServerStatus(null);

    $servers[$serverId]['newsbox'] = isset($serverNewsboxes[$serverId])
        ? $serverNewsboxes[$serverId]
        : normalizeServerNewsbox(null);
}

/*
 * Pastreaza variabilele vechi pentru restul paginii legacy.
 * Contorul din lobby de mai jos foloseste suma tuturor worlds-urilor.
 */
$users = 0;
$active = 0;
$online = 0;

foreach ($servers as $server) {
    $users += $server['stats']['players'];
    $active += $server['stats']['active'];
    $online += $server['stats']['online'];
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo SERVER_NAME; ?></title>
	<link rel="shortcut icon" href="favicon.ico" />
	<link rel="stylesheet" type="text/css" href="gpack/travian/main.css" />
	<link rel="stylesheet" type="text/css" href="gpack/travian/flaggs.css" />
	<link rel="stylesheet" type="text/css" href="gpack/travian/main_en.css" />
	<meta name="content-language" content="<?php echo LANG; ?>" />
	<meta http-equiv="imagetoolbar" content="no" />
	<script src="mt-core.js" type="text/javascript"></script>
	<script src="new.js?22102017" type="text/javascript"></script>
	<script src="new2.js?22102017" type="text/javascript"></script>
	<style type="text/css">
		/* Server backgrounds are assigned inline below. */
		div.c2 {left:237px;}
		ul.c1 {position:absolute; left:0px; width: 686px;}

		/* Multi-instance "news" panel: one compact card per world, built
		   from each world's own ?server_newsbox=1 (its former in-game
		   Newsbox1). Scoped classes (mi-*) so nothing in main.css collides.
		   Panel lives OUTSIDE .secondarybox now (own full-width section
		   below the intro columns) so it can lay worlds out side by side
		   instead of stacking, and no longer stretches to match whichever
		   column (left/right) happens to be taller. */
		/* body.indexpage #content are padding-bottom:120px in main.css - gandit
		   pentru pagina veche, mai scurta. Acum ca panoul de worlds e ultimul
		   element din #content, acel padding lasa un gol mare inainte de footer.
		   Aceeasi regula (acelasi selector), declarata dupa link-ul catre main.css,
		   deci castiga la ordine in cascada fara sa fortez cu !important. */
		body.indexpage #content { padding-bottom:30px; }
		#mi-worlds-panel { width:830px; max-width:100%; margin:8px auto 0; box-sizing:border-box; text-align:left; }
		#mi-worlds-panel .mi-worlds-heading { margin:0 0 8px; padding-bottom:5px; font-size:16px; color:#4a3a1f; border-bottom:2px solid #7a1220; }
		.mi-worlds-news { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:10px; }
		/* Lista noua din News (Templates/indexnews.tpl), stilizata sa semene cu
		   lista "About the game:" din stanga (acelasi indent/line-height). */
		#newsbox .news ul { margin:5px 0 0 15px; padding:0; }
		#newsbox .news li { line-height:19px; padding-bottom:4px; }
		.mi-world-card { border:1px solid #d8c9a3; border-radius:4px; background:#fdfbf5; overflow:hidden; width:100%; box-sizing:border-box; }
		.mi-world-card-title { margin:0; padding:5px 8px; background:#7a1220; color:#f5e9c8; font-size:12px; font-weight:bold; letter-spacing:.3px; text-align:center; }
		.mi-world-stats-grid { display:grid; grid-template-columns:1fr 1fr; gap:4px 6px; padding:7px; box-sizing:border-box; }
		.mi-world-stat { text-align:center; padding:4px 3px; background:#f4ecd8; border-radius:3px; box-sizing:border-box; min-width:0; white-space:normal; }
		.mi-world-stat-label { font-size:9px; color:#5b4a2f; text-transform:uppercase; letter-spacing:.2px; }
		.mi-world-stat-value { font-size:12px; font-weight:bold; color:#7a1220; margin-left:4px; overflow-wrap:break-word; }
		.mi-world-best-player { text-align:center; padding:5px 8px 7px; font-size:11px; border-top:1px solid #e6dcc0; }
	</style>
</head>

<body class="presto indexPage">
	<div class="wrapper">
		<div id="country_select">
			<div id="flags"></div>
			<script src="flaggen.js?a" type="text/javascript"></script>
			<script type="text/javascript">
			var region_list = new Array('Europe','America','Asia','Middle East','Africa','Oceania');
			show_flags('', '', region_list);
			</script>
		</div>
		<div id="header"><h1><?php echo $lang['index'][0][1]; ?></h1></div>
		<div id="navigation">
			<a href="index.php" class="home"><img src="img/x.gif" alt="Travian" /></a>
			<table class="menu">
				<tr>
					<td><a href="tutorial.php"><span><?php echo TUTORIAL; ?></span></a></td>
					<td><a href="anleitung.php"><span><?php echo $lang['index'][0][2]; ?></span></a></td>
					<td><a href="https://github.com/Shadowss/TravianZ/discussions" target="_blank"><span><?php echo FORUM; ?></span></a></td>
					<td><a href="?signup" class="signup_link mark"><span><?php echo $lang['register']; ?></span></a></td>
					<td><a href="?login" class="login_link"><span><?php echo LOGIN; ?></span></a></td>
				</tr>
			</table>
		</div>
		<?php
		if(T4_COMING==true){
		?>
		<div id="t4play">
		<a href="notification/">
		<img src="img/t4n/Teaser_Prelandingpage_EN.png" alt="Travian 4" />
		</a>
		</div>
		<?php } ?>
		<div id="register_now">
			<a href="?signup" class="signup_link"><?php echo $lang['register']; ?></a>
			<span><?php echo PLAY_NOW; ?></span>
		</div>
		<div id="content">
			<div class="grit">
				<div class="infobox">
					<div id="what_is_travian">
						<h2><?php echo $lang['index'][0][4]; ?></h2>
						<p><?php echo $lang['index'][0][5]; ?></p>
						<p class="play_now"><a href="?signup" class="signup_link"><?php echo $lang['index'][0][6]; ?></a></p>
					</div>
					<div id="player_counter">
						<table>
							<tbody>
								<tr>
									<th><?php echo $lang['index'][0][7]; ?>:</th>
									<td><?php echo $users; ?></td>
								</tr>
								<tr>
									<th><?php echo $lang['index'][0][8]; ?>:</th>
									<td><?php echo $active; ?></td>
								</tr>
								<tr>
									<th><?php echo $lang['index'][0][9]; ?>:</th>
									<td><?php echo $online; ?></td>
								</tr>
							</tbody>
						</table>
					</div>
					<div id="about_the_game">
						<h2><?php echo $lang['index'][0][10]; ?>:</h2>
						<ul>
							<li><?php echo $lang['index'][0][11]; ?></li>
							<li><?php echo $lang['index'][0][12]; ?></li>
							<li><?php echo $lang['index'][0][13]; ?></li>
						</ul>
					</div>
				</div>
				<div class="secondarybox">
					<div id="screenshots">
						<h2><?php echo SCREENSHOTS; ?></h2>
						<a href="#last" class="navi prev dynamic_btn"><img class="dynamic_btn" src="img/x.gif" alt="previous" /></a>
						<div id="screenshots_preview">
							<ul id="screenshot_list" class="c1">
								<li><a href="#"><img src="img/un/s/s1s.jpg" alt="Screenshot" /></a></li>
								<li><a href="#"><img src="img/un/s/s2s.jpg" alt="Screenshot" /></a></li>
								<li><a href="#"><img src="img/un/s/s4s.jpg" alt="Screenshot" /></a></li>
								<li><a href="#"><img src="img/un/s/s3s.jpg" alt="Screenshot" /></a></li>
								<li><a href="#"><img src="img/un/s/s5s.jpg" alt="Screenshot" /></a></li>
								<li><a href="#"><img src="img/un/s/s7s.jpg" alt="Screenshot" /></a></li>
								<li><a href="#"><img src="img/un/s/s8s.jpg" alt="Screenshot" /></a></li>
							</ul>
						</div><a href="#next" class="navi next"><img class="dynamic_btn" src="img/x.gif" alt="next" /></a>
					</div>
					<div id="newsbox">
						<h2><?php echo NEWS; ?></h2>
						<div class="news"><?php include ("Templates/indexnews.tpl"); ?></div>
					</div>
				</div>
			</div>
			<div class="clear"></div>
			<div id="mi-worlds-panel">
				<h2 class="mi-worlds-heading">Available Worlds</h2>
				<div class="mi-worlds-news">
					<?php foreach ($servers as $server) { ?>
						<div class="mi-world-card">
							<h3 class="mi-world-card-title"><?php echo htmlspecialchars($server['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
							<div class="mi-world-stats-grid">
								<div class="mi-world-stat">
									<span class="mi-world-stat-label"><?php echo TZ_ONLINE_USERS; ?></span>:
									<span class="mi-world-stat-value"><?php echo (int)$server['newsbox']['online_users']; ?></span>
								</div>
								<div class="mi-world-stat">
									<span class="mi-world-stat-label"><?php echo CONF_SERV_SERVSPEED; ?></span>:
									<span class="mi-world-stat-value"><?php echo mi_newsbox_value($server['newsbox']['speed'], 'x'); ?></span>
								</div>
								<div class="mi-world-stat">
									<span class="mi-world-stat-label"><?php echo CONF_SERV_TROOPSPEED; ?></span>:
									<span class="mi-world-stat-value"><?php echo mi_newsbox_value($server['newsbox']['troop_speed'], 'x'); ?></span>
								</div>
								<div class="mi-world-stat">
									<span class="mi-world-stat-label"><?php echo CONF_SERV_EVASIONSPEED; ?></span>:
									<span class="mi-world-stat-value"><?php echo mi_newsbox_value($server['newsbox']['evasion_speed']); ?></span>
								</div>
								<div class="mi-world-stat">
									<span class="mi-world-stat-label"><?php echo CONF_SERV_MAPSIZE; ?></span>:
									<span class="mi-world-stat-value"><?php echo mi_newsbox_map_size($server['newsbox']['map_size']); ?></span>
								</div>
								<div class="mi-world-stat">
									<span class="mi-world-stat-label"><?php echo TZ_VILLAGE_EXP; ?></span>:
									<span class="mi-world-stat-value"><?php echo mi_newsbox_value($server['newsbox']['village_exp']); ?></span>
								</div>
								<div class="mi-world-stat">
									<span class="mi-world-stat-label"><?php echo TZ_BEGINNERS_PROT; ?></span>:
									<span class="mi-world-stat-value"><?php echo mi_newsbox_value($server['newsbox']['protection_hours'], ' ' . TZ_HRS); ?></span>
								</div>
								<div class="mi-world-stat">
									<span class="mi-world-stat-label"><?php echo CONF_SERV_MEDALINTERVAL; ?></span>:
									<span class="mi-world-stat-value"><?php echo mi_newsbox_value($server['newsbox']['medal_interval']); ?></span>
								</div>
								<div class="mi-world-stat">
									<span class="mi-world-stat-label"><?php echo TZ_SERVER_START; ?></span>:
									<span class="mi-world-stat-value"><?php echo mi_newsbox_value($server['newsbox']['start_date']); ?></span>
								</div>
								<div class="mi-world-stat">
									<span class="mi-world-stat-label"><?php echo CONF_SERV_PEACESYST; ?></span>:
									<span class="mi-world-stat-value"><?php echo mi_newsbox_value($server['newsbox']['peace_system']); ?></span>
								</div>
							</div>
							<div class="mi-world-best-player">
								<span class="mi-world-stat-label"><?php echo TZ_BEST_PLAYER; ?>:</span>
								<span class="mi-world-stat-value"><?php echo mi_newsbox_value($server['newsbox']['best_player']); ?></span>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
		<div id="footer">
			<div class="container">
				<ul class="menu">
					<li><a href="anleitung.php?s=3"><?php echo FAQ; ?></a>|</li>
					<li><a href="index.php?screenshots"><?php echo SCREENSHOTS; ?></a>|</li>
					<li><a href="spielregeln.php"><?php echo SPIELREGELN; ?></a>|</li>
					<li><a href="agb.php"><?php echo AGB; ?></a>|</li>
					<li><a href="impressum.php"><?php echo IMPRINT; ?></a></li>
					<li class="copyright">&copy; 2011-<?php echo date('Y'); ?> - TravianZ - All rights reserved</li>
				</ul>
			</div>
		</div>
	</div>
	<div id="login_layer" class="overlay">
		<div class="mask closer"></div>
		<div id="login_list" class="overlay_content">
			<h2><?php echo CHOOSE; ?></h2>
			<a href="#" class="closer"><img class="dynamic_img" alt="Close" src="img/un/x.gif" /></a>
			<ul class="world_list">
				<?php foreach ($servers as $server) { ?>
					<li class="w_big c3" style="background-image:url('img/en/welten/<?php echo htmlspecialchars($server['image'], ENT_QUOTES, 'UTF-8'); ?>');">
						<a href="<?php echo htmlspecialchars($server['login'], ENT_QUOTES, 'UTF-8'); ?>">
							<img class="w_button"
								src="img/un/x.gif"
								alt="<?php echo htmlspecialchars($server['name'], ENT_QUOTES, 'UTF-8'); ?>"
								title="<?php echo (int)$server['stats']['players']; ?> <?php echo PLAYERS; ?> | <?php echo (int)$server['stats']['active']; ?> <?php echo ACTIVE; ?> | <?php echo (int)$server['stats']['online']; ?> <?php echo ONLINE; ?>" />
						</a>
						<div class="label_players c0"><?php echo PLAYERS; ?>:</div>
						<div class="label_online c0"><?php echo ONLINE; ?>:</div>
						<div class="players c1"><?php echo (int)$server['stats']['players']; ?></div>
						<div class="online c1"><?php echo (int)$server['stats']['online']; ?></div>
					</li>
				<?php } ?>
			</ul>
			<div class="footer"></div>
		</div>
	</div>

	<div id="signup_layer" class="overlay">
		<div class="mask closer"></div>
		<div id="signup_list" class="overlay_content">
			<h2><?php echo CHOOSE; ?></h2>
			<a href="#" class="closer"><img class="dynamic_img" alt="Close" src="img/un/x.gif" /></a>
			<ul class="world_list">
				<?php foreach ($servers as $server) { ?>
					<li class="w_big c4" style="background-image:url('img/en/welten/<?php echo htmlspecialchars($server['image'], ENT_QUOTES, 'UTF-8'); ?>');">
						<a href="<?php echo htmlspecialchars($server['signup'], ENT_QUOTES, 'UTF-8'); ?>">
							<img class="w_button"
								src="img/un/x.gif"
								alt="<?php echo htmlspecialchars($server['name'], ENT_QUOTES, 'UTF-8'); ?>"
								title="<?php echo (int)$server['stats']['players']; ?> <?php echo PLAYERS; ?> | <?php echo (int)$server['stats']['active']; ?> <?php echo ACTIVE; ?> | <?php echo (int)$server['stats']['online']; ?> <?php echo ONLINE; ?>" />
						</a>
						<div class="label_players c0"><?php echo PLAYERS; ?>:</div>
						<div class="label_online c0"><?php echo ONLINE; ?>:</div>
						<div class="players c1"><?php echo (int)$server['stats']['players']; ?></div>
						<div class="online c1"><?php echo (int)$server['stats']['online']; ?></div>
					</li>
				<?php } ?>
			</ul>
			<div class="footer"></div>
		</div>
	</div>
	<div id="iframe_layer" class="overlay">
		<div class="mask closer"></div>
		<div class="overlay_content">
			<a href="#" class="closer"><img class="dynamic_img" alt="Close" src="img/un/x.gif" /></a>
			<h2><?php echo $lang['index'][0][2]; ?></h2>
			<div id="frame_box"></div>
			<div class="footer"></div>
		</div>
	</div>
	<div id="screenshot_layer" class="overlay">
		<div class="mask closer"></div>
		<div class="overlay_content">
			<h3><?php echo SCREENSHOTS; ?></h3>
			<a href="#" class="closer"><img class="dynamic_img" alt="Close" src="img/x.gif" /></a>
			<div class="screenshot_view">
				<h4 id="screen_hl"></h4>
				<img id="screen_view" src="img/x.gif" alt="Screenshot" name="screen_view" />
				<div id="screen_desc"></div>
			</div>
			<a href="#prev" class="navi prev" onclick="galarie.showPrev();"><img class="dynamic_img" src="img/x.gif" alt="previous" /></a>
			<a href="#next" class="navi next" onclick="galarie.showNext();"><img class="dynamic_img" src="img/x.gif" alt="next" /></a>
			<div class="footer"></div>
		</div>
	</div>
	<script type="text/javascript">
		var screenshots = [
			{'img':'img/en/s/s1.png','hl':"<?php echo $lang['screenshots']['title1']; ?>", 'desc':"<?php echo $lang['screenshots']['desc1']; ?>"},{'img':'img/en/s/s2.png','hl':"<?php echo $lang['screenshots']['title2']; ?>", 'desc':"<?php echo $lang['screenshots']['desc2']; ?>"},{'img':'img/en/s/s4.png','hl':"<?php echo $lang['screenshots']['title3']; ?>", 'desc':"<?php echo $lang['screenshots']['desc3']; ?>"},{'img':'img/en/s/s3.png','hl':"<?php echo $lang['screenshots']['title4']; ?>", 'desc':"<?php echo $lang['screenshots']['desc4']; ?>"},{'img':'img/en/s/s5.png','hl':"<?php echo $lang['screenshots']['title5']; ?>", 'desc':"<?php echo $lang['screenshots']['desc5']; ?>"},{'img':'img/en/s/s7.png','hl':"<?php echo $lang['screenshots']['title6']; ?>", 'desc':"<?php echo $lang['screenshots']['desc6']; ?>"},{'img':'img/en/s/s8.png','hl':"<?php echo $lang['screenshots']['title7']; ?>", 'desc':"<?php echo $lang['screenshots']['desc7']; ?>"}
		];
		var galarie = new Fx.Screenshots('screen_view', 'screen_hl', 'screen_desc', screenshots);
	<?php
	    if (isset($_GET['signup'])) {
	?>
		window.addEvent('domready', function() {
			$$('.signup_link').fireEvent('click');
		});
	<?php
	   }
	?>

	<?php
    	if (isset($_GET['login'])) {
	?>
		window.addEvent('domready', function() {
    		$$('.login_link').fireEvent('click');
    	});
	<?php
	   }
	?>
	</script>
</body>
</html>
