<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class image_mdl_image extends dbeav_model{
    var $defaultOrder = array('last_modified','desc');
    
    // 缓存已处理的图片信息
    private static $processedImages = array();
    function store($file,$image_id,$size=null,$name=null,$watermark=false){
        if(!defined('FILE_STORAGER'))define('FILE_STORAGER','filesystem');
        list($w,$h,$t) = getimagesize($file);
  
        $extname = array(
                1 => '.gif',
                2 => '.jpg',
                3 => '.png',
                6 => '.bmp',
            );
    
        if(!isset($extname[$t])){
            return false;
        }

        if($image_id){
            $params = $this->dump($image_id);
            if($name)
                $params['image_name'] = $name;
            $params['image_id'] = $image_id;
        }else{
            $params['image_id'] = $this->gen_id();
            $params['image_name'] = $name;
            $params['storage'] = FILE_STORAGER;
        }
        if(substr($file,0,4)=='http'){
            $params['storage'] = 'network';
            $params['url'] = $file;
            $params['ident'] = $file;
            $params['width'] = $w;
            $params['height'] = $h;
            $this->save($params);
            return $params['image_id'];
        }

        $params['watermark'] = $watermark;
        $storager = new base_storager();
        $params['last_modified'] = time();
        list($url,$ident,$no) = explode("|",$storager->save_upload($file,'image','',$msg,$extname[$t]));
        if($size){
            $size = strtolower($size);
            $params[$size.'_url'] = $url;
            $params[$size.'_ident'] = $ident;
        }else{
            $params['url'] = $url;
            $params['ident'] = $ident;
            $params['width'] = $w;
            $params['height'] = $h;
        }
        parent::save($params);
        return $params['image_id'];
    }


    function rebuild($image_id,$sizes,$watermark=true){
        $storager = new base_storager();

        if($sizes){

            $cur_image_set = $this->app->getConf('image.set');
            $allsize = $this->app->getConf('image.default.set');

            $this->watermark_define = array();
            $this->watermark_default = '';

            $tmp_target = tempnam(DATA_DIR, 'img');
            $img = $this->dump($image_id);
            if(is_array($img))  $org_file = $img['url'];

            if(substr($org_file,0,4)=='http'){

                if($img['storage']=='network'){
                    $response = kernel::single('base_http')->action('get',$org_file);
                    if($response===false){
                        $data = array('image_id'=>$image_id,'last_modified'=>time());
                        parent::save($data);
                        return true;                    
                    }
                    $image_content = $response;
                }else{
                    $image_file = $storager->worker->getFile($img['ident'],'image');
                    if(!$image_file) return false;
                    $image_content = file_get_contents($image_file);
                }
                $org_file = tempnam(DATA_DIR, 'imgorg');
                file_put_contents($org_file, $image_content);
           }

            if(!file_exists($org_file)){
                $data = array('image_id'=>$image_id,'last_modified'=>time());
               // parent::save($data);
                return true;
            }
           // 创建一次image_clip对象，避免重复创建
           $imageClip = new image_clip();
           
           foreach($sizes as $s){
                if(isset($allsize[$s])){
                    $w = $cur_image_set[$s]['width'];
                    $h = $cur_image_set[$s]['height'];
                    $wh = $allsize[$s]['height'];
                    $wd = $allsize[$s]['width'];
                    $w = $w?$w:$wd;
                    $h = $h?$h:$wh;
                    
                    // 使用同一个image_clip对象
                    $imageClip->image_resize($this,$org_file,$tmp_target,$w,$h);
                    
                    if($watermark&&$cur_image_set[$s]['wm_type']!='none'&&($cur_image_set[$s]['wm_text']||$cur_image_set[$s]['wm_image'])){
                        $watermark = true;
                        $imageClip->image_watermark($this,$tmp_target,$cur_image_set[$s]);
                    }
                    $this->store($tmp_target,$image_id,$s,null,$watermark);
					/** 删除指定规格图片 **/
					@unlink(ROOT_DIR.'/'.$img[strtolower($s).'_url']);
                }
            }
            @unlink($tmp_target);
            if(strpos('imgorg',$org_file)!==false)@unlink($org_file);
         }
    }

    function fetch($image_id,$size=null){
        $img = $this->dump($image_id);
        $k = $size?(strtolower($size).'_ident'):'ident';
        if($img['storage']=='network'){
            $org_file = $img['url'];
            $response = kernel::single('base_http')->action('get',$org_file);
            if($response===false){
                $data = array('image_id'=>$image_id,'last_modified'=>time());
                parent::save($data);
                return true;                    
            }
            $image_content = $response;
        }else{
            $storager = new base_storager();
            $image_file = $storager->worker->getFile($img[$k],'image');
            $image_content = file_get_contents($image_file);
        }
        $target_file = tempnam(DATA_DIR, 'targetfile');
        file_put_contents($target_file, $image_content);
        return $target_file;
    }

    /**
     * 关联图片到目标对象
     * @param string $image_id 图片ID
     * @param string $target_type 目标类型 (如: material, goods, user等)
     * @param int $target_id 目标对象ID
     * @return boolean 是否成功
     */
    function attach($image_id, $target_type, $target_id){
        if (!$image_id || !$target_type || !$target_id) {
            return false;
        }

        $imageAttachModel = app::get('image')->model('image_attach');
        
        // 检查是否已存在相同的关联
        $existing = $imageAttachModel->dump(array(
            'image_id' => $image_id,
            'target_type' => $target_type,
            'target_id' => $target_id
        ));
        
        if ($existing) {
            return true; // 已存在，直接返回成功
        }
        
        // 插入新的关联记录
        $attachData = array(
            'target_id' => $target_id,
            'target_type' => $target_type,
            'image_id' => $image_id,
            'last_modified' => time()
        );
        
        return $imageAttachModel->insert($attachData);
    }

    /**
     * 验证上传的文件（适用于$_FILES数组）
     * @param array $file $_FILES数组中的文件信息
     * @param array $options 验证选项
     * @return array|false 成功返回验证后的文件信息，失败返回false
     */
    function validateUploadedFile($file, $options = array()) {
        // 默认验证选项
        $defaultOptions = array(
            'max_size' => 5 * 1024 * 1024,  // 5MB
            'allowed_types' => array('image/jpeg', 'image/jpg', 'image/png', 'image/gif'),
            'max_width' => 4096,  // 最大宽度4K
            'max_height' => 4096, // 最大高度4K
            'check_dimensions' => true
        );
        
        $options = array_merge($defaultOptions, $options);

        
        // 检查文件上传错误
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = array(
                UPLOAD_ERR_INI_SIZE => '上传的文件超过了系统允许的大小',
                UPLOAD_ERR_FORM_SIZE => '上传的文件超过了表单允许的大小',
                UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
                UPLOAD_ERR_NO_FILE => '没有文件被上传',
                UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
                UPLOAD_ERR_CANT_WRITE => '文件写入失败',
                UPLOAD_ERR_EXTENSION => '文件上传被扩展程序阻止'
            );
            
            $errorMsg = isset($uploadErrors[$file['error']]) 
                ? $uploadErrors[$file['error']] 
                : '未知的上传错误';
            

            return array('error' => $errorMsg);
        }
        
        // 检查文件是否存在
        if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {

            return array('error' => '上传文件不存在');
        }
        
        // 检查文件大小
        if (isset($file['size']) && $file['size'] > $options['max_size']) {

            return array('error' => '文件太大，请上传小于' . ($options['max_size'] / 1024 / 1024) . 'MB的文件');
        }
        
        // 检查文件类型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $options['allowed_types'])) {

            return array('error' => '文件格式不支持，请上传 JPG、PNG 或 GIF 格式的图片');
        }
        
        // 检查是否为有效的图片文件
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {

            return array('error' => '无效的图片文件');
        }
        
        // 检查图片尺寸（可选）- 注释掉严格检查，允许压缩处理
        // if ($options['check_dimensions']) {
        //     if ($imageInfo[0] > $options['max_width'] || $imageInfo[1] > $options['max_height']) {
        //         return array('error' => '图片尺寸过大，请上传小于' . $options['max_width'] . 'x' . $options['max_height'] . '的图片');
        //     }
        // }
        
        // 验证通过，返回文件信息
        return array(
            'success' => true,
            'tmp_name' => $file['tmp_name'],
            'name' => $file['name'],
            'size' => $file['size'],
            'type' => $mimeType,
            'width' => $imageInfo[0],
            'height' => $imageInfo[1]
        );
    }

    /**
     * 上传并保存图片，同时建立关联关系（一站式服务）
     * @param string $file 图片文件路径或上传的临时文件
     * @param string $target_type 目标类型 (如: material, goods, user等)
     * @param int $target_id 目标对象ID
     * @param string $name 图片名称（可选）
     * @param array $sizes 需要生成的尺寸 (如: ['L','M','S'])
     * @param boolean $watermark 是否添加水印
     * @return array|false 成功返回图片信息，失败返回false
     */
    function uploadAndAttach($file, $target_type, $target_id, $name = null, $sizes = null, $watermark = false) {
        if (!$file || !$target_type || !$target_id) {
            return array('error' => '缺少必要参数');
        }

        ini_set('memory_limit', '256M');

        // =============== 图片验证和压缩逻辑 ===============
        // 先进行基本验证（不检查尺寸和文件大小限制）
        $fileInfo = array(
            'tmp_name' => $file,
            'name' => basename($file),
            'size' => filesize($file),
            'error' => UPLOAD_ERR_OK  // 手动设置上传成功状态
        );
        
        // 使用validateUploadedFile方法进行基本验证（不检查尺寸和大小限制）
        $validationResult = $this->validateUploadedFile($fileInfo, array(
            'max_size' => 50 * 1024 * 1024,  // 临时提高限制到50MB
            'max_width' => 10000,  // 临时提高限制到10000px
            'max_height' => 10000, // 临时提高限制到10000px
            'check_dimensions' => false  // 暂时不检查尺寸
        ));
        
        if (isset($validationResult['error'])) {
            return $validationResult;
        }

        // 检查是否需要压缩
        $compressedFile = $this->compressImageIfNeeded($file, $validationResult);
        if (isset($compressedFile['error'])) {
            return $compressedFile;
        }

        // 使用压缩后的文件（如果有的话）
        $finalFile = $compressedFile ? $compressedFile : $file;

        try {
            // 1. 保存图片到image表
            $image_id = $this->store($finalFile, null, null, $name, $watermark);
            
            if (!$image_id) {
                return array('error' => '图片保存失败');
            }

            // 2. 生成不同尺寸的图片（可选）
            if ($sizes && is_array($sizes) && !empty($sizes)) {
                $this->rebuild($image_id, $sizes, $watermark);
            }

            // 3. 建立关联关系
            $attachResult = $this->attach($image_id, $target_type, $target_id);
            
            if (!$attachResult) {
                // 如果关联失败，删除已保存的图片
                $this->delete_image($image_id, $target_type);
                return array('error' => '图片关联失败');
            }

            // 4. 清理临时压缩文件
            if ($compressedFile && $compressedFile !== $file) {
                @unlink($compressedFile);
            }

            // 5. 返回完整的图片信息
            $imageInfo = $this->dump($image_id);
            if ($imageInfo) {
                // 添加关联信息
                $imageInfo['target_type'] = $target_type;
                $imageInfo['target_id'] = $target_id;
                return $imageInfo;
            }

            return array('error' => '获取图片信息失败');
        } catch (Exception $e) {
            // 清理临时压缩文件
            if ($compressedFile && $compressedFile !== $file) {
                @unlink($compressedFile);
            }
            return array('error' => '图片上传异常：' . $e->getMessage());
        }
    }

    /**
     * 检查并压缩图片（如果需要）
     * @param string $file 原始图片文件路径
     * @param array $validationResult 验证结果
     * @return string|array 压缩后的文件路径，或错误信息
     */
    function compressImageIfNeeded($file, $validationResult) {
        // 默认压缩限制
        $maxWidth = 4096;
        $maxHeight = 4096;
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        $width = $validationResult['width'];
        $height = $validationResult['height'];
        $fileSize = filesize($file);
        
        // 计算目标尺寸
        $targetDimensions = $this->calculateTargetDimensions($width, $height, $fileSize, $maxWidth, $maxHeight, $maxSize);
        
        // 如果不需要压缩，直接返回原文件
        if (!$targetDimensions['needCompress']) {
            return $file;
        }
        
        // 使用优化的压缩方法
        return $this->compressImageWithRetry($file, $targetDimensions['width'], $targetDimensions['height'], $maxSize);
    }
    
    /**
     * 计算目标压缩尺寸
     */
    private function calculateTargetDimensions($width, $height, $fileSize, $maxWidth, $maxHeight, $maxSize) {
        $needCompress = false;
        $targetWidth = $width;
        $targetHeight = $height;
        
        // 检查尺寸是否超限
        if ($width > $maxWidth || $height > $maxHeight) {
            $needCompress = true;
            
            // 计算压缩后的尺寸，保持宽高比
            if ($width > $height) {
                // 宽度优先
                if ($width > $maxWidth) {
                    $targetWidth = $maxWidth;
                    $targetHeight = round(($height / $width) * $maxWidth);
                }
            } else {
                // 高度优先
                if ($height > $maxHeight) {
                    $targetHeight = $maxHeight;
                    $targetWidth = round(($width / $height) * $maxHeight);
                }
            }
        }
        
        // 检查文件大小是否超限
        if ($fileSize > $maxSize) {
            $needCompress = true;
            
            if (!$needCompress) {
                // 如果尺寸没超限但文件太大，按比例缩小
                $ratio = sqrt($maxSize / $fileSize);
                $targetWidth = round($width * $ratio);
                $targetHeight = round($height * $ratio);
            } else {
                // 如果尺寸和文件都超限，取更严格的限制
                $sizeRatio = sqrt($maxSize / $fileSize);
                $dimensionRatio = min($maxWidth / $width, $maxHeight / $height);
                $finalRatio = min($sizeRatio, $dimensionRatio);
                
                $targetWidth = round($width * $finalRatio);
                $targetHeight = round($height * $finalRatio);
            }
        }
        
        return array(
            'needCompress' => $needCompress,
            'width' => $targetWidth,
            'height' => $targetHeight
        );
    }
    
    /**
     * 带重试机制的图片压缩
     */
    private function compressImageWithRetry($file, $targetWidth, $targetHeight, $maxSize, $maxRetries = 3) {
        $imageClip = new image_clip();
        $compressedFile = null;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // 创建临时压缩文件
                $compressedFile = tempnam(DATA_DIR, 'img_compress_' . $attempt . '_');
                
                // 执行压缩
                $result = $imageClip->image_resize($this, $file, $compressedFile, $targetWidth, $targetHeight);
                
                if (!$result || !file_exists($compressedFile)) {
                    throw new Exception('图片压缩失败');
                }
                
                // 检查压缩后的文件大小
                $compressedSize = filesize($compressedFile);
                if ($compressedSize <= $maxSize) {
                    return $compressedFile;
                }
                
                // 如果仍然太大，计算新的压缩比例
                if ($attempt < $maxRetries) {
                    $ratio = sqrt($maxSize / $compressedSize);
                    $targetWidth = round($targetWidth * $ratio);
                    $targetHeight = round($targetHeight * $ratio);
                    
                    // 清理当前文件，准备下次尝试
                    @unlink($compressedFile);
                    $compressedFile = null;
                }
                
            } catch (Exception $e) {
                if ($compressedFile && file_exists($compressedFile)) {
                    @unlink($compressedFile);
                }
                
                if ($attempt === $maxRetries) {
                    return array('error' => '图片压缩异常：' . $e->getMessage());
                }
            }
        }
        
        return array('error' => '图片压缩失败，超过最大重试次数');
    }

    /**
     * 批量上传图片并建立关联
     * @param array $files 文件数组，每个元素包含文件路径和名称
     * @param string $target_type 目标类型
     * @param int $target_id 目标对象ID
     * @param array $sizes 需要生成的尺寸
     * @param boolean $watermark 是否添加水印
     * @return array 成功上传的图片信息数组
     */
    function batchUploadAndAttach($files, $target_type, $target_id, $sizes = null, $watermark = false) {
        $results = array();
        
        if (!is_array($files) || empty($files)) {
            return $results;
        }

        foreach ($files as $fileInfo) {
            $file = isset($fileInfo['file']) ? $fileInfo['file'] : $fileInfo;
            $name = isset($fileInfo['name']) ? $fileInfo['name'] : null;
            
            $result = $this->uploadAndAttach($file, $target_type, $target_id, $name, $sizes, $watermark);
            
            if ($result) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * 获取目标对象关联的所有图片
     * @param string $target_type 目标类型
     * @param int $target_id 目标对象ID
     * @param string $size 图片尺寸 (L/M/S)，为空则返回原图
     * @return array 图片信息数组
     */
    function getAttachedImages($target_type, $target_id, $size = null) {
        if (!$target_type || !$target_id) {
            return array();
        }

        $imageAttachModel = app::get('image')->model('image_attach');
        
        // 获取关联记录
        $attachList = $imageAttachModel->getList('*', array(
            'target_type' => $target_type,
            'target_id' => $target_id
        ), 0, -1, 'last_modified DESC');

        if (!$attachList) {
            return array();
        }

        $images = array();
        foreach ($attachList as $attach) {
            $imageInfo = $this->dump($attach['image_id']);
            if ($imageInfo) {
                // 根据需要的尺寸获取对应的URL
                $urlKey = $size ? strtolower($size) . '_url' : 'url';
                $identKey = $size ? strtolower($size) . '_ident' : 'ident';
                
                $image = array(
                    'image_id' => $imageInfo['image_id'],
                    'image_name' => $imageInfo['image_name'],
                    'url' => $imageInfo[$urlKey] ?: $imageInfo['url'],
                    'ident' => $imageInfo[$identKey] ?: $imageInfo['ident'],
                    'width' => $imageInfo['width'],
                    'height' => $imageInfo['height'],
                    'storage' => $imageInfo['storage'],
                    'attach_id' => $attach['attach_id'],
                    'last_modified' => $attach['last_modified']
                );
                
                // 获取完整路径
                if ($image['storage'] !== 'network') {
                    try {
                        $image['full_url'] = base_storager::image_path($image['image_id'], $size ?: '');
                    } catch (Exception $e) {
                        $image['full_url'] = $image['url'];
                    }
                } else {
                    $image['full_url'] = $image['url'];
                }

                $images[] = $image;
            }
        }

        return $images;
    }

    /**
     * 取消图片与目标对象的关联
     * @param string $image_id 图片ID
     * @param string $target_type 目标类型
     * @param int $target_id 目标对象ID
     * @param boolean $delete_image 是否同时删除图片文件
     * @return boolean 是否成功
     */
    function detach($image_id, $target_type, $target_id, $delete_image = false) {
        if (!$image_id || !$target_type || !$target_id) {
            return false;
        }

        $imageAttachModel = app::get('image')->model('image_attach');
        
        // 删除关联记录
        $result = $imageAttachModel->delete(array(
            'image_id' => $image_id,
            'target_type' => $target_type,
            'target_id' => $target_id
        ));

        // 如果需要删除图片文件
        if ($delete_image && $result) {
            $this->delete_image($image_id, $target_type);
        }

        return $result;
    }

    function gen_id(){
        return md5(rand(0,9999).microtime());
    }

    function all_storages(){
        return; 
    }

    function modifier_storage(&$list){
        $all_storages = $this->all_storages();
        $all_storages['network'] = app::get('image')->_('远程');
        $list = (array)$list;
        foreach($list as $k=>$v){
            $list[$k] = $all_storages[$k];
        }
    }
	
	/**
	 * 删除图片image_id
	 * @param string image_id
	 * @param string target_type
	 * @return boolean
	 */
	public function delete_image($image_id,$target_type)
	{
		if (!$image_id || !$target_type) return true;
		
		/** 商品图片资源被其他模块关联就不需要删除了 **/
		$filter = array(
			'image_id'=>$image_id,
			'target_type|ne'=>$target_type,
		);
		$obj_image_attachment = app::get('image')->model('image_attach');
		$tmp = $obj_image_attachment->getList('*',$filter);
		if ($tmp) return true;
		
		$tmp = $this->getList('*',array('image_id'=>$image_id,'storage'=>'filesystem'));
		if ($tmp){
			if (file_exists(ROOT_DIR.'/'.$tmp[0]['url']))
				@unlink(ROOT_DIR.'/'.$tmp[0]['url']);
			if (file_exists(ROOT_DIR.'/'.$tmp[0]['l_url']))
				@unlink(ROOT_DIR.'/'.$tmp[0]['l_url']);
			if (file_exists(ROOT_DIR.'/'.$tmp[0]['m_url']))
				@unlink(ROOT_DIR.'/'.$tmp[0]['m_url']);
			if (file_exists(ROOT_DIR.'/'.$tmp[0]['s_url']))
				@unlink(ROOT_DIR.'/'.$tmp[0]['s_url']);
		}
		return $this->delete(array('image_id'=>$image_id,'storage'=>'filesystem'));
	}
}
