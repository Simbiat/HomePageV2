<?php
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
?>