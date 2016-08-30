<?php
$collect = "all"; // change the value if you will catch anything from jcatchList.txt repetitively

function dump($str){
	print_r($str);
}

function checkRecord($pok){
	//Check from Master list
	$pList = trim(@file_get_contents("jplist.txt"));
	if(strpos($pList, $pok) !== false){
		return false;
	}
	//ADD TO RECORD
	$fp = fopen('jplist.txt', 'a');
	fwrite($fp, $pok."\n");
	fclose($fp);
	return true;

}

function snipe($pok){
	exec("START \"\" pokesniper2://".$pok['name']."/".$pok['coords']);
}

function parse1(){
	$str = @file_get_contents("http://pokesnipers.com/api/v1/pokemon.json?referrer=home");
	$str = json_decode($str,true);
	return $str = $str['results'];
}

function parse2(){
	$str = @file_get_contents("http://pokegosnipers.com/api/v1/pokemon.json");
	$str = json_decode($str,true);
	return $str = $str['results'];
}

function parse3(){
	$str = @file_get_contents("http://pokesniper.org/newapiman.txt");
	return $str = json_decode($str,true);
}

function getList(){
	$cList = trim(file_get_contents("jcatchList.txt"));
	$cList = str_replace("\r","",$cList);
	return explode("\n",$cList);
}

function resetLog(){
	$fp = fopen('PokeSniper2.log', 'w');
	fwrite($fp, "");
	fclose($fp);
}

function checkSniper(){
	for($c=0;;$c++){
		$caught = @file_get_contents("PokeSniper2.log");
		if (
			(strpos($caught, 'caught') !== false) ||
			(strpos($caught, 'away') !== false) ||
			(strpos($caught, 'space') !== false) ||
			(strpos($caught, 'without') !== false) ||
			(strpos($caught, 'Object reference') !== false) ||
			(strpos($caught, 'Exception') !== false) ||
			(strpos($caught, 'There is no') !== false) 
		){
			sleep(3);
			@exec("taskkill /IM PokeSniper2.exe /F");
			break;
		}
	}
}


$catchList = getList();
file_put_contents('jplist.txt', "");
for($ctr=0;;$ctr++){
	//$str = parse2();
	$str = array_merge(parse1(),parse2());
	$str = array_merge($str,parse3());
	for($c=0;$c<count($str);$c++){
		if(in_array($str[$c]['name'], $catchList)) { 
			$try = checkRecord("pokesniper2://".$str[$c]['name']."/".$str[$c]['coords']);
		}else{$try=false;
			echo $str[$c]['name']. " : ";
		}
		if($try){
			resetLog();
			echo "Catching ".$str[$c]['name']." in ".$str[$c]['coords']." (20s)\n";
			snipe($str[$c]);checkSniper();
			$caught = @file_get_contents("PokeSniper2.log");
			if (strpos($caught, 'caught') !== false) {
				if($collect == "all"){
					$cList=file_get_contents('jcatchList.txt');
					$cList=str_replace($str[$c]['name']."\r\n", "",$cList);
					file_put_contents('jcatchList.txt', $cList);
					$catchList = getList();
				}
				echo "\033[32m".$str[$c]['name']." was caught!\033[0m\n";
			}elseif (strpos($caught, 'away') !== false) {
				echo "\033[31m".$str[$c]['name']." ran away.\033[0m\n";
			}elseif (strpos($caught, 'space') !== false) {
				echo "\033[31mThere is not space for new Pokemon.\033[0m\n";
				die();
			}elseif (strpos($caught, 'Exception') !== false) {
				echo "\033[31mSniper failed to login.\033[0m\n";
			}elseif (strpos($caught, 'without') !== false) {
				echo "\033[31mGot into the fight without any Pokeballs.\033[0m\n";
				die();
			}else{
				echo "\033[31m".$str[$c]['name']." not found.\033[0m\n";
			}
		}
	}
echo "---------LOOP: ".($ctr+1).": Waiting 3sec\n";
sleep(3);
}

?>