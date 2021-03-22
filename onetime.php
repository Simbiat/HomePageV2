<?php
require_once './backend/security.php';
require_once './backend/fftracker.php';
//var_dump(lodestone_grab("22517998136913132", "linkshell"));

function cachedel($mask) {
	global $siteconfig;
	foreach(glob($siteconfig['cachedir'].$mask) as $file){
		if(is_file($file)) {
			@unlink($file);
		}
	}
}
function siteconfig() {
	$siteconfig = array();
	$siteconfig['title'] = "Simbiat Universe";
	$siteconfig['usecache'] = false;
	$siteconfig['cachetime'] = 86400;
	$siteconfig['cachedir'] = getcwd()."/backend/cache/";
	$siteconfig['poemsmusic'] = "/poems/";
	$siteconfig['imgintpath'] = "/frontend/images/";
	$siteconfig['linkiconspath'] = "/frontend/images/linkicons/";
	
	$siteconfig['itemsperpage'] = 20;
	
	$siteconfig['newscats'] = array (
		"index",
		"poems",
		"stories",
		"news",
		"snippets",
		"changelog",
		"videos",
	);
	
	$siteconfig['servicenames'] = array (
		"bic",
		"fftracker",
		"musiclib",
		"todo",
		"idealist",
		"silversteam",
		"budget",
		"tvtracker",
		"multitool",
	);
	
	$siteconfig['navcollapsed'] = array (
		"Planned",
		"Legacy",
		"Sitemaps",
		"Atom Feeds",
		"Contacts",
		"Useful Links",
	);
	
	$siteconfig['services'] = array(
		"FF Tracker" => "/FFTracker",
		"FFTracker" => "/FFTracker",
		"FFXIV Tracker" => "/FFTracker",
		"Final Fantasy XIV Tracker" => "/FFTracker",
		"Free Company Tracker" => "/GitHub/XIV-FC-Page",
		"XIVSync" => "https://github.com/viion/XIVPads-LodestoneAPI",
		"Lodestone Parser" => "https://github.com/viion/lodestone-php",
		"XIVDB" => "http://xivdb.com/",
		"Steam" => "http://store.steampowered.com",
		"(Final Fantasy XIV|A Realm Reborn|FFXIV|Heavensward|Stormblood|Lodestone)" => "http://eu.finalfantasyxiv.com/lodestone",
		"SilverSteam" => "/SilverSteam",
		"DarkSteam" => "/SilverSteam",
		"eDocuments" => "/eDocuments",
		"Hash Checker" => "/snip/4-Hash_Checker",
		"HTA Logging" => "/snip/7-HTA_Logging",
		"Global Functions" => "/snip/3-Global_Functions",
		"Hash Updater" => "/snip/5-Hash_Updater",
		"HTA Launcher" => "/snip/9-HTA_Launcher",
		"Hash Checker" => "/snip/4-Hash_Checker",
		"SteamDB" => "https://steamdb.info",
		"EnhancedSteam" => "http://www.enhancedsteam.com",
		"BIC Library" => "/BIC",
		"BIC" => "/BIC",
		"ÁÈÊ" => "/BIC",
		"Yandex" => "https://yandex.ru/",
		"Google" => "https://www.google.com",
	);
	return $siteconfig;
}
$siteconfig = siteconfig();

$datam = lodestone_grab(17257994, "character");
$datam = lodestone_convert($datam, "character");
echo '<pre>' . var_export($datam, true) . '</pre>';

exit;
$dbh = dbconnect();
ini_set('memory_limit', '-1');
$members = dbselect("SELECT * FROM `ff__characters` WHERE server NOT IN ('Adamantoise', 'Balmung', 'Cactuar', 'Coeurl', 'Faerie', 'Gilgamesh', 'Goblin', 'Jenova', 'Mateus', 'Midgardsormr', 'Sargatanas', 'Siren', 'Zalera', 'Cerberus', 'Lich', 'Louisoix', 'Moogle', 'Odin', 'Omega', 'Phoenix', 'Ragnarok', 'Shiva', 'Zodiark', 'Aegis', 'Atomos', 'Carbuncle', 'Garuda', 'Gungnir', 'Kujata', 'Ramuh', 'Tonberry', 'Typhon', 'Unicorn', 'Alexander', 'Bahamut', 'Durandal', 'Fenrir', 'Ifrit', 'Ridill', 'Tiamat', 'Ultima', 'Valefor', 'Yojimbo', 'Zeromus', 'Anima', 'Asura', 'Belias', 'Chocobo', 'Hades', 'Ixion', 'Mandragora', 'Masamune', 'Pandaemonium', 'Shinryu', 'Titan', 'Behemoth', 'Brynhildr', 'Diabolos', 'Excalibur', 'Exodus', 'Famfrit', 'Hyperion', 'Lamia', 'Leviathan', 'Malboro', 'Ultros')");
foreach ($members as $member) {
	$datam = lodestone_grab($member['id'], "character");
	if ($datam != 404 && $datam != false) {
		$datam = lodestone_convert($datam, "character");
		//echo '<pre>' . var_export($datam, true) . '</pre>';
		character_update($datam, false, false);
	}
}

/*
$members = dbselect("SELECT id, levels FROM ff__characters WHERE `levels` LIKE '%Arcanist%' LIMIT 100000");
foreach ($members as $member) {
	$json = json_decode($member['levels'], true);
	$json['Paladin'] = $json['Gladiator'];
	unset($json['Gladiator']);
	$json['Monk'] = $json['Pugilist'];
	unset($json['Pugilist']);
	$json['Warrior'] = $json['Marauder'];
	unset($json['Marauder']);
	$json['Dragoon'] = $json['Lancer'];
	unset($json['Lancer']);
	$json['Bard'] = $json['Archer'];
	unset($json['Archer']);
	$json['Ninja'] = $json['Rogue'];
	unset($json['Rogue']);
	$json['White Mage'] = $json['Conjurer'];
	unset($json['Conjurer']);
	$json['Black Mage'] = $json['Thaumaturge'];
	unset($json['Thaumaturge']);
	$json['Scholar'] = $json['Arcanist'];
	$json['Summoner'] = $json['Arcanist'];
	unset($json['Arcanist']);
	$query = "UPDATE `ff__characters` SET `levels`=".$dbh->quote(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT))." WHERE id=".$member['id'];
	dbedit($query);
}
*/
echo "Finished";
dbclose($dbh);
?>