<?php
// exchange calculator
function from_euro (float $sum, string $currency, bool $reverse=FALSE){
	$arrCur=getConfig('currency_now.cfg');
	$result = $sum;
	foreach ($arrCur as $k=>$v){
		if ($currency == $arrCur[$k][0]) $result = $reverse ? (real) $sum/$arrCur[$k][1] : (real) $sum*$arrCur[$k][1];
	};
	return $result;
}
// function for digital format
function format(float $sum, string $currency) {           
		if ($currency=='JPY') {return ceil($sum);
			}else {return sprintf("%.2f",ceil($sum*100)/100);}
}
// main function for calculate fee
function calculate_fee(array $input) {		              
	static $weeks=[];
	$sDate = $input[0];
	$sId = $input[1];
	$sPerson = $input[2];
	$sTrans = $input[3];
	$sSum = $input[4];
	$sCurrency = $input[5];
	$commission = 0.00;
	switch ($sTrans) {
		case "cash_in":
			$tmp = from_euro(0.50, $sCurrency);
			$commission = ($tmp<$sSum*0.003 ? $tmp : $sSum*0.003);
		break;
		case "cash_out":
			switch ($sPerson) {
				case "legal":
					$tmp = from_euro(5, $sCurrency);
					$commission = ($tmp > $sSum*0.03 ? $tmp : $sSum*0.03);
				break;
				case "natural":
					$clidweek = $sId*1000000+date("oW", strtotime($sDate));
						if (!array_key_exists($clidweek,$weeks)) {
							$weeks[$clidweek] = [
								"remain_transactions" => 0,
								"remain_sum" => 1000
							];
						};
					$thisweek = $weeks[$clidweek];
					$commission = 0.00;
					if ($thisweek["remain_transactions"]++ > 3) {
						$commission = $sSum*0.003;
					} else  {
							$remainSum = from_euro($thisweek["remain_sum"], $sCurrency);
							if ($remainSum >= $sSum) {
								$commission = 0;
								$thisweek["remain_sum"] -= from_euro($sSum, $sCurrency, TRUE);
							} else  {
									$commission = ($sSum - $remainSum) * 0.003;
									$thisweek["remain_sum"] = 0;
									};
							};
							$weeks[$clidweek] = $thisweek;
			};
	};
	return format($commission,$sCurrency);
}
// it's finish with custom subdirectory
function inOutDir(string $dir){          
	$dir=__DIR__."/".$dir."/";
	if (is_dir($dir)) {
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if (is_file($dir.$file)){
					$fr = fopen($dir.$file,"r");
					while ( $line = fgets($fr) ){
					$arr=explode(',', trim($line));
					echo calculate_fee($arr) . "\n";
					}
					fclose($fr);
				}
			}
        closedir($dh);
		}
	}
}
//function for pull currencies and prices. You can change or add currency in .cfg file
function getConfig(string $file){	
	$stringFile=__DIR__."/".$file;
	$config=[];
		if (is_file($stringFile)){
			$fr1 = fopen($stringFile,"r");
			while ( $line = fgets($fr1) ){
			array_push($config,explode(':', trim($line)));
			}
			fclose($fr1);
		}
	return $config;
}