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
    'remoteImg'=>true
);

$uri = htmlspecialchars( $_POST[ 'upfile' ] );
$uri = str_replace( "&amp;" , "&" , $uri );
$imgUrls = explode( "ue_separate_ue" , $uri );
$tmpNames = array();
foreach ( $imgUrls as $imgUrl ) {
    $up = new Uploader( $imgUrl , $config );
    $result = $up->getFileInfo();
    if( $result['state'] == 'SUCCESS' ){
        array_push( $tmpNames , $result['url'] );
    }
}
/**
 * 返回数据格式
 * {
 *   'url'   : '新地址一ue_separate_ue新地址二ue_separate_ue新地址三',
 *   'srcUrl': '原始地址一ue_separate_ue原始地址二ue_separate_ue原始地址三'，
 *   'tip'   : '状态提示'
 * }
 */
echo "{'url':'" . implode( "ue_separate_ue" , $tmpNames ) . "','tip':'远程图片抓取成功！','srcUrl':'" . $uri . "'}";
