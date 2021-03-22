<?php
#SQL connection settings
$db_host="localhost";
$db_user="username";
$db_pass="password";
$db_name="database";

#Encryption settings
$encrypt_method = "AES-256-OFB";
$secret_key = "*fgx7VG&G73Ih3#pxk1M$31n@";
$secret_iv = "3^n1FKejhqc!z4iEe%8WCa&dY";
$hash_type = "sha512";

#Database functions connectivity functions
function dbconnect() {
	Global $db_host, $db_user, $db_pass, $db_name;
	try {
		$dbh = new PDO('mysql:host='.$db_host.';charset=utf8mb4;dbname='.$db_name, $db_user, $db_pass, array(PDO::ATTR_PERSISTENT => true, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC));
		return $dbh;
	} catch (PDOException $e) {
		return $e->getMessage();
	}
}
function dbclose($dbh) {
	try {
		$dbh = null;
		return true;
	} catch (PDOException $e) {
		return false;
	}
}
#Following function to be used only for static SELECTs
function dbselect($select, $check = false, $count = false) {
	Global $dbh;
	$dbh = dbconnect();
	if ($dbh !== false) {
		$stmt = $dbh->prepare($select);
		if ($stmt->execute()) {
			if ($result = $stmt->fetchAll()) {
				if ($check === true) {
					return true;
				} else {
					if ($count === false) {
						return $result;
					} else {
						return count($result);
					}
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}
function dbedit($query) {
	Global $dbh;
	$dbh = dbconnect();
	if ($dbh !== false) {
		$stmt = $dbh->prepare($query);
		if ($stmt->execute()) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}
function dbquote($value) {
	Global $dbh;
	return ($value !== NULL ? $dbh->quote(html_entity_decode($value, ENT_QUOTES | ENT_HTML401)) : "NULL");
}

#Cookies functions
function insert_news() {
	#query
	//INSERT INTO `news`(`date`, `title`, `type`, `short`, `text`) VALUES (UNIX_TIMESTAMP(now()),"Test","News","Short text","Full text")
}

#Text preparation
function vardump($value) {
	echo '<pre>' . var_export($value, true) . '</pre>';
}
function imgnamesane($imgname) {
	return preg_replace("/[^A-Za-z0-9]/", '', $imgname);
}
function txtout($string) {
	#Strip HTML
	$string = striphtml($string);
	#Convert BB to HTML
	$string = bb2html($string);
	#Convert service names to links
	$string = service2html($string);
	return $string;
}
function codestrip($string) {
	$tags = array(
		"/<code(.*?)<\/code>/is" => "<i class=\"codestrip fa fa-terminal\"></i>CODE<i class=\"codestrip fa fa-code\"></i>",
		"/<pre><quote(.*?)<\/quote><\/pre>/is" => "<i class=\"codestrip fa fa-quote-left\"></i>QUOTE<i class=\"codestrip fa fa-quote-right\"></i>",
	);
	foreach($tags as $match=>$replacement){
		$string = preg_replace($match, $replacement, $string);
	}
	return $string;
}
function pwrapper($string) {
	$split = explode("\n", $string);
	return "<p>" . implode("</p><p>", $split)."</p>";
}
function txtcut($string, $length) {
	$tag = false;
	$chars = 0;
	$position = 0;
	$split = str_split($string);
	foreach ($split as $char) {
		$position++;
		if ($char == "<") {
			$tag = true;
			continue;
		}
		if ($char == ">") {
			$tag = false;
			continue;
		}
		if ($tag == true) {
			continue;
		}
		$chars++;
		if ($chars >= $length) {
			if ($char == " " || $char == "\r" || $char == "\n") {
				$position--;
				break;
			}
		}
	}
	if ($position == strlen($string)) {
		return strlen($string);
	} else {
		$string = substr($string, 0, $position);
		if (substr($string, -4, 4) == "<br>") {
			$string = rtrim(substr($string, 0, -4));
		}
		return strlen($string);
	}
}

#Sanitization
function striphtml($string) {
	#Replace html tags and stuff within [code][/code] with htmlenteties
	preg_match_all('/\[code\](.*?)\[\/code\]/i', $string, $matches);
	$replacement = $matches[0];
	$search = $matches[0];
	foreach ($replacement as $key => $value) {
		$replacement[$key] = htmlspecialchars($value);
	}
	foreach ($search as $key => $value) {
		$search[$key] = strip_tags($value);
	}
	$string = strip_tags($string);
	$string = str_replace($search, $replacement, $string);
	return $string;
}
function bb2html($string) {
	Global $siteconfig;
	$bbtags = array(
		'[heading1]' => '<h1>','[/heading1]' => '</h1>',
		'[heading2]' => '<h2>','[/heading2]' => '</h2>',
		'[heading3]' => '<h3>','[/heading3]' => '</h3>',
		'[h1]' => '<h1>','[/h1]' => '</h1>',
		'[h2]' => '<h2>','[/h2]' => '</h2>',
		'[h3]' => '<h3>','[/h3]' => '</h3>',
		'[paragraph]' => '<p>','[/paragraph]' => '</p>',
		'[para]' => '<p>','[/para]' => '</p>',
		'[p]' => '<p>','[/p]' => '</p>',
		'[left]' => '<p class="left-text">','[/left]' => '</p>',
		'[right]' => '<p class="right-text">','[/right]' => '</p>',
		'[center]' => '<p class="center-text">','[/center]' => '</p>',
		'[justify]' => '<p class="justify-text">','[/justify]' => '</p>',
		'[bold]' => '<span class="bold">','[/bold]' => '</span>',
		'[italic]' => '<span class="italic">','[/italic]' => '</span>',
		'[underline]' => '<span class="underline">','[/underline]' => '</span>',
		'[b]' => '<span class="bold">','[/b]' => '</span>',
		'[i]' => '<span class="italic">','[/i]' => '</span>',
		'[u]' => '<span class="underline">','[/u]' => '</span>',
		'[break]' => '<br>',
		'[br]' => '<br>',
		'[newline]' => '<br>',
		'[nl]' => '<br>',
		'[unordered_list]' => '<ul>','[/unordered_list]' => '</ul>',
		'[list]' => '<ul>','[/list]' => '</ul>',
		'[ul]' => '<ul>','[/ul]' => '</ul>',
		'[ordered_list]' => '<ol>','[/ordered_list]' => '</ol>',
		'[ol]' => '<ol>','[/ol]' => '</ol>',
		'[list_item]' => '<li>','[/list_item]' => '</li>',
		'[li]' => '<li>','[/li]' => '</li>',
		'[*]' => '<li>','[/*]' => '</li>',
		'[preformatted]' => '<pre>','[/preformatted]' => '</pre>',
		'[pre]' => '<pre>','[/pre]' => '</pre>',
	);
	$string = str_ireplace(array_keys($bbtags), array_values($bbtags), $string);
	$bbextended = array(
		"/\[url](.*?)\[\/url]/i" => "<a href=\"http://$1\" title=\"$1\" target=\"_blank\">$1</a>",
		"/\[url=(.*?)\](.*?)\[\/url\]/i" => "<a href=\"$1\" title=\"$1\" target=\"_blank\">$2</a>",
		"/\[youtube](.*?)\[\/youtube]/i" => "<iframe class=\"youtube\" src=\"https://www.youtube.com/embed/$1\"></iframe>",
		"/\[code](.*?)\[\/code]/is" => "<code class=\"php\">$1</code>",
		"/\[code=(.*?)\](.*?)\[\/code\]/is" => "<code class=\"$1\">$2</code>",
		"/\[quote](.*?)\[\/quote]/is" => "<pre><quote><i class=\"fa fa-quote-left\"></i>
$1<i class=\"fa fa-quote-right\"></i></quote></pre>",
		"/\[quote=(.*?)\](.*?)\[\/quote\]/is" => "<pre><quote>$1:<br><i class=\"fa fa-quote-left\"></i>
$2<i class=\"fa fa-quote-right\"></i></quote></pre>",
		"/\[email=(.*?)\](.*?)\[\/email\]/i" => "<a href=\"mailto:$1\">$2</a>",
		"/\[mail=(.*?)\](.*?)\[\/mail\]/i" => "<a href=\"mailto:$1\">$2</a>",
		"/\[img\]([^[]*)\[\/img\]/i" => "<a href=\"$1\" target=\"_blank\"><img class=\"imgbb\" src=\"$1\" alt=\" \" /></a>",
		"/\[image\]([^[]*)\[\/image\]/i" => "<a href=\"$1\" target=\"_blank\"><img class=\"imgbb\" src=\"$1\" alt=\" \" /></a>",
		"/\[imgint\]([^[]*)\[\/imgint\]/i" => "<a href=\"".$siteconfig['imgintpath']."$1\" target=\"_blank\"><img class=\"imgbb\" src=\"".$siteconfig['imgintpath']."$1\" alt=\" \" /></a>",
	);
	foreach($bbextended as $match=>$replacement){
		$string = preg_replace($match, $replacement, $string);
	}
	return $string;
}
function digitalizer($string) {
	return preg_replace("/[^0-9]/", "", $string);
}

#SEO
function service2html($string, $blank = true) {
	Global $siteconfig;
	if ($blank) {
		$target = " target=\"_blank\"";
	} else {
		$target = "";
	}
	foreach($siteconfig['services'] as $match=>$replacement){
		//$string = preg_replace($match, $replacement, $string);
		$string = preg_replace("/<a.*?<\/a>(*SKIP)(*F)|(\b".$match."\b)/i", "<a href=\"".$replacement."\" title=\"$1\"".$target.">$1</a>", $string);
	}
	return $string;
}
function seonizer($string) {
	$string = transliterator_transliterate("Cyrillic-Latin", $string);
	$string = preg_replace("/[^0-9a-zA-Z \-]/", "", $string);
	return preg_replace("/[ ]/", "_", $string);
}


#Encryption stuff
function jumbleup($string) {
	$split = str_split($string, 1);
	$numpair = 1;
	foreach ($split as $value) {
		if (!($numpair&1)) {
			$temp = $split[$numpair - 2];
			$split[$numpair - 2] = $value;
			$split[$numpair - 1] = $temp;
		}
		$numpair++;
	}
	$numpair = 1;
	foreach ($split as $value) {
		if (!($numpair&1)) {
			$split[$numpair - 1] = encrypt($value);
		} else {
			$split[$numpair - 1] = encrypt($value);
		}
		$numpair++;
	}
	$string = encrypt(implode("|", $split));
	return $string;
}
function jumbledown($string) {
	$split = explode("|", decrypt($string));
	$numpair = 1;
	foreach ($split as $value) {
		if (!($numpair&1)) {
			$split[$numpair - 1] = decrypt($value);
		} else {
			$split[$numpair - 1] = decrypt($value);
		}
		$numpair++;
	}
	$numpair = 1;
	foreach ($split as $value) {
		if (!($numpair&1)) {
			$temp = $split[$numpair - 2];
			$split[$numpair - 2] = $value;
			$split[$numpair - 1] = $temp;
		}
		$numpair++;
	}
	$string = implode("", $split);
	return $string;
}
function encrypt($string) {
	Global $encrypt_method, $secret_key, $secret_iv, $hash_type;
	$key = hash($hash_type, $secret_key);
	$iv = substr(hash($hash_type, $secret_iv), 0, 16);
	$string = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
	$string = base64_encode($string);
	return $string;
}
function decrypt($string) {
	Global $encrypt_method, $secret_key, $secret_iv, $hash_type;
	$key = hash($hash_type, $secret_key);
	$iv = substr(hash($hash_type, $secret_iv), 0, 16);
	$string = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
	return $string;
}

#Older functions for review an use with login
function spicing_up() {
	$characterList = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ!.,@#$%&*:;/(){}[]-_=+?";
	$i = 0;
	$cumin = "";
	$charoli = "";
	$cubeb = "";
	$mace = "";
	while ($i < 30) {
		$cumin .= $characterList{mt_rand(0, (strlen($characterList) - 1))};
		$charoli .= $characterList{mt_rand(0, (strlen($characterList) - 1))};
		$cubeb .= $characterList{mt_rand(0, (strlen($characterList) - 1))};
		$mace .= $characterList{mt_rand(0, (strlen($characterList) - 1))};
		$i++;
	}
	return array($cumin, $charoli, $cubeb, $mace);
}



function pass_hash($cumin, $charoli, $unhashed, $cubeb, $mace) {
	$hashed = hash("sha512", $unhashed);
	return hash("sha512", $cumin.substr($hashed, 0, 1).$charoli.substr($hashed, 1, -1).$cubeb.substr($hashed, -1).$mace);
}
?>