<?php
    /**
     * Created by JetBrains PhpStorm.
     * User: taoqili
     * Date: 12-1-16
     * Time: 上午11:44
     * To change this template use File | Settings | File Templates.
     */
    header("Content-Type: text/html; charset=utf-8");
    error_reporting( E_ERROR | E_WARNING );

    $upload_config = wp_upload_dir();

    global $wpdb;
    $pics = array('gif','jpeg','jpg','png','bmp');
    $condition = null;
    foreach($pics as $type){
        if( $condition == null){
            $condition = "`meta_value` LIKE '%.$type.'";
        }
        else{
            $condition = $condition." OR `meta_value` LIKE '%.$type'";
        }
    }
    $sql = "SELECT * FROM {$wpdb->postmeta} WHERE {$condition} ORDER BY `meta_id` DESC ;";
    $myrows = $wpdb->get_results( $sql );
    if($myrows != false){
        $str = "";
        foreach ( $myrows as $row ) {
            if(stripos($row->meta_value, 'http') === 0 ){
                $str .= $row->meta_value . "ue_separate_ue";
            }
            else{
                $str .= $upload_config['baseurl'].'/'.$row->meta_value . "ue_separate_ue";
            }
        }
        echo $str;
    }
    else{
        echo '';
    }
    exit;
