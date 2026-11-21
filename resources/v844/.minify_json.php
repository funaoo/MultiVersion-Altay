<?php

declare(strict_types=1);

$ext = ".json";
$extLen = strlen($ext);
foreach(scandir(__DIR__, SCANDIR_SORT_NONE) as $file){
	if(substr($file, -$extLen) === $ext){
		$file = __DIR__ . "/$file";
		file_put_contents($file, json_encode(json_decode(file_get_contents($file))));
		echo "Minified JSON file $file\n";
	}
}
