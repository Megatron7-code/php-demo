<?php
/**
 * 用来计算所有接口列表及总数
 * Created by PhpStorm.
 * User: Megatron
 * Date: 2017/2/10
 * Time: 11:55
 */

/**
 * 读取文件行,利用生成器
 * @param $file
 */
function getLines($file){
    @$fileHandle = fopen($file, 'r');
    if($fileHandle === false){
        echo "【".$file."】,文件不存在\n";
        yield false;
    }
    try {
        while ($line = @fgets($fileHandle)) {
            yield $line;
        }
    }finally{
        @fclose($fileHandle);
        yield "end-xs";
    }
}

/**
 * 获取函数名
 * @param $file
 */
function getFunctionName($file){
    preg_match('/\w+\.class.php/',$file,$class);
    $functionList = array();
    $current_line = 0;
    $num = 0;
    foreach(getLines($file) as $n=>$line){
        $num = $n;
        //echo "$n=>$line\n";
        //如果是私有方法跳过
        if(preg_match('/private/',$line) || preg_match('/function\s+\d+/',$line) || preg_match('/function\s+ceshi/',$line) || preg_match('/function\s+_{1,2}\w+/',$line)){
            continue;
        }
        if(preg_match('/function\s+\w+/',$line,$str)){
            if($current_line != 0){
                $temp = $n - $current_line;
                $sum = count($functionList);
                if($sum > 0){
                    echo $functionList[$sum - 1]." ,共计".$temp."行\n";
                }
            }
            $functionList[] = '【'.$class[0].'】'.$str[0];
            $current_line = $n;
        }

        //处理最后一个函数的行数
        if($line === 'end-xs'){
            $temp = $num - $current_line;
            $sum = count($functionList);
            if($sum > 0){
                echo $functionList[$sum - 1]." ,共计".$temp."行\n";
            }
        }
    }

    return count($functionList);
}

/**
 * 函数行数升级版
 * @param $file
 * @return int
 */
function getFunctionNameFinallyVersion($file){
    preg_match('/\w+\.class.php/',$file,$class);
    $functionList = array();
    $current_line = 0;
    $num = 1;
    $function_name = '';
    foreach(getLines($file) as $n=>$line){
        //echo "$n=>$line\n";
        //如果是私有方法跳过
        if(preg_match('/private/',$line) || preg_match('/function\s+\d+/',$line) || preg_match('/function\s+ceshi/',$line) || preg_match('/function\s+_{1,2}\w+/',$line)){
            continue;
        }
        //获取函数名
        if(preg_match('/function\s+\w+/',$line,$str)){
            $function_name = $str[0];
            $functionList[] = '【'.$class[0].'】'.$function_name;
            $current_line = $n;
            $num++;
        }
        //统计行数
        if($num != 1){
            if(preg_match_all('/{/',$line,$count)){
                $num += count($count[0]);
            }

            if(preg_match_all('/}/',$line,$count)){
                $num -= count($count[0]);
            }

            //最后一个花括号
            if($num == 2 && $n != $current_line){
                //计算函数行 当前行 - 函数起始行 + 1（函数签名占用行,算头不算尾）
                $temp = $n - $current_line + 1;
                $sum = count($functionList);
                if($sum > 0){
                    echo $functionList[$sum - 1]." ,共计".$temp."行\n";
                }
                $num = 1;
            }
        }
    }

    return count($functionList);
}

/**
 * 生成目录树
 * @param $directory
 */
function tree($directory){
    $mydir = dir($directory);
    $temp = [];
    while($file = $mydir->read()){
        //如果是目录则继续向下寻找 深度递归
        if(is_dir($directory."/".$file) && $file != "." && $file != ".."){
            tree($directory."/".$file);
        }elseif(preg_match("/\\w[a-zA-Z0-9]+\\.class.php/", $file, $str)){
            //echo $str[0]."\n";
            $temp[] = $str[0];
        }
    }
    $mydir->close();
    return $temp;
}

$dir = "/www/merchant-api/Apps/Home/Controller";
$user_api_air = "/www/user-api/Apps/Home/Controller";
$docs = tree($user_api_air);
$temp = 0;
foreach ($docs as $k=>$val){
    $temp += getFunctionNameFinallyVersion($user_api_air.'/'.$val);
}

echo "总计:".$temp." 个接口";
