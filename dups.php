#!/usr/bin/php5
<?php

$arr=array();

$table=false;
$normal=false;
$verbose=false;
$audio=false;
$bash=false;
$divide_by=1;
$store_fp=false;
$random=false;

$fpext='fp';

$md5arr=array();
$sizes=array();
$durations=array();
$fprints=array();

$i=0;

$files=0;

declare(ticks = 10);
$run = true;
pcntl_signal(SIGINT,  function($signo) {
    global $run;
    $run = false;
});


foreach ($argv as $k => $arg){
    if ($k!=0){
        if (substr($arg,0,1)=="-"){
            if ($arg=="-h" || $arg=="--help")       help();
            if ($arg=="-t" || $arg=="--table")      $table=true;
            if ($arg=="-n" || $arg=="--normal")     $normal=true;
            if ($arg=="-v" || $arg=="--verbose")    $verbose=true;
            if ($arg=="-a" || $arg=="--audio")      $audio=true;
            if ($arg=="-b" || $arg=="--bash")       $bash=true;
            if ($arg=="-r" || $arg=="--random")     $random=true;
            if ($arg=="-fp")                        $store_fp=true;
            if ($arg=="-d2")                        $divide_by=2;
            if ($arg=="-d3")                        $divide_by=3;
            if ($arg=="-d4")                        $divide_by=4;
            if ($arg=="-d5")                        $divide_by=5;
            if ($arg=="-d6")                        $divide_by=6;
            if ($arg=="-d7")                        $divide_by=7;
            if ($arg=="-d8")                        $divide_by=8;
            if ($arg=="-d9")                        $divide_by=9;
            if ($arg=="-d10")                       $divide_by=10;
            if ($arg=="-d12")                       $divide_by=12;
            if ($arg=="-d14")                       $divide_by=14;
            if ($arg=="-d16")                       $divide_by=16;
            if ($arg=="-d18")                       $divide_by=18;
            if ($arg=="-d20")                       $divide_by=20;
            if ($arg=="-d24")                       $divide_by=24;
            if ($arg=="-d28")                       $divide_by=28;
            if ($arg=="-d30")                       $divide_by=30;
        } else {
            $arr[] = $arg;
        }
    }
}
if (count($arr)==0){
    $arr[]=".";
}
$total=count($arr);

if ($verbose) fwrite(STDERR, "Step: 1/6\tDefining functions.\n");

function mglob($arg){
    global $arr, $normal, $i, $verbose, $total, $random;
    $j=$i;
    $tmp=glob($arg);
    if ($random){
        shuffle($tmp);
    }
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
    -n, --normal\tScan in normal recursive mode, default is to scan first all files on current folder and then the files of direct subfolders and so on.
    -a, --audio\t\tUse Audio Fingerprint to compare audio files, needs fpcalc, mediainfo and file to be installed. Bee carefull with this option its much slower and can make mistakes.
    -b, --bash\t\tWrite 'rm' before all but the first file in a group (you can write 'php ".$argv[0]." | bash')
    -v, --verbose\tBe more verbose. Output is sent to the stderr so you don't need to worry about pipes.
    -fp\t\t\tAllows me to store fingerprints from files in other files with the same name but adding some extra extention.

    directory\t\tThe absolute or relative path to some directory without the last slash \"/\" default is \".\"  (current directory).
";
die();
}

//http://php.net/manual/en/function.filesize.php#106569
function human_filesize($bytes, $decimals = 2) {
    $sz = 'BKMGTP';
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

function human_time($time){
    $s=$time%60;
    $m=floor($time/60)%60;
    $h=floor($time/3600);
    return $h.":".$m.":".$s;
}

//http://php.net/manual/en/function.shuffle.php#94697
//this is just for fun
function shuffle_assoc(&$array) {
    $new = array();
    $keys = array_keys($array);
    shuffle($keys);
    foreach($keys as $key) {
        $new[$key] = $array[$key];
    }
    $array = $new;
    return true;
}
function msubstr($str,$len){
    if (strlen($str)<=$len)    return $str;
    $len = floor(($len - 3)/2);
    return substr($str,0,$len)."...".substr($str,-$len);
}

if ($verbose) fwrite(STDERR, "\nStep: 2/6\tStarting file scan.\n");

$run = true;

while (isset($arr[$i]) && $run){
    $item = $arr[$i];
    if ($verbose) fwrite(STDERR, round(($files/$total)*100)."% ".$files."/".$total."\t".$item."");
    if (filetype($item)=="file"){
        $files++;
        if (substr($item,-strlen($fpext))==$fpext){
            if ($verbose) fwrite(STDERR, " skipping fingerprint file ");
        } else {
            $size = filesize($item);
            $sizes[$size][] = $item;
            if ($audio){
                //$type=exec('file '.str_replace("?","\\?",escapeshellarg($item)));
                $type=exec('file '.escapeshellarg($item));
                if (strpos($type,"Audio")!==false ||
                    strpos($type,"audio")!==false ||
                    strpos($type,"Microsoft ASF")!==false ||
                    strpos($type,"MPEG v4 system")!==false){
                        $duration = exec('mediainfo --Inform="General;%Duration%" '.escapeshellarg($item).'');  // General outputs always in ms, audio outputs in s if duration%1000==0
                        $duration = floor($duration/1000);  // ms to s
                        $duration = floor($duration/$divide_by);    // to match more files while comparing durations.
                        $duration = $duration * $divide_by;
                        $durations[$duration][] = $item;
                        if ($verbose) fwrite(STDERR, "\tAudio ".human_time($duration));
                }
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

if ($verbose) fwrite(STDERR, "\n(3 / 6)\tComparing sizes.\n");

foreach ($sizes as $size => $files){
    if (count($files)==1){
        unset($sizes[$size]);
    }
}

if ($random){
    shuffle_assoc($sizes);
}

$total=count($sizes);
$i=0;

$run = true;

foreach ($sizes as $size => $sizdups){
    if (!$run) break;
    $i++;
    if ($verbose) fwrite(STDERR, round(($i/$total)*100)."% ".$i."/".$total."\t".human_filesize($size)."\n");
    foreach ($sizdups as $file){
        $md5 = md5_file($file);
        $md5arr[$md5][] = $file;
        if ($verbose) fwrite(STDERR, "\tmd5: ".msubstr($md5,18)." ".$file."\n");
    }
}

$run = true;

if ($audio){
    if ($verbose) fwrite(STDERR, "\n(4 / 6)\tComparing durations.\n");

    foreach ($durations as $duration => $files){
        if (count($files)==1){
            unset($durations[$duration]);
        }
    }

    if ($random){
        shuffle_assoc($durations);
    }
    $total=count($durations);
    $i=0;
    foreach ($durations as $duration => $duration_dups){
        if (!$run) break;
        $i++;
        if ($verbose) fwrite(STDERR, round(($i/$total)*100)."% ".$i."/".$total."\t".human_time($duration)."\n");
        foreach ($duration_dups as $file){
            unset($output);
            if (file_exists($file.'.'.$fpext)){
                $output = file_get_contents($file.'.'.$fpext);
                $fprint_md5=md5_file($file.'.'.$fpext);
            } else {
                exec('fpcalc -length '.$duration.' '.str_replace("?","\\?",escapeshellarg($file)),$output);
                $output = implode(" ",$output);
                $output = explode("=",$output);
                $output = end($output);
                $fprint_md5 = md5($output);
                if ($store_fp){
                    file_put_contents($file.'.'.$fpext, $output);
                }
            }
            if (strlen($output)<=6){//invalid output: 'AQAAAA' or ''
                if ($verbose) fwrite(STDERR, "\tSkipping ".$file."\n");
            }else {
                $fprints[$fprint_md5][] = array($file,$output);
                if ($verbose) fwrite(STDERR, "\tfprint_md5: ".msubstr($fprint_md5,18)." ".$file."\n");
            }
        }
    }
}

if ($verbose) fwrite(STDERR, "\n(5 / 6)\tComparing md5.\n");

//ksort($md5arr);
$total=count($md5arr);
$i=0;
$run = true;
foreach ($md5arr as $md5 => $dups){
    if (!$run) break;
    $i++;
    if (count($dups)>1){
        if ($verbose) fwrite(STDERR, round(($i/$total)*100)."% ".$i."/".$total."\tFollowing files have same md5: ");
        if (!$table) {
            if ($bash)  echo "#".$md5."\n";
            else        echo $md5."\n";
        }
        if ($bash){
            $j=0;
            foreach ($dups as $dup){
                $dup=escapeshellarg($dup);
                if ($j==0)  echo "# ".$dup."\n";
                else        echo "rm ".$dup."\n";
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
    if ($verbose) fwrite(STDERR, "\n(6 / 6)\tComparing Audio fingerprints.\n");
    //ksort($fprints);
    $total=count($fprints);
    $i=0;
    foreach ($fprints as $fprint_md5 => $fprint_dups){
        if (!$run) break;
        $i++;
        if (count($fprint_dups)>1){
            if ($verbose) fwrite(STDERR, "\n".round(($i/$total)*100)."% ".$i."/".$total."\tFollowing files have same fingerprint_md5: ");
            if (!$table) echo $fprint_md5."\n";
            foreach ($fprint_dups as $dup){
                if ($table) echo $fprint_md5." ".$dup[0]."\t".msubstr($dup[1],43)."\n";
                else        echo "\t".$dup[0]."\t".msubstr($dup[1],43)."\n";
            }
        }
    }
}

if ($verbose) fwrite(STDERR, "Done!\n");
