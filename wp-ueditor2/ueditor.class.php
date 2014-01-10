<?php
/**
 * Created by JetBrains PhpStorm.
 * User: taoqili
 * Date: 12-4-6
 * Time: 下午4:00
 * To change this template use File | Settings | File Templates.
 */
if ( !class_exists( "UEditor" ) ) {
    /**
     * UEditor插件类
     */
    class UEditor{
        private  $renderId      = "" ;             //编辑器容器ID
        private  $customConfigs = array();        //ue配置

        function  __construct($id,$arr){
            $this->renderId = $id;
            $this->customConfigs = $this->getOptions($arr);
        }

        function getOptions($arr){
            return !$arr? '{}':json_encode($arr);
        }

        /**
         * 当开启UEditor插件时，关闭默认的编辑器
         */
        function ue_closeDefaultEditor(){
            if(!get_option("close_default_editor")){
                add_option("close_default_editor");
            }
            update_option("close_default_editor","true");
        }
        /**
         * 当关闭UEditor插件时，开启wp默认的编辑器
         */
        function ue_openDefaultEditor(){
            update_option("close_default_editor","false");
        }
        /**
         * 在前台展示页面显示代码高亮
         */
        function ue_importSyntaxHighlighter(){
            $url = plugin_dir_url(__FILE__);
            echo  '<script type="text/javascript" src="'.$url.'ueditor/third-party/SyntaxHighlighter/shCore.js"></script>';
            echo  '<link type="text/css" rel="stylesheet" href=" '.$url.'ueditor/third-party/SyntaxHighlighter/shCoreDefault.css" />';
        }
        function ue_syntaxHighlighter(){
            echo '<script type="text/javascript">SyntaxHighlighter.all();</script>';
        }

        /**
         * 导入UEditor资源
         */
        function ue_importUEditorResource(){
            $url = plugin_dir_url(__FILE__);
            echo '
<script type="text/javascript">
    window.UEDITOR_HOME_URL="'.$url .'ueditor/";
</script>
<script type="text/javascript" src="'.$url.'ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="'.$url.'ueditor/ueditor.all.js"></script>
<link type="text/css" rel="stylesheet" href=" '.$url.'ueditor/themes/default/css/ueditor.css" />
';
        }

        /**
         *实例化编辑器
         */
        function ue_renderUEditor(){
            echo '
<script type="text/javascript">
    var wp_ueditor = new baidu.editor.ui.Editor(' . $this->customConfigs .');
    wp_ueditor.render("'.$this->renderId.'");
    wp_ueditor.addListener( "ready", function( editor ) {
        wp_ueditor.setContent(wp_ueditor_content, false, false);
    } );

    var mce_statusbar = document.getElementById("post-status-info");
    mce_statusbar.parentNode.removeChild(mce_statusbar);
    window.addEventListener("load", function(){
            var fullscreenDiv = document.getElementById("wp-fullscreen-body");
            fullscreenDiv.parentNode.removeChild(fullscreenDiv);
        }, false);
</script>
';
//            wp_ueditor.render("'.$this->renderId.'");
        }
    }
}