#!/usr/bin/php5
<?php

$arr=array();

$table=false;
$normal=false;
$verbose=false;
$audio=false;
$bash=false;
$divide_by=1;

$md5arr=array();
$sizes=array();
$durations=array();
$fprints=array();

$i=0;


declare(ticks = 10);
$run = true;
pcntl_signal(SIGINT,  function($signo) {
	global $run;
	$run = false;
});


foreach ($argv as $k => $arg){
	if ($k!=0){
		if (substr($arg,0,1)=="-"){
			if ($arg=="-h" || $arg=="--help")		help();
			if ($arg=="-t" || $arg=="--table")		$table=true;
			if ($arg=="-n" || $arg=="--normal")		$normal=true;
			if ($arg=="-v" || $arg=="--verbose")	$verbose=true;
			if ($arg=="-a" || $arg=="--audio")		$audio=true;
			if ($arg=="-b" || $arg=="--bash")		$bash=true;
			if ($arg=="-d2")						$divide_by=2;
			if ($arg=="-d3")						$divide_by=3;
			if ($arg=="-d4")						$divide_by=4;
			if ($arg=="-d5")						$divide_by=5;
			if ($arg=="-d6")						$divide_by=6;
			if ($arg=="-d7")						$divide_by=7;
			if ($arg=="-d8")						$divide_by=8;
			if ($arg=="-d9")						$divide_by=9;
			if ($arg=="-d10")						$divide_by=10;
			if ($arg=="-d12")						$divide_by=12;
			if ($arg=="-d14")						$divide_by=14;
			if ($arg=="-d16")						$divide_by=16;
			if ($arg=="-d18")						$divide_by=18;
			if ($arg=="-d20")						$divide_by=20;
			if ($arg=="-d24")						$divide_by=24;
			if ($arg=="-d28")						$divide_by=28;
			if ($arg=="-d30")						$divide_by=30;
		} else {
			$arr[] = $arg;
		}
	}
}
if (count($arr)==0){
	$arr[]=".";
}
$total=count($arr);

if ($verbose) fwrite(STDERR, "(1 / 6)\tDefining functions.\n");

function mglob($arg){
	global $arr, $normal, $i, $verbose, $total;
	$j=$i;
	$tmp=glob($arg);
	foreach ($tmp as $tmpitem){
		if (substr($tmpitem,-3)!="/.." && substr($tmpitem,-2)!="/." && !in_array($tmpitem,$arr)){
			if ($normal && count($arr)>$j){
				$j++;
				array_splice($arr,$j,0,$tmpitem);
			} else {
				$arr[]=$tmpitem;
			}
			$total++;
			if ($verbose) fwrite(STDERR, ".");
		}
	}
}

function help(){
	global $argv;
echo "Usage: php ".$argv[0]." [options]... [directorys]...

    This php script searches duplicate files in the current directory or the one specified on the command line and show them to the standard output together with their md5sum.

    -h, --help\t\tShows This and exits.
    -t, --table\t\tShow results as table, otherwise the output is shown in groups.
    -n, --normal\tScan in normal recursivity, default is to scan first all files on current folder and then the files of direct subfolders and so on.
    -a, --audio\t\tUse Audio Fingerprint to compare audio files, needs fpcalc, mediainfo and file to be installed. Bee carefull with this option its much slower and can make mistakes.
    -b, --bash\tWrite 'rm' before all but the first file in a group (you can write 'php ".$argv[0]." | bash')
    -v, --verbose\tBe more verbose. Output is sent to the stderr so you don't need to worry about pipes.

    directory\t\tThe absolute or relative path to some directory without the last slash \"/\" default is \".\"  (current directory).
";
die();
}

if ($verbose) fwrite(STDERR, "(2 / 6)\tStarting file scan.\n");

$run = true;

while (isset($arr[$i]) && $run){
	$item = $arr[$i];
	if ($verbose) fwrite(STDERR, "(2 / 6) (".$i." / ".$total.") ".$item." ");
	if (filetype($item)=="file"){
		$size = filesize($item);
		$sizes[$size][] = $item;
		if ($audio){
			//$type=exec('file '.str_replace("?","\\?",escapeshellarg($item)));
			$type=exec('file '.escapeshellarg($item));
			if (strpos($type,"Audio")!==false ||
				strpos($type,"audio")!==false ||
				strpos($type,"Microsoft ASF")!==false ||
				strpos($type,"MPEG v4 system")!==false){
					//$duration = exec('mediainfo --Inform="General;%Duration%" '.str_replace("?","\\?",escapeshellarg($item)).'');	// General outputs always in ms, audio outputs in s if duration%1000==0
					$duration = exec('mediainfo --Inform="General;%Duration%" '.escapeshellarg($item).'');	// General outputs always in ms, audio outputs in s if duration%1000==0
					$duration = floor($duration/1000);	// ms to s
					$duration = floor($duration/$divide_by);	// to match more files while comparing durations.
					$duration = $duration * $divide_by;
					$durations[$duration][] = $item;
					if ($verbose) fwrite(STDERR, "Audio file, Duration: ".$duration." ");
			}
		}
	} else if (filetype($item)=="dir"){
		mglob($item."/*");
		mglob($item."/.*");
		mglob($item."/*[*");
		mglob($item."/*]*");
	} else if ($verbose){
		fwrite(STDERR, "Filetype ".filetype($item)." isn't file nor dir, skipping.");
	}
	if ($verbose) fwrite(STDERR, "\n");
	$i++;
}

if ($verbose) fwrite(STDERR, "(3 / 6)\tComparing sizes.\n");

//ksort($sizes);
//$total=count($sizes);
$total=count($sizes,COUNT_RECURSIVE)-count($sizes);
$i=0;

$run = true;

foreach ($sizes as $size => $sizdups){
	if (!$run) break;
	//$i++;
	if (count($sizdups)>1){
		foreach ($sizdups as $file){
			$i++;
			$md5 = md5_file($file);
			$md5arr[$md5][] = $file;
			if ($verbose) fwrite(STDERR, "(3 / 6) ".round(($i/$total)*100)."% (".$i." / ".$total.") size: ".$size." md5: ".$md5." - ".$file."\n");
		}
	} else {
		$i++;
	}
}

$run = true;

if ($audio){
	if ($verbose) fwrite(STDERR, "(4 / 6)\tComparing durations.\n");
	//$total=count($durations);
	$total=count($durations,COUNT_RECURSIVE)-count($durations);
	$i=0;
	foreach ($durations as $duration => $duration_dups){
		if (!$run) break;
		//$i++;
		if (count($duration_dups)>1){
			foreach ($duration_dups as $file){
				$i++;
				unset($output);
				exec('fpcalc -length '.$duration.' '.str_replace("?","\\?",escapeshellarg($file)),$output);
				$output = implode(" ",$output);
				$output = explode("=",$output);
				$output = end($output);
				$fprint_md5 = md5($output);
				$fprints[$fprint_md5][] = array($file,$output);
				if ($verbose) fwrite(STDERR, "(4 / 6) ".round(($i/$total)*100)."% (".$i." / ".$total.") duration: ".$duration." fprint_md5: ".$fprint_md5." - ".$file."\n");
			}
		} else {
			$i++;
		}
	}
}

if ($verbose) fwrite(STDERR, "(5 / 6)\tComparing md5.\n");

//ksort($md5arr);
$total=count($md5arr);
$i=0;
$run = true;
foreach ($md5arr as $md5 => $dups){
	if (!$run) break;
	$i++;
	if (count($dups)>1){
		if ($verbose) fwrite(STDERR, "(5 / 6) ".round(($i/$total)*100)."% (".$i." / ".$total.")\tFollowing files have same md5: ");
		if (!$table) {
			if ($bash)	echo "#".$md5."\n";
			else		echo $md5."\n";
		}
		if ($bash){
			$j=0;
			foreach ($dups as $dup){
				$search = array("'",	'(',	')',	' ',	'&');
				$replace= array("\\'",	'\\(',	'\\)',	'\\ ',	'\\&');
				$dup = str_replace($search,$replace,$dup);
				if ($j==0)	echo "# ".$dup."\n";
				else		echo "rm ".$dup."\n";
				$j++;
			}
		} else {
			foreach ($dups as $dup){
				if ($table) echo $md5." ".$dup."\n";
				else echo "\t".$dup."\n";
			}
		}
	}
}

$run = true;

if ($audio){
	if ($verbose) fwrite(STDERR, "(6 / 6)\tComparing Audio fingerprints.\n");
	//ksort($fprints);
	$total=count($fprints);
	$i=0;
	foreach ($fprints as $fprint_md5 => $fprint_dups){
		if (!$run) break;
		$i++;
		if (count($fprint_dups)>1){
			if ($verbose) fwrite(STDERR, "(6 / 6) ".round(($i/$total)*100)."% (".$i." / ".$total.")\tFollowing files have same fingerprint_md5: ");
			if (!$table) echo $fprint_md5."\n";
			foreach ($fprint_dups as $dup){
				if ($table) echo $fprint_md5." ".$dup[0]." ".substr($dup[1],0,30)."...".substr($dup[1],-30)."\n";
				else echo "\t".$dup[0]." ".substr($dup[1],0,30)."...".substr($dup[1],-30)."\n";
			}
		}
	}
}

if ($verbose) fwrite(STDERR, "Done!\n");
