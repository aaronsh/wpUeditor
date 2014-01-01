<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
    <script type="text/javascript" src="../internal.js"></script>
    <script type="text/javascript" src="ZeroClipboard.js"></script>
    <style type="text/css" media="screen">
        .bigButtonBox {
            font-size: larger;
            font-family: georgia, serif;
            overflow:hidden;
        }
        .btn { display: block; position: relative; background: #aaa; padding: 5px; float: left; color: #fff; text-decoration: none; cursor: pointer; }
        .btn * { font-style: normal; background-image: url(btn2.png); background-repeat: no-repeat; display: block; position: relative; }
        .btn i { background-position: top left; position: absolute; margin-bottom: -5px;  top: 0; left: 0; width: 5px; height: 5px; }
        .btn span { background-position: bottom left; left: -5px; padding: 0 0 5px 10px; margin-bottom: -5px; }
        .btn span i { background-position: bottom right; margin-bottom: 0; position: absolute; left: 100%; width: 10px; height: 100%; top: 0; }
        .btn span span { background-position: top right; position: absolute; right: -10px; margin-left: 10px; top: -5px; height: 0; }
        .btn.blue { background: #2ae; }
        .btn.green { background: #9d4; }
        .btn.pink { background: #e1a; }
        .btn:hover { background-color: #a00; }
        .btn:active { background-color: #444; }
        .btn[class] {  background-image: url(shade.png); background-position: bottom; }

        * html .btn { border: 3px double #aaa; }
        * html .btn.blue { border-color: #2ae; }
        * html .btn.green { border-color: #9d4; }
        * html .btn.pink { border-color: #e1a; }
        * html .btn:hover { border-color: #a00; }

        p { clear: both; padding-bottom: 2em; }
        form { margin-top: 2em; }
        form p .btn { margin-right: 1em; }
        textarea { margin: 1em 0;}

        img{max-width:100px;max-height:100px;}
        img{
            width: expression(this.width > 100 && this.width > this.height ? 100 : auto);
            height: expression(this.height > 100 ? 100 : auto);
            padding: 2px;
            border: lightblue solid 1px;
            margin:2px;
        }
    </style>

</head>
<body>
    <div id="nothing_to_upload">
        Sorry, nothing to upload!
    </div>
    <div id="upload_box" class="wrapper">
        <div style="padding:5px; ">
            <div class='bigButtonBox'>
                <a onclick="uploadImages()" class="btn blue big">
                    <i></i><span ><i></i><span></span>Upload Images</span>
                </a>
            </div>
        </div>
        <div id="upload_steps", style="padding:5px;">
            <h1>Please Select files to upload</h1>
            <div>
                <div id="upload_step1"> Step1: Please click "Copy" button to copy word temp folder into clipboard
                    <div id="word_temp_folder">
                    </div>
                    <div>
                        <button id="clip-button" data-clipboard-text="Copy me sam!" title="Click to copy to clipboard.">Copy to Clipboard</button>
                    </div>
                </div>
                <div id="upload_step2"> Step2: Please click "Open" button to open file selection dialog
                    <input id="fileImage" type="file" size="200" name="fileselect[]" multiple />
                </div>
                <div id="upload_step3"> Step3: Please select all files under word temp folder and click 'OK' to confirm selection</div>
            </div>
        </div>
        <div id='images'style="padding:5px;">
            <div></div>
        </div>
    </div>
    <script type="text/javascript">
        var imgsInDoc = UE.dom.domUtils.getElementsByTagName(editor.document.body, 'img');
        var clip = new ZeroClipboard( document.getElementById("clip-button"));

        var filesHandler = {
            fileInput:document.getElementById("fileImage"),
            dragDrop: document.getElementById("fileDragArea"),
            upButton: document.getElementById("fileSubmit"),
            url: '',
            fileFilter: [],					//过滤后的文件数组
            filter: function(files) {
                var arrFiles = [];
                folder = document.getElementById('word_temp_folder').innerText;
                folder = "file:///" + folder + "\\";
                for (var i = 0, file; file = files[i]; i++) {
                    console.log(file);
                    fileName = folder + file.name;
                    for(var j= 0, ci; ci=imgsInDoc[j]; j++){
                        var url = ci.getAttribute("word_img");
                        if( url && url == fileName){
                            ci.setAttribute("loaded", "yes");
                            file.pathname = url;
                            if (file.type.indexOf("image") == 0 || (!file.type && /\.(?:jpg|png|gif)$/.test(file.name) /* for IE10 */)) {
                                arrFiles.push(file);
                                addImageLocal(file, url);
                            } else {
                                addBinLocal(file, url);
                            }
                            break;
                        }
                    }
                }

                folder = getWordTempFolder();
                if( folder == null ){
                    document.getElementById('upload_steps').style.display = 'none';
                }
                else{
                    uploadStep1(folder);
                }
                return arrFiles;
            },
            onProgress: function(file, loaded, total) {
                //var eleProgress = $("#uploadProgress_" + file.index), percent = (loaded / total * 100).toFixed(2) + '%';

                percent = (loaded / total * 100).toFixed(2) + '%';
                console.log(percent);
                //eleProgress.show().html(percent);
            },
            onSuccess: function(file, response) {
                var res = JSON.parse(response)
                onWordImageUploadSucc(res.file, res.url);
            },
            onFailure: function(file) {
                console.log('onFail');
                console.log(file);
            },
            onComplete: function() {
                console.log("onComplete");
            },

            //获取选择文件，file控件或拖放
            funGetFiles: function(e) {
                // 获取文件列表对象
                var files = e.target.files || e.dataTransfer.files;
                //继续添加文件
                this.fileFilter = this.fileFilter.concat(this.filter(files));
                return this;
            },

            //文件上传
            funUploadFile: function() {
                var self = this;
                if (location.host.indexOf("sitepointstatic") >= 0) {
                    //非站点服务器上运行
                    return;
                }
                for (var i = 0, file; file = this.fileFilter[i]; i++) {
                    (function(file) {
                        var xhr = new XMLHttpRequest();
                        if (xhr.upload) {
                            // 上传中
                            xhr.upload.addEventListener("progress", function(e) {
                                self.onProgress(file, e.loaded, e.total);
                            }, false);

                            // 文件上传成功或是失败
                            xhr.onreadystatechange = function(e) {
                                if (xhr.readyState == 4) {
                                    if (xhr.status == 200) {
                                        self.onSuccess(file, xhr.responseText);
                                        //self.funDeleteFile(file);
                                        if (!self.fileFilter.length) {
                                            //全部完毕
                                            self.onComplete();
                                        }
                                    } else {
                                        self.onFailure(file, xhr.responseText);
                                    }
                                }
                            };

                            // 开始上传
                            xhr.open("POST", self.url+"&file="+encodeURIComponent(file.pathname), true);
                            //xhr.setRequestHeader("X_FILENAME", file.name);
                            var fd = new FormData();
                            fd.append('upfile', file);
                            xhr.send(fd);
                        }
                    })(file);
                }

            },

            init: function() {
                var self = this;
                var url = window.location.toString();
                var pieces = url.split('wp-content');
                url = pieces[0] + 'wp-admin/admin-ajax.php?action=ueditor_post&method=onekey';
                this.url = url;

                if (this.dragDrop) {
                    this.dragDrop.addEventListener("dragover", function(e) { self.funDragHover(e); }, false);
                    this.dragDrop.addEventListener("dragleave", function(e) { self.funDragHover(e); }, false);
                    this.dragDrop.addEventListener("drop", function(e) { self.funGetFiles(e); }, false);
                }

                //文件选择控件选择
                if (this.fileInput) {
                    this.fileInput.addEventListener("change", function(e) { self.funGetFiles(e); }, false);
                    this.fileInput.addEventListener("click", function(e) { uploadStep3(); }, false);

                }
            }
        };
        filesHandler.init();


        function uploadImages(){
            console.log('uploadImages');
            filesHandler.funUploadFile();
        }
        function addImage(imgUrl, imgFileName, imgOrigin){
            var img = document.createElement('img')
            img.src = imgUrl;
            img.setAttribute("file_src", imgFileName);
            img.setAttribute("src_origin", imgOrigin);
            //img.style = "width:50px;height:100%;";
            var container = document.getElementById('images')
            container.appendChild(img);
        }

        function addImageLocal(file, word_image){
            var img = document.createElement('img')
            img.setAttribute("word_image", word_image);
            var reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = function(e){
                img.src = e.target.result;
            }
            //img.style = "width:50px;height:100%;";
            var container = document.getElementById('images')
            container.appendChild(img);
        }

        function addBinLocal(file, word_image){
            var img = document.createElement('img')
            img.setAttribute("word_image", word_image);
            img.src = '..\\..\\themes\\default\\images\\new_file.png';
            //img.style = "width:50px;height:100%;";
            var container = document.getElementById('images')
            container.appendChild(img);
        }


        function getWordTempFolder(){
            for(var i= 0, ci; ci=imgsInDoc[i]; i++){
                var url = ci.getAttribute("word_img");
                if( url && !ci.hasAttribute('loaded')){
                    var index = url.lastIndexOf("\\");
                    var fileName = url.substr(8, index-8);
                        return fileName;
                }
            }
            return null;
        }

        function onExit(){
            for(var i= 0, ci; ci=imgsInDoc[i]; i++){
                var url = ci.getAttribute("word_img");
                ci.removeAttribute("loaded");
            }
        }

        function onWordImageUploadSucc(word_image, url){
            for(var i= 0, ci; ci=imgsInDoc[i]; i++){
                var filename = ci.getAttribute("word_img");
                if( filename == word_image){
                        ci.src = url;
                        ci.removeAttribute("word_img");
                        ci.removeAttribute("_src");
                        ci.removeAttribute("style");
                        ci.removeAttribute("alt");
                        break;
                }
            }

            //img.style = "width:50px;height:100%;";
            var container = document.getElementById('images')
            for( var img=container.firstElementChild; img!=null; img = img.nextElementSibling ){
                if( img.getAttribute('word_image') == word_image ){
                    container.removeChild(img);
                    break;
                }
            }
        }

        function uploadStep1(folder){
            var clipButton = document.getElementById('clip-button');
            clipButton.style.display = "block";
            clipButton.setAttribute("data-clipboard-text", folder);
            document.getElementById('word_temp_folder').innerText = folder;
            clip.setText(folder);
            var div = document.getElementById('upload_step1');
            div.style.color = "darkblue";


            div = document.getElementById('upload_step2');
            div.style.color = "lightgray";
            document.getElementById('fileImage').style.display = "none";

            div = document.getElementById('upload_step3');
            div.style.color = "lightgray";
        }

        function uploadStep2(){

            var div = document.getElementById('upload_step1');
            div.style.color = "lightgray";
            document.getElementById('clip-button').style.display = "none";

            div = document.getElementById('upload_step2');
            div.style.color = "darkblue";
            document.getElementById('fileImage').style.display = "block";

            div = document.getElementById('upload_step3');
            div.style.color = "lightgray";
        }

        function uploadStep3(){
            var div = document.getElementById('upload_step1');
            div.style.color = "lightgray";

            div = document.getElementById('upload_step2');
            div.style.color = "lightgray";
            document.getElementById('fileImage').style.display = "none";

            div = document.getElementById('upload_step3');
            div.style.color = "darkblue";
        }

        clip.on( 'complete', function ( client, args ) {
            uploadStep2();
        } );
        window.onload = function () {
            console.log(editor);

            console.log(dialog);
            console.log(imgsInDoc);

            var folder = getWordTempFolder();
            if( folder == null ){
                document.getElementById('nothing_to_upload').style.display = 'block';
                document.getElementById('upload_box').style.display = 'none';
            }
            else{
                document.getElementById('nothing_to_upload').style.display = 'none';
                document.getElementById('upload_box').style.display = 'block';
                uploadStep1(folder);
            }

            //点击OK按钮
            dialog.onok = function () {
                console.log('onok');
                onExit();
            };
            dialog.oncancel = function () {
                onExit();
            };

        };

    </script>
</body>
</html>