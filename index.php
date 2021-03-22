<?php
$maintenance = false;
$globalusecache = true;

#Backend preparetion
session_save_path(getcwd()."/backend/sessions/");
function siteconfig() {
	global $globalusecache;
	$siteconfig = array();
	$siteconfig['title'] = "Simbiat Universe";
	$siteconfig['usecache'] = $globalusecache;
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
		"БИК" => "/BIC",
		"Yandex" => "https://yandex.ru/",
		"Google" => "https://www.google.com",
	);
	return $siteconfig;
}
$siteconfig = siteconfig();
require_once './backend/security.php';

#Preparing for cache usage and gzipping
$urlname = strtolower(preg_replace("/[\<\>\:\"\\\\\|\?\*]/", "_", rtrim(ltrim($_SERVER['REQUEST_URI'], "/"), "/")).".csh");
if ($urlname == ".csh") {
	$urlname = "index.csh";
}
if (empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
	$gzipsupp = false;
} else {
	$gzipsupp = strpos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip');
}
cachecleaner($siteconfig['cachetime']);

header('Cache-Control: no-cache, no-store, must-revalidate');
#Outputting cache if present and fresh and browser supports gzip
if ($siteconfig['usecache'] === true && $gzipsupp !== false && file_exists($siteconfig['cachedir'].$urlname) && time()-filemtime($siteconfig['cachedir'].$urlname) < $siteconfig['cachetime']) {
	ob_start('ob_gzhandler');
	$filecontent = gzdecode(file_get_contents($siteconfig['cachedir'].$urlname));
	#Getting columns
	$column2 = blocks();
	$filecontent = str_replace("\$column2", $column2, $filecontent);
	header('Content-Length: '.strlen($filecontent));
	echo $filecontent;
	ob_end_flush();
	exit;
}
if ($maintenance == true) {
	echo "The site is under maintenance. Sorry for any inconvenience.";
	exit;
}

#Determening what to run
if (empty($_GET['service'])) {
	$pagetype = "index";
} else {
	$pagetype = strtolower($_GET['service']);
}
#Check if we have a page
if (empty($_GET['page'])) {
	$page = 1;
} else {
	$page = digitalizer($_GET['page']);
}
if (empty($_GET['lang'])) {
	$lang = "";
} else {
	$lang = $_GET['lang'];
}

#Emptying output, just in case
$finaloutput = "";

#Getting data from DataBase
$dbh = dbconnect();

$pagination = "";
$breadcrumbs = array();
$about = "";
$content = "";
if (in_array($pagetype, $siteconfig['newscats'])) {
	if (empty($_GET['id'])) {		
		if ($pagetype == "index") {
			$count = ceil(dbselect("SELECT `id` FROM `news`", false, true)/$siteconfig['itemsperpage']);
			$query = "SELECT * FROM `news` ORDER BY `date` DESC LIMIT ".$siteconfig['itemsperpage']." OFFSET ".((min($page, $count)-1)*$siteconfig['itemsperpage']);
			$basetitle = "News (page ".min($page, $count).")";
			$title = str_replace(" (page 1)", "", $basetitle)." <= ".$siteconfig['title'];
		} else {
			$basequery = "SELECT * FROM `news` WHERE `type` = ".dbquote(ucfirst($pagetype));
			if ($lang != "") {
				$basequery .= " AND `language` = ".dbquote($lang);
			}
			$count = ceil(dbselect($basequery, false, true)/$siteconfig['itemsperpage']);
			$query = $basequery." ORDER BY `date` DESC LIMIT ".$siteconfig['itemsperpage']." OFFSET ".((min($page, $count)-1)*$siteconfig['itemsperpage']);
			$basetitle = ucfirst($pagetype);
			if ($lang == "eng") {
				$basetitle = "English ".$basetitle;
			} elseif ($lang == "rus") {
				$basetitle = "Russian ".$basetitle;
			} elseif ($lang != "" && $lang != "eng" && $lang != "rus") {
				$basetitle = strtoupper($lang)." ".$basetitle;
			}
			$title = str_replace(" (page 1)", "", $basetitle." (page ".min($page, $count).") <= ".$siteconfig['title']);
		}
		$pagination = pagination($count, $page, $pagetype."/".$lang);
		$news = article($query);
		$content = $news['content'];
		$breadcrumbs["level1"] = "/".$pagetype;
		if (!empty($lang)) {
			$breadcrumbs["level2"] = "/".$lang;
			$breadcrumbs["content"] = array(array("link" => "/".$pagetype, "name" => ucfirst($pagetype)), array("link" => "/".$pagetype.$breadcrumbs["level2"], "name" => langconv($lang)), array("link" => (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", "name" => "Page ".$page, "show" => true));
		} else {
			if ($pagetype == "index") {
				$breadcrumbs["content"] = array(array("name" => ""), array("link" => (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", "name" => "Page ".$page, "show" => true));
			} else {
				$breadcrumbs["content"] = array(array("link" => "/".$pagetype, "name" => ucfirst($pagetype)), array("link" => (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", "name" => "Page ".$page, "show" => true));
			}
		}
	} else {
		$id = digitalizer($_GET['id']);
		$title = ucfirst($pagetype)." <= ".$siteconfig['title'];
		$query = "SELECT * FROM `news` WHERE `id`=".dbquote($id)." AND `type` = ".dbquote(ucfirst($pagetype));
		$article = article($query);
		if ($article['content'] == "") {
			header( 'Location: /', true, 301);
			exit;
		} else {
			$query = "SELECT `id`, `title`, `description` as `text`, \"0\" as `collapsed` FROM `news` WHERE `id`=".dbquote($id);
			$about = about($query, true);
			if (!empty($about['content'])) {
				$about = $about['content'];
			} else {
				$about = "";
			}
			$content = $article['content'];
			$title = htmlspecialchars($article['title'])." <= ".$siteconfig['title'];
		}
		$breadcrumbs["level1"] = "/".$pagetype;
		if (!empty($article['language'])) {
			$breadcrumbs["level2"] = "/".$article['language'];
			$breadcrumbs["content"] = array(array("link" => "/".$pagetype, "name" => ucfirst($pagetype)), array("link" => "/".$pagetype.$breadcrumbs["level2"], "name" => langconv($article['language'])), array("link" => (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", "name" => $article['title'], "show" => true));
		} else {
			$breadcrumbs["content"] = array(array("link" => "/".$pagetype, "name" => ucfirst($pagetype)), array("link" => (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", "name" => $article['title'], "show" => true));
		}
	}
} elseif (in_array($pagetype, $siteconfig['servicenames'])) {
	$query = "SELECT * FROM `services` WHERE `name`=".dbquote($pagetype);
	$aboutq = about($query);
	$about = $aboutq['content'];
	$title = $aboutq['title']. " <= ".$siteconfig['title'];
	$breadcrumbs["level1"] = "/".$pagetype;
	if ($pagetype == "bic") {
		require_once './backend/bic.php';
		if (isset($_GET['bic'])) {
			showbics($_GET['bic']);
		} else {
			$content = showbics();
		}
		$breadcrumbs["content"] = array(array("link" => "/".$pagetype, "name" => $aboutq['title']));
	} elseif ($pagetype == "fftracker") {
		require_once './backend/fftracker.php';
		if (!empty($_GET['search'])) {
			fc_search($_GET['search']);
		} elseif (empty($_GET['type']) || empty($_GET['id'])) {
			$info = fc_list();
			$content = $info[0];
		} elseif (!empty($_GET['type']) && !empty($_GET['id'])) {
			if (isset($_GET['rank'])) {
				$info = ff_id_gateway($_GET['id'], $_GET['type'], $_GET['rank']);
			} else {
				$info = ff_id_gateway($_GET['id'], $_GET['type']);
			}
			$content = $content . (empty($info['content']) ? "" : $info['content']);
			$title = (empty($info['title']) ? "" : $info['title'])." <= ".$siteconfig['title'];
		}
		$breadcrumbs["content"] = array(array("link" => "/".$pagetype, "name" => $aboutq['title']), array("name" => ""), array("name" => ""));
		$breadcrumbs["content"] = array_merge($breadcrumbs["content"], $info['crumbs']);
	} else {
		$breadcrumbs["level1"] = "/".$pagetype;
		$breadcrumbs["content"] = array(array("link" => "/".$pagetype, "name" => ucfirst($pagetype)));
	}
} elseif ($pagetype == "prices") {
	$content = prices();
	$title = "Prices <= ".$siteconfig['title'];
	$breadcrumbs["level1"] = "/".$pagetype;
	$breadcrumbs["content"] = array(array("link" => "/".$pagetype, "name" => ucfirst($pagetype)));
} elseif ($pagetype == "simbiat") {
	$content = "";
	$content .= "<div class=\"general_table_block\">";
	$content .= "<div class=\"center-text w3-small w3-green\">Placeholder</div>";
	$content .= "<div class=\"article justify-text\">";
	$content .= "Placeholder";
	$content .= "</div><div class=\"w3-small w3-green\">&nbsp;</div></div><br>";
	$title = ucfirst($pagetype)." <= ".$siteconfig['title'];
	$breadcrumbs["level1"] = "/".$pagetype;
	$breadcrumbs["content"] = array(array("link" => "/".$pagetype, "name" => ucfirst($pagetype)));
} else {
	header( 'Location: /', true, 301);
	exit;
}

$content = $about.$content;

#h1
$h1 = trim(explode("<=", $title)[0]);
#Cleaning up title for Index
if ($pagetype == "index") {
	$retitle = explode("<=", $title);
	$title = trim($retitle[(count($retitle)-1)]);
}

#Getting OGP tags
$url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$ogimage = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/frontend/images/ogimages/";
#OGP Image
if ($pagetype == "bic") {
	$ogimage .= "bic-1200x630.png";
} elseif ($pagetype == "fftracker") {
	if (!empty($info['ogimage'])) {
		$ogimage = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$info['ogimage'];
	} else {
		$ogimage .= "fftracker-1200x630.png";
	}
} else {
	$ogimage .= "simbiat-1200x630.png";
}
#OGP Desription, Keywords and Extras
$ogdesc = "Personal webpage of Dmitry Kustov aka Simbiat, also providing services like Final Fantasy XIV Free Company Tracker and BIC Library.";
$keywords = "Simbiat, Coding, Poems, Stories, Videos, Personal Page, Final Fantasy XIV Free Company Tracker, SilverSteam, DarkSteam, BIC Library";
$ogextra = "";
if ($pagetype == "bic") {
	$ogdesc = "Representation of Bank Identification Codes from Central Bank of Russia with filtering options.";
	$keywords .= ", Bank Library, BIC, SWIFT, Bank Identification Code, Bank, Library, Search, PZN, UER, REAL";
} elseif ($pagetype == "fftracker") {
	$ogdesc = "Tracker for Final Fantasy XIV Free Companies to show their basic information, standings history and suggest possible promotions for their members. Also tracks members' changes.";
	$keywords .= ", Final Fantasy XIV, Realm Reborn, Heavensward, Bloodstorm, Gridania, Twin Adder, Maelstrom, Limsa Lominsa, Free Company, Lodestone, Moogle, Au Ra, Mi'qote, Tracker, Character progression, Name history, Company members, Level history";
	if (empty($info['name']) == false) {
		$profname = explode(" ", $info['name']);
		$ogextra = "<meta property=\"og:type\" content=\"profile\" /><meta property=\"profile:first_name\" content=\"".htmlspecialchars($profname[0])."\" /><meta property=\"profile:last_name\" content=\"".htmlspecialchars($profname[1])."\" /><meta property=\"profile:username\" content=\"".htmlspecialchars($info['name'])."\" /><meta property=\"profile:gender\" content=\"".htmlspecialchars($info['gender'])."\" />";
	}
} elseif ($pagetype == "silversteam") {
	$ogdesc = "Alternative representation of Steam Library.";
	$keywords .= ", Steam, Games, Valve";
} elseif ($pagetype == "poems") {
	$ogdesc = "Poems written by Dmitry Kustov.";
	if (!empty($article)) {
		$ogextra = "<meta property=\"og:type\" content=\"article\" /><meta property=\"article:published_time\" content=\"".date("c", $article['time'])."\" /><meta property=\"article:modified_time\" content=\"".date("c", $article['time'])."\" />";
	}
} elseif ($pagetype == "stories") {
	$ogdesc = "Stories written by Dmitry Kustov.";
	if (!empty($article)) {
		$ogextra = "<meta property=\"og:type\" content=\"article\" /><meta property=\"article:published_time\" content=\"".date("c", $article['time'])."\" /><meta property=\"article:modified_time\" content=\"".date("c", $article['time'])."\" />";
	}
} elseif ($pagetype == "news") {
	$ogdesc = "News of Simbiat Universe website.";
	if (!empty($article)) {
		$ogextra = "<meta property=\"og:type\" content=\"article\" /><meta property=\"article:published_time\" content=\"".date("c", $article['time'])."\" /><meta property=\"article:modified_time\" content=\"".date("c", $article['time'])."\" />";
	}
} elseif ($pagetype == "snippets") {
	$ogdesc = "Code snippets written by Dmitry Kustov.";
	if (!empty($article)) {
		$ogextra = "<meta property=\"og:type\" content=\"article\" /><meta property=\"article:published_time\" content=\"".date("c", $article['time'])."\" /><meta property=\"article:modified_time\" content=\"".date("c", $article['time'])."\" />";
	}
} elseif ($pagetype == "changelog") {
	$ogdesc = "Log of changes made to Simbiat Universe website and its services.";
	if (!empty($article)) {
		$ogextra = "<meta property=\"og:type\" content=\"article\" /><meta property=\"article:published_time\" content=\"".date("c", $article['time'])."\" /><meta property=\"article:modified_time\" content=\"".date("c", $article['time'])."\" />";
	}
} elseif ($pagetype == "videos") {
	$ogdesc = "Videos made by Dmitry Kustov.";
	if (!empty($article)) {
		$ogextra = "<meta property=\"og:type\" content=\"article\" /><meta property=\"article:published_time\" content=\"".date("c", $article['time'])."\" /><meta property=\"article:modified_time\" content=\"".date("c", $article['time'])."\" />";
	}
}

#Getting favicons
if ($pagetype == "bic") {
	$favicon = "<link rel=\"icon\" href=\"\$domain/frontend/images/favicons/bic.ico\" type=\"image/x-icon\" />";
} elseif ($pagetype == "fftracker") {
	if (!empty($info['crest'])) {
		$favicon = "
			<!-- Apple favicons -->
			<link rel=\"apple-touch-icon\" sizes=\"128x128\" href=\"\$domain".$info['crest']."\" />
			
			<!-- Regular favicons -->
			<link rel=\"icon\" type=\"image/png\" href=\"\$domain".$info['crest']."\" sizes=\"128x128\" />
		";
	} else {
		$favicon = "
			<!-- Apple favicons -->
			<link rel=\"apple-touch-icon\" sizes=\"57x57\" href=\"\$domain/frontend/images/favicons/fftracker-57x57.png\" />
			<link rel=\"apple-touch-icon\" sizes=\"114x114\" href=\"\$domain/frontend/images/favicons/fftracker-114x114.png\" />
			<link rel=\"apple-touch-icon\" sizes=\"72x72\" href=\"\$domain/frontend/images/favicons/fftracker-72x72.png\" />
			<link rel=\"apple-touch-icon\" sizes=\"144x144\" href=\"\$domain/frontend/images/favicons/fftracker-144x144.png\" />
			<link rel=\"apple-touch-icon\" sizes=\"60x60\" href=\"\$domain/frontend/images/favicons/fftracker-60x60.png\" />
			<link rel=\"apple-touch-icon\" sizes=\"120x120\" href=\"\$domain/frontend/images/favicons/fftracker-120x120.png\" />
			<link rel=\"apple-touch-icon\" sizes=\"76x76\" href=\"\$domain/frontend/images/favicons/fftracker-76x76.png\" />
			<link rel=\"apple-touch-icon\" sizes=\"152x152\" href=\"\$domain/frontend/images/favicons/fftracker-152x152.png\" />
	
			<!-- Regular favicons -->
			<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/fftracker-16x16.png\" sizes=\"16x16\" />
			<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/fftracker-32x32.png\" sizes=\"32x32\" />
			<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/fftracker-96x96.png\" sizes=\"96x96\" />
			<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/fftracker-128x128.png\" sizes=\"128x128\" />
			<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/fftracker-196x196.png\" sizes=\"196x196\" />
			<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/fftracker-228x228.png\" sizes=\"228x228\" />
			<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/fftracker.png\" sizes=\"240x240\" />
		";
	}
} else {
	$favicon = "
		<!-- Apple favicons -->
		<link rel=\"apple-touch-icon\" sizes=\"57x57\" href=\"\$domain/frontend/images/favicons/simbiat-57x57.png\" />
		<link rel=\"apple-touch-icon\" sizes=\"114x114\" href=\"\$domain/frontend/images/favicons/simbiat-114x114.png\" />
		<link rel=\"apple-touch-icon\" sizes=\"72x72\" href=\"\$domain/frontend/images/favicons/simbiat-72x72.png\" />
		<link rel=\"apple-touch-icon\" sizes=\"144x144\" href=\"\$domain/frontend/images/favicons/simbiat-144x144.png\" />
		<link rel=\"apple-touch-icon\" sizes=\"60x60\" href=\"\$domain/frontend/images/favicons/simbiat-60x60.png\" />
		<link rel=\"apple-touch-icon\" sizes=\"120x120\" href=\"\$domain/frontend/images/favicons/simbiat-120x120.png\" />
		<link rel=\"apple-touch-icon\" sizes=\"76x76\" href=\"\$domain/frontend/images/favicons/simbiat-76x76.png\" />
		<link rel=\"apple-touch-icon\" sizes=\"152x152\" href=\"\$domain/frontend/images/favicons/simbiat-152x152.png\" />

		<!-- Regular favicons -->
		<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/simbiat-16x16.png\" sizes=\"16x16\" />
		<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/simbiat-32x32.png\" sizes=\"32x32\" />
		<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/simbiat-96x96.png\" sizes=\"96x96\" />
		<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/simbiat-128x128.png\" sizes=\"128x128\" />
		<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/simbiat-196x196.png\" sizes=\"196x196\" />
		<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/simbiat-228x228.png\" sizes=\"228x228\" />
		<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/simbiat-256x256.png\" sizes=\"256x256\" />
		<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/simbiat-512x512.png\" sizes=\"512x512\" />
		<link rel=\"icon\" type=\"image/png\" href=\"\$domain/frontend/images/favicons/simbiat.png\" sizes=\"630x630\" />
	";
}

#Getting navbars
$sidenavbar = sidenavbar($breadcrumbs);

dbclose($dbh);

#Preparing the output
$template = file_get_contents("./frontend/template.html");
$finaloutput = $template;

#Updating template
$find = array("\$currentyear", "\$content", "\$sidenavbar", "\$title", "\$h1", "\$favicon", "\$url", "\$ogimage", "\$ogdesc", "\$ogextra", "\$keywords", "\$pagination");
$replace = array("-".date("Y", time()), $content, $sidenavbar, $title, $h1, $favicon, $url, $ogimage, $ogdesc, $ogextra, $keywords, $pagination);
$finaloutput = str_replace($find, $replace, $finaloutput);
$finaloutput = str_replace("\$domain", (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]", $finaloutput);

#Outputting the page
cacheout($finaloutput);

#Functions

#Social buttons
function socialbtns($url, $title) {
	$output = "Share via ";
	$output .= "<a href=\"".(isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$url."\" title=\"".$title."\" class=\"bookmark-this-page\"><img src=\"/frontend/images/social/bookmark.png\" class=\"pointer navicon social-btn-left-margin\" title=\"Your own browser's favorites\">";
	$output .= "<a href=\"javascript:window.open('https://facebook.com/sharer/sharer.php?u=".(isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$url."&t=".htmlspecialchars($title)."','Share this','width=640,height=480,location=no,toolbar=no,menubar=no');void(0);\" title=\"Facebook\" class=\"social-btn-left-margin\"><img src=\"/frontend/images/social/facebook.png\" class=\"navicon\"></a>";
	$output .= "<a href=\"javascript:window.open('https://vk.com/share.php?url=".(isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$url."&title=".htmlspecialchars($title)."','Share this','width=640,height=480,location=no,toolbar=no,menubar=no');void(0);\" title=\"VKontakte\" class=\"social-btn-left-margin\"><img src=\"/frontend/images/social/vk.png\" class=\"navicon\"></a>";
	$output .= "<a href=\"javascript:window.open('http://www.linkedin.com/shareArticle?url=".(isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$url."&text=".htmlspecialchars($title)."','Share this','width=640,height=480,location=no,toolbar=no,menubar=no');void(0);\" title=\"LinkedIn\" class=\"social-btn-left-margin\"><img src=\"/frontend/images/social/Linkedin.png\" class=\"navicon\"></a>";
	$output .= "<a href=\"javascript:window.open('http://twitter.com/share?url=".(isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$url."&text=".htmlspecialchars($title)."','Share this','width=640,height=480,location=no,toolbar=no,menubar=no');void(0);\" title=\"Twitter\" class=\"social-btn-left-margin\"><img src=\"/frontend/images/social/twitter.png\" class=\"navicon\"></a>";
	$output .= "<a href=\"javascript:window.open('http://connect.mail.ru/share?url=".(isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$url."&title=".htmlspecialchars($title)."','Share this','width=640,height=480,location=no,toolbar=no,menubar=no');void(0);\" title=\"Мой мир\" class=\"social-btn-left-margin\"><img src=\"/frontend/images/social/moimir.png\" class=\"navicon\"></a>";
	$output .= "<a href=\"javascript:window.open('https://plus.google.com/share?url=".(isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$url."','Share this','width=640,height=480,location=no,toolbar=no,menubar=no');void(0);\" title=\"Google+\" class=\"social-btn-left-margin\"><img src=\"/frontend/images/social/google.png\" class=\"navicon\"></a>";
	$output .= "<a href=\"javascript:window.open('https://www.odnoklassniki.ru/dk?st.cmd=addShare&st.s=1&st._surl=".(isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$url."&st.comments=".htmlspecialchars($title)."','Share this','width=640,height=480,location=no,toolbar=no,menubar=no');void(0);\" title=\"Одноклассники\" class=\"social-btn-left-margin\"><img src=\"/frontend/images/social/ok.png\" class=\"navicon\"></a>";
	$output .= "<a href=\"javascript:window.open('http://livejournal.com/update.bml?event=".(isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$url."&subject=".htmlspecialchars($title)."','Share this','width=640,height=480,location=no,toolbar=no,menubar=no');void(0);\" title=\"LiveJournal\" class=\"social-btn-left-margin\"><img src=\"/frontend/images/social/livejournal.png\" class=\"navicon\"></a>";
	$output .= "<a href=\"javascript:window.open('https://reddit.com/submit?url=".(isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$url."&title=".htmlspecialchars($title)."','Share this','width=640,height=480,location=no,toolbar=no,menubar=no');void(0);\" title=\"Reddit\" class=\"social-btn-left-margin\"><img src=\"/frontend/images/social/reddit.png\" class=\"navicon\"></a>";
	$output .= "<a href=\"javascript:window.open('viber://forward?text=".(isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$url."','Share this','width=640,height=480,location=no,toolbar=no,menubar=no');void(0);\" title=\"Viber\" class=\"social-btn-left-margin\"><img src=\"/frontend/images/social/viber.png\" class=\"navicon\"></a>";
	$output .= "<a href=\"javascript:window.open('whatsapp://send?text=".(isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$url."','Share this','width=640,height=480,location=no,toolbar=no,menubar=no');void(0);\" title=\"WhatsApp\" class=\"social-btn-left-margin\"><img src=\"/frontend/images/social/whatsapp.png\" class=\"navicon\"></a>";
	$output .= "<a href=\"javascript:window.open('https://telegram.me/share/url?url=".(isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$url."','Share this','width=640,height=480,location=no,toolbar=no,menubar=no');void(0);\" title=\"Telegram\" class=\"social-btn-left-margin\"><img src=\"/frontend/images/social/telegram.png\" class=\"navicon\"></a>";
	return $output;
}
#Blocks
function blocks() {
	global $siteconfig;
	$urlname = "blocks/".strtolower(preg_replace("/[\<\>\:\"\\\\\|\?\*]/", "_", rtrim(ltrim($_SERVER['REQUEST_URI'], "/"), "/")).".csh");
	if (empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
		$gzipsupp = false;
	} else {
		$gzipsupp = strpos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip');
	}
	if ($siteconfig['usecache'] === true && $gzipsupp !== false && file_exists($siteconfig['cachedir'].$urlname) && time()-filemtime($siteconfig['cachedir'].$urlname) < $siteconfig['cachetime']/2) {
		$filecontent = gzdecode(file_get_contents($siteconfig['cachedir'].$urlname));
		return $filecontent;
	}
	global $dbh;
	$result = "";
	$searches = dbselect("SELECT * FROM (SELECT \"largestcompany\" AS `type`, `id`, `name`, `membercount` AS `data` FROM `ff__freecompanies` ORDER BY `membercount` DESC LIMIT 1) AS a
				UNION ALL
				SELECT * FROM (SELECT \"freshestcompany\" AS `type`, `id`, `name`, `updated` AS `data` FROM `ff__freecompanies` WHERE `updated`<>`added` AND (`deleted` IS NULL OR (`deleted` IS NOT NULL AND `deleted`<>`added`)) ORDER BY `updated` DESC LIMIT 1) AS b
				UNION ALL
				SELECT * FROM (SELECT \"newestcompany\" AS `type`, `id`, `name`, `updated` AS `data` FROM `ff__freecompanies` WHERE `updated`=`added` ORDER BY `updated` DESC LIMIT 1) AS c
				UNION ALL
				SELECT * FROM (SELECT \"lastremovedcompany\" AS `type`, `id`, `name`, `updated` AS `data` FROM `ff__freecompanies` WHERE `updated`=`deleted` ORDER BY `updated` DESC LIMIT 1) AS d
				UNION ALL
				SELECT * FROM (SELECT \"youngestcompany\" AS `type`, `id`, `name`, `formed` AS `data` FROM `ff__freecompanies` ORDER BY `formed` DESC LIMIT 1) AS e
				UNION ALL
				SELECT * FROM (SELECT \"oldestcompany\" AS `type`, `id`, `name`, `formed` AS `data` FROM `ff__freecompanies` ORDER BY `formed` ASC LIMIT 1) AS f
				UNION ALL
				SELECT * FROM (SELECT \"largestlinkshell\" AS `type`, `id`, `name`, `membercount` AS `data` FROM `ff__linkshells` ORDER BY `membercount` DESC LIMIT 1) AS g
				UNION ALL
				SELECT * FROM (SELECT \"freshestlinkshell\" AS `type`, `id`, `name`, `updated` AS `data` FROM `ff__linkshells` WHERE `updated`<>`added` AND (`deleted` IS NULL OR (`deleted` IS NOT NULL AND `deleted`<>`added`)) ORDER BY `updated` DESC LIMIT 1) AS h
				UNION ALL
				SELECT * FROM (SELECT \"newestlinkshell\" AS `type`, `id`, `name`, `updated` AS `data` FROM `ff__linkshells` WHERE `updated`=`added` ORDER BY `updated` DESC LIMIT 1) AS i
				UNION ALL
				SELECT * FROM (SELECT \"lastremovedlinkshell\" AS `type`, `id`, `name`, `updated` AS `data` FROM `ff__linkshells` WHERE `updated`=`deleted` ORDER BY `updated` DESC LIMIT 1) AS j
				UNION ALL
				SELECT * FROM (SELECT \"freshestcharacter\" AS `type`, `id`, `name`, `updated` AS `data` FROM `ff__characters` WHERE `updated`<>`added` AND (`deleted` IS NULL OR (`deleted` IS NOT NULL AND `deleted`<>`added`)) ORDER BY `updated` DESC LIMIT 1) AS k
				UNION ALL
				SELECT * FROM (SELECT \"newestcharacter\" AS `type`, `id`, `name`, `updated` AS `data` FROM `ff__characters` WHERE `updated`=`added` ORDER BY `updated` DESC LIMIT 1) AS l
				UNION ALL
				SELECT * FROM (SELECT \"lastremovedcharacter\" AS `type`, `id`, `name`, `updated` AS `data` FROM `ff__characters` WHERE `updated`=`deleted` ORDER BY `updated` DESC LIMIT 1) AS m
			");
	$selectedids = array_column($searches, 'id');
	$where = "WHERE ";
	foreach ($selectedids as $selectedid) {
		$where .= "`id` <> ".$selectedid." AND ";
	}
	$where = rtrim($where, " AND ");
	if (!empty($_GET['id']) && $_GET['service'] == "fftracker") {
		$where .= " AND `id` <> ".$_GET['id'];
	}
	$searches = array_merge($searches, dbselect("SELECT * FROM (SELECT \"randomcompany\" AS `type`, `id`, `name`, \"\" AS `data` FROM `ff__freecompanies` ".$where." ORDER BY RAND() LIMIT 1) AS a
							UNION ALL
							SELECT * FROM (SELECT \"randomlinkhshell\" AS `type`, `id`, `name`, \"\" AS `data` FROM `ff__linkshells` ".$where." ORDER BY RAND() LIMIT 1) AS b
							UNION ALL
							SELECT * FROM (SELECT \"randomcharacter\" AS `type`, `id`, `name`, \"\" AS `data` FROM `ff__characters` ".$where." ORDER BY RAND() LIMIT 1) AS c
						")
				);
	$result .= "<div class=\"general_table_block\"><div class=\"left-text w3-small w3-green\"><img class=\"navicon\" src=\"/frontend/images/favicons/fftracker-32x32.png\"> Free Companies</div><div class=\"font-small\">";
	$key = array_search('largestcompany', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Largest company with ".$searches[$key]['data']." members\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/crowd.png\"> ".(is_file(getcwd()."/frontend/images/fftracker/crests/merged/".$searches[$key]['id'].".png") ? "<img class=\"navicon\" src=\"/frontend/images/fftracker/crests/merged/".$searches[$key]['id'].".png\"> " : "")."<a href=\"/fftracker/freecompany/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";
	}
	$key = array_search('youngestcompany', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Youngest company formed on ".date("d F Y H:i:s" ,$searches[$key]['data'])."\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/young.png\"> ".(is_file(getcwd()."/frontend/images/fftracker/crests/merged/".$searches[$key]['id'].".png") ? "<img class=\"navicon\" src=\"/frontend/images/fftracker/crests/merged/".$searches[$key]['id'].".png\"> " : "")."<a href=\"/fftracker/freecompany/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";
	}
	$key = array_search('oldestcompany', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Oldest company formed on ".date("d F Y H:i:s" ,$searches[$key]['data'])."\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/old.png\"> ".(is_file(getcwd()."/frontend/images/fftracker/crests/merged/".$searches[$key]['id'].".png") ? "<img class=\"navicon\" src=\"/frontend/images/fftracker/crests/merged/".$searches[$key]['id'].".png\"> " : "")."<a href=\"/fftracker/freecompany/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";

	}
	$key = array_search('newestcompany', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Newest company registered on ".date("d F Y H:i:s" ,$searches[$key]['data'])."\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/new.png\"> ".(is_file(getcwd()."/frontend/images/fftracker/crests/merged/".$searches[$key]['id'].".png") ? "<img class=\"navicon\" src=\"/frontend/images/fftracker/crests/merged/".$searches[$key]['id'].".png\"> " : "")."<a href=\"/fftracker/freecompany/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";
	}
	$key = array_search('freshestcompany', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Last updated on ".date("d F Y H:i:s" ,$searches[$key]['data'])."\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/fresh.png\"> ".(is_file(getcwd()."/frontend/images/fftracker/crests/merged/".$searches[$key]['id'].".png") ? "<img class=\"navicon\" src=\"/frontend/images/fftracker/crests/merged/".$searches[$key]['id'].".png\"> " : "")."<a href=\"/fftracker/freecompany/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";
	}
	$key = array_search('lastremovedcompany', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Last disbanded on ".date("d F Y H:i:s" ,$searches[$key]['data'])."\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/rip.png\"> ".(is_file(getcwd()."/frontend/images/fftracker/crests/merged/".$searches[$key]['id'].".png") ? "<img class=\"navicon\" src=\"/frontend/images/fftracker/crests/merged/".$searches[$key]['id'].".png\"> " : "")."<a href=\"/fftracker/freecompany/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";
	}
	$key = array_search('randomcompany', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Random company\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/dice.png\"> ".(is_file(getcwd()."/frontend/images/fftracker/crests/merged/".$searches[$key]['id'].".png") ? "<img class=\"navicon\" src=\"/frontend/images/fftracker/crests/merged/".$searches[$key]['id'].".png\"> " : "")."<a href=\"/fftracker/freecompany/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";
	}
	$result .= "</div></div><br>";
	$result .= "<div class=\"general_table_block\"><div class=\"left-text w3-small w3-green\"><img class=\"navicon\" src=\"/frontend/images/fftracker/linkshell.png\"> Linkshells</div><div class=\"font-small\">";
	$key = array_search('largestlinkshell', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Largest linkshell with ".$searches[$key]['data']." members\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/crowd.png\"> <a href=\"/fftracker/linkshell/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";
	}
	$key = array_search('newestlinkshell', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Newest linkshell registered on ".date("d F Y H:i:s" ,$searches[$key]['data'])."\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/new.png\"> <a href=\"/fftracker/linkshell/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";
	}
	$key = array_search('freshestlinkshell', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Last updated on ".date("d F Y H:i:s" ,$searches[$key]['data'])."\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/fresh.png\"> <a href=\"/fftracker/linkshell/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";
	}
	$key = array_search('lastremovedlinkshell', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Last disbanded on ".date("d F Y H:i:s" ,$searches[$key]['data'])."\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/rip.png\"> <a href=\"/fftracker/linkshell/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";
	}
	$key = array_search('randomlinkhshell', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Random linkshell\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/dice.png\"> <a href=\"/fftracker/linkshell/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";
	}
	$result .= "</div></div><br>";
	$result .= "<div class=\"general_table_block\"><div class=\"left-text w3-small w3-green\"><img class=\"navicon\" src=\"/frontend/images/favicons/fftracker-32x32.png\"> Characters</div><div class=\"font-small\">";
	$key = array_search('newestcharacter', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Newest character registered on ".date("d F Y H:i:s" ,$searches[$key]['data'])."\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/new.png\"> <a href=\"/fftracker/character/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";
	}
	$key = array_search('freshestcharacter', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Last updated on ".date("d F Y H:i:s" ,$searches[$key]['data'])."\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/fresh.png\"> <a href=\"/fftracker/character/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";
	}
	$key = array_search('lastremovedcharacter', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Last slayed on ".date("d F Y H:i:s" ,$searches[$key]['data'])."\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/rip.png\"> <a href=\"/fftracker/character/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";
	}
	$key = array_search('randomcharacter', array_column($searches, 'type'));
	if ($key !== false) {
		$result .= "<div title=\"Random character\"><img class=\"navicon\" src=\"/frontend/images/fftracker/blockstatus/dice.png\"> <a href=\"/fftracker/character/".$searches[$key]['id']."/\">".$searches[$key]['name']."</a></div>";
	}
	$result .= "</div></div>";
	if ($siteconfig['usecache'] === true && $gzipsupp !== false) {
		$dir = pathinfo($siteconfig['cachedir'].$urlname, PATHINFO_DIRNAME);
		if (is_file($dir)) {
			@unlink($dir);
		}
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}
		file_put_contents($siteconfig['cachedir'].$urlname, gzencode($result, 6, FORCE_GZIP));
	}
	return $result;
}
#Grab related links, if any
function rellinks($id) {
	global $siteconfig;
	$output = "";
	$query = "SELECT * FROM `rellinks` WHERE `serviceid`=".dbquote($id)." ORDER BY `linkorder` ASC";
	if ($result = dbselect($query, false)) {
		$output .= "<br><table class=\"width-ninety tbllinks\"><tr><td class=\"bold center-text\" colspan=\"2\">Related Links</td></tr>";
		foreach ($result as $key=>$row) {
			$output .= "<tr><td>";
			if ($row['icon'] != "") {
				$output .= "<img scr=\"".$siteconfig['linkiconspath'].$row['icon']."\" class=\"rellink-icon\">";
			}
			$output .= "<a target=\"_blank\" href=\"".$row['link']."\" title=\"".$row['title']."\">".$row['title']."</a></td><td>".$row['description']."</td></tr>";
		}
		$output .= "</table><br>";
	}
	return $output;
}
#Grab description of the service
function about($query, $nolinks = false) {
	$output = "";
	$title = "";
	if ($result = dbselect($query, false)) {
		foreach ($result as $key=>$row) {
			$title = $row['title'];
			if (!empty($row['text'])) {
				$output =  "<script type=\"text/javascript\">
						function toggleabout() {
							var btn = document.getElementById('atogbutt');
							if (btn.classList.contains(\"fa-chevron-circle-down\")) {
								btn.classList.remove(\"fa-chevron-circle-down\");
								btn.classList.add(\"fa-chevron-circle-up\");
								document.getElementById(\"about\").classList.remove(\"hideme\");
								document.getElementById(\"about_bottom\").classList.remove(\"hideme\");
							} else {
								btn.classList.remove(\"fa-chevron-circle-up\");
								btn.classList.add(\"fa-chevron-circle-down\");
								document.getElementById(\"about\").classList.add(\"hideme\");
								document.getElementById(\"about_bottom\").classList.add(\"hideme\");
							}
						}
					</script>
				";
				$output .= "<div class=\"general_table_block\">";
				$output .= "<div onclick=\"toggleabout()\" class=\"pointer center-text w3-small w3-green\">About<span id=\"toggleabout\"><i id=\"atogbutt\" class=\"fa fa-chevron-circle-".($row['collapsed'] == 1 ? "down" : "up");
				$output .= "\"></i></span></div>
						<div id=\"about\" class=\"article justify-text";
				if ($row['collapsed'] == 1) {
					$output .= " hideme";
				}	
				$output .= "\">";
				$output .= pwrapper(nl2br(txtout($row['text'])));
				if ($nolinks == false) {
					$output .= rellinks($row['id']);
				}
				$output .= "</div>
							<div id=\"about_bottom\" class=\"w3-small w3-green";
				if ($row['collapsed'] == 1) {
					$output .= " hideme";
				}			
				$output .= "\">&nbsp;</div></div><br>";
			} else {
				$output = "";
			}
		}
	} else {
		$output = "";
	}
	return array("content" => $output, "title" => $title);
}
#Language convert
function langconv($lang) {
	if ($lang == "eng") {
		return "English";
	} elseif ($lang == "rus") {
		return "Russian";
	} elseif ($lang != "" && $lang != "eng" && $lang != "rus") {
		return $lang;
	}
}
#Grab articles
function article($query) {
	global $siteconfig;
	$output = "";
	$title = "";
	if ($result = dbselect($query, false)) {
		foreach ($result as $key=>$row) {
			$output .= "<div class=\"general_table_block\" id=\"".$row['type'].$row['id']."\">";
			if ($row['type'] == "Poems") {
				$split = explode("\n", $row['text']);
				if ($row['title'] == "***") {
					$row['title'] = $split[0]."...";
				}
			}
			$output .= "<div class=\"w3-small w3-green\"><span class=\"article-block-name\"><a class=\"no-decor\" href=\"/".strtolower($row['type'])."/".$row['id']."-".seonizer($row['title'])."\">".$row['title']."</a>";
			if ($row['type'] == "Poems" || $row['type'] == "Stories") {
				if ($row['copyright'] != "") {
					$output .= "&nbsp;<a class=\"no-decor\" href=\"".$row['copyright']."\" target=\"_blank\">&copy;</a>";
				}
			}
			$output .= "</span><span class=\"item-time\"><a class=\"no-decor\" href=\"/".strtolower($row['type'])."/".$row['id']."-".seonizer($row['title'])."\">".date("d/m/Y H:i" ,$row['date'])."</a></span><div class=\"floatclear\"></div></div>
					<div class=\"article ";
			if ($row['type'] == "Poems") {
				$output .= "center";
			} else {
				$output .= "justify";
			}
			$output .= "-text\">";
			if (count($result) === 1) {
				$title = $row['title'];
				if ($row['type'] == "Poems") {
					if ($row['song'] != "") {
						$output .= "<br><audio controls preload=\"metadata\">
									<source src=\"".$siteconfig['poemsmusic'].$row['song']."\" type=\"audio/mpeg\"><a href=\"".$siteconfig['poemsmusic'].$row['song']."\">Download song</a></audio><br><br>";
					}
					if ($row['original'] != "") {
						$output .= "<table><tr><td class=\"bold\">Translation</td><td class=\"bold\">Original</td></tr><tr><td class=\"top-align\">".nl2br(txtout($row['text']))."</td><td class=\"top-align\">".nl2br(txtout($row['original']))."</td></tr></table>";
					} else {
						$output .= nl2br(txtout($row['text']));
					}
				} else {
					$output .= pwrapper(nl2br(txtout($row['text'])));
				}
			} else {
				if ($row['type'] == "Poems") {
					if (count($split) > 4) {
						$output .= txtout($split[0])."<br>".txtout($split[1])."<br>".txtout($split[2])."<br>".txtout($split[3])."<br><a class=\"no-decor\" href=\"/poems/".$row['id']."-".seonizer($row['title'])."\">&middot;&middot;&middot;<i style=\"color:#af111c;\" class=\"fa fa-scissors\"></i>&middot;&middot;&middot;</a>";
					} else {
						$output .= nl2br(txtout($row['text']));
					}
				} else {
					if ($row['type'] == "Snippets") {
						$row['text'] = codestrip(txtout($row['text']));
					} else {
						$row['text'] = txtout($row['text']);
					}
					$textcut = txtcut($row['text'], 500);
					if (strlen($row['text']) == $textcut) {
						$output .= nl2br($row['text']);
					} else {
						$output .= nl2br(substr($row['text'], 0, $textcut))."<br><a class=\"no-decor\" href=\"/".strtolower($row['type'])."/".$row['id']."-".seonizer($row['title'])."\">&middot;&middot;&middot;<i style=\"color:#af111c;transform:rotate(270deg)\" class=\"fa fa-scissors\"></i>&middot;&middot;&middot;</a>";
					}
				}
			}
			$output .= "</div><div class=\"article-block-bottom w3-small w3-green\">
							<span class=\"type-tag\"><a class=\"no-decor\" href=\"/".strtolower($row['type'])."/\">[".ucfirst($row['type'])."]</a></span>";
				$output .= "<div class=\"social-btns-block\">".socialbtns("/".strtolower($row['type'])."/".$row['id']."-".seonizer($row['title']), $row['title'])."</div>";
			$output .= "<div class=\"floatclear\"></div>
					</div></div><br>";
		}
	} else {
		header( 'Location: /', true, 301);
		exit;
	}
	if (count($result) === 1) {
		return array("content" => $output, "title" => $title, "time" => $row['date'], "language" => $row['language']);
	} else {
		return array("content" => $output, "breadcrumb" => "<a href=\"/\">Index</a>".(empty($_GET['page']) ? (empty($_GET['service']) ? "" : "<i style=\"color:#af111c;\" class=\"fa fa-arrow-right\"></i><a href=\"/".$_GET['service']."/\">".ucfirst($_GET['service'])."</a>").(empty($_GET['lang']) ? "" : "<i style=\"color:#af111c;\" class=\"fa fa-arrow-right\"></i><a href=\"/".$_GET['service']."/".$_GET['lang']."/\">In ".($_GET['lang'] == "eng" ? "English" : ($_GET['lang'] == "rus" ? "Russian" : strtoupper($_GET['lang'])))."</a>")."<i style=\"color:#af111c;\" class=\"fa fa-arrow-right\"></i>Page 1" : (empty($_GET['service']) ? "" : "<i style=\"color:#af111c;\" class=\"fa fa-arrow-right\"></i><a href=\"/".$_GET['service']."/\">".ucfirst($_GET['service'])."</a>").(empty($_GET['lang']) ? "" : "<i style=\"color:#af111c;\" class=\"fa fa-arrow-right\"></i><a href=\"/".$_GET['service']."/".$_GET['lang']."/\">In ".($_GET['lang'] == "eng" ? "English" : ($_GET['lang'] == "rus" ? "Russian" : strtoupper($_GET['lang'])))."</a>")."<i style=\"color:#af111c;\" class=\"fa fa-arrow-right\"></i>Page ".$_GET['page']));
	}
}
#Grab prices
function prices() {
	$output = "";
	$output .= "<div class=\"general_table_block\">";
	$output .= "<div class=\"center-text w3-small w3-green\">Prices</div>";
	$output .= "<div class=\"article justify-text\">";
	$output .= "Placeholder for prices list. Yes, some of the services will cost a bit, but if you reember prices for DarkSteam, you know I do not put huge tags. Also, donation buttons are planned";
	$output .= "</div><div class=\"w3-small w3-green\">&nbsp;</div></div><br>";
	return $output;
}
#Pagination
function pagination($count_pages, $active = 1, $pagetype) {
	$output = "";
	if ($count_pages > 1) {
		$output .= "<div class=\"w3-center w3-small pagination\">";
		if ($active != 1) {
			$output .= "<span><a class=\"no-decor\" href=\"/".$pagetype."/1\">1</a></span>";
		}
		if (($active - 4) > 1) {
			$output .= "...&nbsp;";
		}
		if (($active - 3) > 1) {
			$output .= "&nbsp;<span><a class=\"no-decor\" href=\"/".$pagetype."/".($active - 3)."\">".($active - 3)."</a></span>";	
		}
		if (($active - 2) > 1) {
			$output .= "&nbsp;<span><a class=\"no-decor\" href=\"/".$pagetype."/".($active - 2)."\">".($active - 2)."</a></span>";	
		}
		if (($active - 1) > 1) {
			$output .= "&nbsp;<span><a class=\"no-decor\" href=\"/".$pagetype."/".($active - 1)."\">".($active - 1)."</a></span>";	
		}
		if ($active != 1) {
			$output .= "&nbsp;";
		}
		$output .= "<span style=\"color: #67818a;\">".$active."</span>";
		if (($active + 1) < $count_pages) {
			$output .= "&nbsp;<span><a class=\"no-decor\" href=\"/".$pagetype."/".($active + 1)."\">".($active + 1)."</a></span>";	
		}
		if (($active + 2) < $count_pages) {
			$output .= "&nbsp;<span><a class=\"no-decor\" href=\"/".$pagetype."/".($active + 2)."\">".($active + 2)."</a></span>";	
		}
		if (($active + 3) < $count_pages) {
			$output .= "&nbsp;<span><a class=\"no-decor\" href=\"/".$pagetype."/".($active + 3)."\">".($active + 3)."</a></span>";	
		}
		if (($active + 4) < $count_pages) {
			$output .= "&nbsp;...";	
		}
		if ($active != $count_pages) {
			$output .= "&nbsp;<span><a class=\"no-decor\" href=\"/".$pagetype."/".$count_pages."\">".$count_pages."</a></span>";
		}
		$output .= "</div>";
		$output = str_replace("//", "/", $output);
	}
	return $output;
}
#Get navigation functions
function sidenavbar($breadcrumbs) {
	global $siteconfig;
	$output = "<nav role=\"navigation\" itemscope itemtype=\"http://schema.org/SiteNavigationElement\">
		<ul class=\"navul\">
			<li>
				<img alt=\"Main Page\" class=\"navicon\" src=\"/frontend/images/favicons/simbiat-32x32.png\"> <a href=\"/index/\" title=\"Main Page\" target=\"_self\" itemprop=\"url\"><span itemprop=\"name\">Main Page</span></a>";
	if (strcasecmp($breadcrumbs["level1"], "/index") === 0) {
		$output .= "<br>".bread($breadcrumbs['content']);
	}
	$output .= "
			</li>
			<ul>";
	if ($links = dbselect("SELECT * FROM `navbar` ORDER BY `linkorder` ASC", false)) {
		foreach (array_keys(array_column($links, 'parentid'), "0") as $level1) {
			$output .= "<li>&nbsp;&nbsp;&nbsp;<img alt=\"".$links[$level1]['title']."\" class=\"navicon\" src=\"".(empty($links[$level1]['icon']) ? "/frontend/images/favicons/simbiat-32x32.png" : $links[$level1]['icon'])."\"> <a ";
			if (in_array($links[$level1]['name'], $siteconfig['navcollapsed'])) {
				$output .= "onclick=\"accordion('".str_replace(" ", "", $links[$level1]['name'])."list');\" ";
			}
			if (!empty($breadcrumbs['level1']) && strcasecmp($breadcrumbs['level1']."/", $links[$level1]['link']) === 0) {
				$output .= " class=\"breadcrumb\" ";
			}
			$output .= "href=\"".$links[$level1]['link']."\" title=\"".$links[$level1]['title']."\" target=\"".$links[$level1]['target']."\" itemprop=\"url\"><span itemprop=\"name\">".$links[$level1]['name']."</span></a>";
			if (in_array($links[$level1]['name'], $siteconfig['navcollapsed'])) {
				$output .= " <i onclick=\"accordion('".str_replace(" ", "", $links[$level1]['name'])."list');\" class=\"fa fa-caret-down\"></i><div id=\"".str_replace(" ", "", $links[$level1]['name'])."list\" class=\"hideme\">";
			}
			if (!empty($breadcrumbs['level1']) && strcasecmp($breadcrumbs['level1']."/", $links[$level1]['link']) === 0 && empty($breadcrumbs['level2'])) {
				$output .= "<br>".bread($breadcrumbs['content']);
			}
			if (array_search($links[$level1]['id'], array_column($links, 'parentid')) !== false) {
				$output .= "<ul>";
				foreach (array_keys(array_column($links, 'parentid'), $links[$level1]['id']) as $level2) {
					$output .= "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img alt=\"".$links[$level2]['title']."\" class=\"navicon\" src=\"".(empty($links[$level2]['icon']) ? "/frontend/images/favicons/simbiat-32x32.png" : $links[$level2]['icon'])."\"> <a ";
					if (in_array($links[$level2]['name'], $siteconfig['navcollapsed'])) {
						$output .= "onclick=\"accordion('".str_replace(" ", "", $links[$level2]['name'])."list');\" ";
					}
					if ((!empty($breadcrumbs['level2']) && strcasecmp($breadcrumbs['level1'].$breadcrumbs['level2']."/", $links[$level2]['link']) === 0 ) || (!empty($breadcrumbs['level1']) && $breadcrumbs['level1']."/" == $links[$level2]['link'])) {
						$output .= " class=\"breadcrumb\" ";
					}
					$output .= "href=\"".$links[$level2]['link']."\" title=\"".$links[$level2]['title']."\" target=\"".$links[$level2]['target']."\" itemprop=\"url\"><span itemprop=\"name\">".$links[$level2]['name']."</span></a>";
					if (in_array($links[$level2]['name'], $siteconfig['navcollapsed'])) {
						$output .= " <i onclick=\"accordion('".str_replace(" ", "", $links[$level2]['name'])."list');\" class=\"fa fa-caret-down\"></i><div id=\"".str_replace(" ", "", $links[$level2]['name'])."list\" class=\"hideme\">";
					}
					if ((!empty($breadcrumbs['level2']) && strcasecmp($breadcrumbs['level1'].$breadcrumbs['level2']."/", $links[$level2]['link']) === 0 && empty($breadcrumbs['level3'])) || (!empty($breadcrumbs['level1']) && $breadcrumbs['level1']."/" == $links[$level2]['link'] && empty($breadcrumbs['level2']))) {
						$output .= "<br>".bread($breadcrumbs['content']);
					}
					if (array_search($links[$level2]['id'], array_column($links, 'parentid')) !== false) {
						$output .= "<ul>";
						foreach (array_keys(array_column($links, 'parentid'), $links[$level2]['id']) as $level3) {
							$output .= "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img alt=\"".$links[$level3]['title']."\" class=\"navicon\" src=\"".(empty($links[$level3]['icon']) ? "/frontend/images/favicons/simbiat-32x32.png" : $links[$level3]['icon'])."\"> <a";
							if ((!empty($breadcrumbs['level3']) && strcasecmp($breadcrumbs['level1'].$breadcrumbs['level2'].$breadcrumbs['level3']."/", $links[$level3]['link']) === 0) || (!empty($breadcrumbs['level2']) && strcasecmp($breadcrumbs['level1'].$breadcrumbs['level2']."/", $links[$level3]['link']) === 0) || (!empty($breadcrumbs['level1']) && strcasecmp($breadcrumbs['level1']."/", $links[$level3]['link']) === 0)) {
								$output .= " class=\"breadcrumb\"";
							}
							$output .= " href=\"".$links[$level3]['link']."\" title=\"".$links[$level3]['title']."\" target=\"".$links[$level3]['target']."\" itemprop=\"url\"><span itemprop=\"name\">".$links[$level3]['name']."</span></a>";
							if ((!empty($breadcrumbs['level3']) && strcasecmp($breadcrumbs['level1'].$breadcrumbs['level2'].$breadcrumbs['level3']."/", $links[$level3]['link']) === 0) || (!empty($breadcrumbs['level2']) && strcasecmp($breadcrumbs['level1'].$breadcrumbs['level2']."/", $links[$level3]['link']) === 0) || (!empty($breadcrumbs['level1']) && strcasecmp($breadcrumbs['level1']."/", $links[$level3]['link']) === 0)) {
								$output .= "<br>".bread($breadcrumbs['content']);
							}
							$output .= "</li>";
						}
						$output .= "</ul>";
					}
					if ($links[$level1]['name'] == "Planned") {
						$output .= "</div>";
					}
					$output .= "</li>";
				}
				$output .= "</ul>";
			}
			if ($links[$level1]['name'] == "Contacts" || $links[$level1]['name'] == "Useful Links") {
				$output .= "</div>";
			}
			$output .= "</li>";
		}
	}
	//exit;
	$output .= "</ul>
		
	</ul></nav>";
	return $output;
}
function bread($crumbs) {
	$bread = "<ol class=\"bcol\" vocab=\"http://schema.org/\" typeof=\"BreadcrumbList\">";
	$level = 2;
	$fakelevel = 0;
	$bread .= "<li class=\"hideme\" property=\"itemListElement\" typeof=\"ListItem\">
			<a property=\"item\" typeof=\"WebPage\" href=\"/index/\">
				<span property=\"name\">Index</span>
			</a>
			<meta property=\"position\" content=\"1\">
		</li>";
	foreach ($crumbs as $crumb) {
		if (empty($crumb["name"])) {
			$fakelevel++;
		} else {
			$bread .= "<li class=\"breadcrumb".(empty($crumb["show"]) ? " hideme" : "")."\" property=\"itemListElement\" typeof=\"ListItem\">
				".str_repeat("&nbsp;&nbsp;&nbsp;", $level - 1 + $fakelevel)."<img alt=\"".$crumb["name"]."\" class=\"navicon\" src=\"".(empty($crumb["icon"]) ? "/frontend/images/favicons/simbiat-32x32.png" : $crumb["icon"])."\"> <a property=\"item\" typeof=\"WebPage\" href=\"".$crumb["link"]."\"><span property=\"name\">".$crumb["name"]."</span></a>
				<meta property=\"position\" content=\"".$level."\">
			</li>";
			$level++;
		}
	}
	$bread .= "</ol>";
	return $bread;
}
#Function to force reload
function refreshpage($page="") {
	$scroll = "<script type=\"text/javascript\">document.body.scrollTop = document.body.scrollHeight - document.body.clientHeight;</script>";
	ob_end_flush();
	ob_start();
	Echo "Refreshing page in 5 seconds...<br>".$scroll;
	ob_flush();
	flush();
	sleep(1);
	Echo "Refreshing page in 4 seconds...<br>".$scroll;
	ob_flush();
	flush();
	sleep(1);
	Echo "Refreshing page in 3 seconds...<br>".$scroll;
	ob_flush();
	flush();
	sleep(1);
	Echo "Refreshing page in 2 seconds...<br>".$scroll;
	ob_flush();
	flush();
	sleep(1);
	Echo "Refreshing page in 1 second...".$scroll;
	ob_flush();
	flush();
	sleep(1);
	if ($page != "") {
		echo "<script type=\"text/javascript\">window.top.location.href=\"".$page."\"</script>";
	} else {
		echo "<script type=\"text/javascript\">window.top.location.reload(true);</script>";
	}
}
#Cache functions
function cachecleaner($age) {
	global $siteconfig;
	recursiveRemove($siteconfig['cachedir'], $age);
}
function cachedel($mask) {
	global $siteconfig;
	foreach(glob($siteconfig['cachedir'].$mask) as $file){
		if(is_file($file)) {
			@unlink($file);
		}
	}
}
function cacheout($data, $forcereg=false) {
	global $siteconfig;
	#Getting columns
	$column2 = blocks();
	if ($siteconfig['usecache'] === true && $GLOBALS['gzipsupp'] !== false && $forcereg === false) {
        	ob_start('ob_gzhandler');
		$size = strlen(str_replace("\$column2", $column2, $data));
		header("Content-Length: ".$size);
		$dir = pathinfo($siteconfig['cachedir'].$GLOBALS['urlname'], PATHINFO_DIRNAME);
		if (is_file($dir)) {
			@unlink($dir);
		}
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}
		file_put_contents($siteconfig['cachedir'].$GLOBALS['urlname'], gzencode($data, 6, FORCE_GZIP));
	} else {
		ob_start();
	}
	echo str_replace("\$column2", $column2, $data);
	ob_end_flush();
	exit;
}
function recursiveRemove($dir, $age) {
	global $siteconfig;
	$curtime = time();
	$structure = glob(rtrim($dir, "/").'/*');
		if (is_array($structure)) {
			foreach($structure as $file) {
				if (is_dir($file)) {
					recursiveRemove($file, $age);
				} elseif (is_file($file) && $curtime-filemtime($file) > $age) {
					@unlink($file);
				}
        		}
		}
	if ($dir != $siteconfig['cachedir']) {
		@rmdir($dir);
	}
}
?>