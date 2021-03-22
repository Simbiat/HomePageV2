<?php
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

#Determening what to run
if (empty($_GET['service'])) {
    $pagetype = "index";
} else {
    $pagetype = strtolower($_GET['service']);
}
#Check if we have a page
if (empty($_GET['lang'])) {
    $lang = "";
} else {
    $lang = $_GET['lang'];
}

#Emptying output, just in case
$finaloutput = "";

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
if ($pagetype == "bic") {
    //tsfd
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