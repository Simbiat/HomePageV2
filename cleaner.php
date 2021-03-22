<?php
$cachedir = getcwd()."/backend/cache/";
recursiveRemove(getcwd()."/backend/cache/");
function recursiveRemove($dir) {
	global $cachedir;
	$structure = glob(rtrim($dir, "/").'/*');
		if (is_array($structure)) {
			foreach($structure as $file) {
				if (is_dir($file)) {
					recursiveRemove($file);
				} elseif (is_file($file)) {
					echo "Removed ".str_replace($cachedir, "", $file)."<br>";
					@unlink($file);
				}
        		}
		}
	if ($dir != getcwd()."/backend/cache/") {
		@rmdir($dir);
	}
}
?>