<?php
    return array(
        'expires' => 86400, //缓存周期,默认3600*24秒即一天
        'memcache'=>array(
            'host'=>"localhost",
            'port'=>11211,
            'key_size'=>128,
            'expires'=>86400
        ),
        'upload_dir'=>BASE_PATH."/uploads",
        'upload_max_filesize'=>10485760,//最大可上传10MB的文件
        'upload_max_imgsize'=>2097152,//最大可上传2MB的图片文件,
        'upload_allowed_exts'=>array(
            'jpg','jpeg','bmp','gif','png','pdf',
            'txt','doc','csv','xls','ppt','docx',
            'xlsx','pptx','xml'
        ),//允许上传的文件类型
    );
?>