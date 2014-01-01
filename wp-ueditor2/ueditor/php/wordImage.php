<?php
/**
 * Created by JetBrains PhpStorm.
 * User: taoqili
 * Date: 11-12-28
 * Time: 上午9:54
 * To change this template use File | Settings | File Templates.
 */
header("Content-Type: text/html; charset=utf-8");
error_reporting(E_ERROR|E_WARNING);


include "Uploader.class.php";
//远程抓取图片配置
$config = array(
    "savePath" => "upload/" , //保存路径
    "allowFiles" => array( ".gif" , ".png" , ".jpg" , ".jpeg" , ".bmp" ) , //文件允许格式
    "maxSize" => 100000, //文件大小限制，单位KB
    'fileField'=>'upfile'
);
$up = new Uploader( 'upfile' , $config );
$info = $up->getFileInfo();

echo "{'url':'" . $info["url"] . "','title':'" . $title . "','original':'" . $info["originalName"] . "','state':'" . $info["state"] . "'}";
