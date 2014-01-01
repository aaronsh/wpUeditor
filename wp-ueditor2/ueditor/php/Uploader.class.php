<?php
/**
 * Created by JetBrains PhpStorm.
 * User: taoqili
 * Date: 12-7-18
 * Time: 上午11: 32
 * UEditor编辑器通用上传类
 */
class Uploader
{
    private $fileField;            //文件域名
    private $file;                 //文件上传对象
    private $config;               //配置信息
    private $oriName;              //原始文件名
    private $fileName;             //新文件名
    private $fullName;             //完整文件名,即从当前配置目录开始的URL
    private $fileSize;             //文件大小
    private $fileType;             //文件类型
    private $stateInfo;            //上传状态信息,
    private $stateMap = array(    //上传状态映射表，国际化用户需考虑此处数据的国际化
        "SUCCESS" ,                //上传成功标记，在UEditor中内不可改变，否则flash判断会出错
        "文件大小超出 upload_max_filesize 限制" ,
        "文件大小超出 MAX_FILE_SIZE 限制" ,
        "文件未被完整上传" ,
        "没有文件被上传" ,
        "上传文件为空" ,
        "POST" => "文件大小超出 post_max_size 限制" ,
        "SIZE" => "文件大小超出网站限制" ,
        "TYPE" => "不允许的文件类型" ,
        "DIR" => "目录创建失败" ,
        "IO" => "输入输出错误" ,
        "UNKNOWN" => "未知错误" ,
        "MOVE" => "文件保存时出错",
        "MEDIA" => "无法使用系统媒体库"
    );

    /**
     * 构造函数
     * @param string $fileField 表单名称
     * @param array $config  配置项
     * @param bool $base64  是否解析base64编码，可省略。若开启，则$fileField代表的是base64编码的字符串表单名
     */
    public function __construct( $fileField , $config , $base64 = false )
    {
        $this->fileField = $fileField;
        $this->config = $config;
        $this->stateInfo = $this->stateMap[ 0 ];
        $this->upFile( $base64 );
    }

    /**
     * 上传文件的主处理方法
     * @param $base64
     * @return mixed
     */
    private function upFile( $base64 )
    {
        if( function_exists('sys_get_temp_dir') ){
            $this->config[ 'savePath' ] = sys_get_temp_dir();
        }
        //处理base64上传
        if ( "base64" == $base64 ) {
            $content = $_POST[ $this->fileField ];
            $this->base64ToImage( $content );
            return;
        }

        if( isset($this->config['remoteImg']) && $this->config['remoteImg'] ){
            $this->remoteImages( $this->fileField);
            return;
        }

        //处理普通上传
        $file = $this->file = $_FILES[ $this->fileField ];
        if ( !$file ) {
            $this->stateInfo = $this->getStateInfo( 'POST' );
            return;
        }
        if ( $this->file[ 'error' ] ) {
            $this->stateInfo = $this->getStateInfo( $file[ 'error' ] );
            return;
        }
        if ( !is_uploaded_file( $file[ 'tmp_name' ] ) ) {
            $this->stateInfo = $this->getStateInfo( "UNKNOWN" );
            return;
        }

        $this->oriName = $file[ 'name' ];
        $this->fileSize = $file[ 'size' ];
        $this->fileType = $this->getFileExt();

        if ( !$this->checkSize() ) {
            $this->stateInfo = $this->getStateInfo( "SIZE" );
            return;
        }
        if ( !$this->checkType() ) {
            $this->stateInfo = $this->getStateInfo( "TYPE" );
            return;
        }

        if ( ! function_exists( 'media_handle_upload' ) || ! function_exists( 'wp_get_attachment_url' ) ) {
            $this->stateInfo = $this->getStateInfo( 'MEDIA' );
            return;
        }

        if ( $this->stateInfo == $this->stateMap[ 0 ] ) {
            $rsid = media_handle_upload("upfile", $_REQUEST['post_id']);
            if( is_object($rsid) && get_class($rsid) === 'WP_Error' ){
                $err_text = "";
                foreach($rsid->errors as $val){
                    if( is_array ($val) && count($val) > 0 ){
                        $err_text = end($val);
                        break;
                    }
                }
                $this->stateInfo = $this->getStateInfo( $err_text );
                return;
            }
            $url = wp_get_attachment_url($rsid);
            if ( $url === false ) {
                $this->stateInfo = $this->getStateInfo( "MOVE" );
                return;
            }
            $this->fullName = $url;
            $this->stateInfo = $this->getStateInfo( "SUCCESS" );
        }
    }

    /**
     * 处理base64编码的图片上传
     * @param $base64Data
     * @return mixed
     */
    private function base64ToImage( $base64Data )
    {
        $img = base64_decode( $base64Data );
        $this->fileName = time() . rand( 1 , 10000 ) . ".png";
        $this->fullName = $this->getFolder() . '/' . $this->fileName;
        if ( !file_put_contents( $this->fullName , $img ) ) {
            $this->stateInfo = $this->getStateInfo( "IO" );
            return;
        }
        $this->oriName = "";
        $this->fileSize = strlen( $img );
        $this->fileType = ".png";

        $file_array['name'] = $this->fileName;
        $file_array['tmp_name'] = $this->fullName;

        // do the validation and storage stuff
        $id = media_handle_sideload( $file_array, $_REQUEST['post_id'] );
        // If error storing permanently, unlink
        if ( is_wp_error($id) ) {
            @unlink($file_array['tmp_name']);
            $this->stateInfo = $this->getStateInfo( "UNKNOWN" );
            return ;
        }

        $url = wp_get_attachment_url( $id );
        if ( $url === false ) {
            $this->stateInfo = $this->getStateInfo( "MOVE" );
            return;
        }
        $this->fullName = $url;
        $this->stateInfo = $this->getStateInfo( "SUCCESS" );
    }

    /**
     * 处理远程图片抓取
     * @param $base64Data
     * @return mixed
     */
    private function remoteImages( $imgUrl )
    {
        $tmpFile = $this->getRemoteImage($imgUrl);
        if( $tmpFile == false){
            $this->stateInfo = $this->getStateInfo( "UNKNOWN" );
            return ;
        }

        $file_array['name'] = $this->fileName;
        $file_array['tmp_name'] = $this->fullName;

        // do the validation and storage stuff
        $id = media_handle_sideload( $file_array, $_REQUEST['post_id'] );
        // If error storing permanently, unlink
        if ( is_wp_error($id) ) {
            @unlink($file_array['tmp_name']);
            $this->stateInfo = $this->getStateInfo( "UNKNOWN" );
            return ;
        }

        $url = wp_get_attachment_url( $id );
        if ( $url === false ) {
            $this->stateInfo = $this->getStateInfo( "MOVE" );
            return;
        }
        $this->fullName = $url;
        $this->stateInfo = $this->getStateInfo( "SUCCESS" );
    }

    function getRemoteImage( $imgUrl)
    {
        //忽略抓取时间限制
        set_time_limit( 0 );

        //http开头验证
        if(strpos($imgUrl,"http")!==0){
            return false;
        }
        //获取请求头
        $heads = get_headers( $imgUrl );
        //死链检测
        if ( !( stristr( $heads[ 0 ] , "200" ) && stristr( $heads[ 0 ] , "OK" ) ) ) {
            return false;
        }

        //格式验证(扩展名验证和Content-Type验证)
        $fileType = strtolower( strrchr( $imgUrl , '.' ) );
        if ( !in_array( $fileType , $this->config[ 'allowFiles' ] ) || stristr( $heads[ 'Content-Type' ] , "image" ) ) {
            return false;
        }
        $this->fileType = $fileType;

        //打开输出缓冲区并获取远程图片
        ob_start();
        $context = stream_context_create(
            array (
                'http' => array (
                    'follow_location' => false // don't follow redirects
                )
            )
        );
        //请确保php.ini中的fopen wrappers已经激活
        readfile( $imgUrl,false,$context);
        $img = ob_get_contents();
        ob_end_clean();

        //大小验证
        $uriSize = strlen( $img ); //得到图片大小
        $allowSize = 1024 * $this->config[ 'maxSize' ];
        if ( $uriSize > $allowSize ) {
            return false;
        }
        //创建保存位置
        $savePath = $this->config[ 'savePath' ];
        $tmpName =  rand( 1 , 10000 ) . time() . strrchr( $imgUrl , '.' );
        $this->fileName = $tmpName;
        $this->fullName = $this->getFolder() . '/' . $this->fileName;
        //写入文件
        try {
            $fp2 = @fopen( $this->fullName , "a" );
            fwrite( $fp2 , $img );
            fclose( $fp2 );
            return $this->fullName;
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * 获取当前上传成功文件的各项信息
     * @return array
     */
    public function getFileInfo()
    {
        return array(
            "originalName" => $this->oriName ,
            "name" => $this->fileName ,
            "url" => $this->fullName ,
            "size" => $this->fileSize ,
            "type" => $this->fileType ,
            "state" => $this->stateInfo
        );
    }

    /**
     * 上传错误检查
     * @param $errCode
     * @return string
     */
    private function getStateInfo( $errCode )
    {

        return isset($this->stateMap[ $errCode ]) ?  $this->stateMap[ $errCode ]:$errCode;
    }

    /**
     * 重命名文件
     * @return string
     */
    private function getName()
    {
        return $this->fileName = time() . rand( 1 , 10000 ) . $this->getFileExt();
    }

    /**
     * 文件类型检测
     * @return bool
     */
    private function checkType()
    {
        return in_array( $this->getFileExt() , $this->config[ "allowFiles" ] );
    }

    /**
     * 文件大小检测
     * @return bool
     */
    private function  checkSize()
    {
        return $this->fileSize <= ( $this->config[ "maxSize" ] * 1024 );
    }

    /**
     * 获取文件扩展名
     * @return string
     */
    private function getFileExt()
    {
        return strtolower( strrchr( $this->file[ "name" ] , '.' ) );
    }

    /**
     * 按照日期自动创建存储文件夹
     * @return string
     */
    private function getFolder()
    {
        $pathStr = $this->config[ "savePath" ];
        if ( strrchr( $pathStr , "/" ) != "/" ) {
            $pathStr .= "/";
        }
        $pathStr .= date( "Ymd" );
        if ( !file_exists( $pathStr ) ) {
            if ( !mkdir( $pathStr , 0777 , true ) ) {
                return false;
            }
        }
        return $pathStr;
    }
}