<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class image_clip{
    function image_resize(&$imgmdl,$src_file,$target_file,$new_width,$new_height){
        if(!isset($src_file) || !is_file($src_file)){
            return false;
        }
        
        // 获取图片信息
        $imageInfo = getimagesize($src_file);
        if(!$imageInfo){
            return false;
        }
        
        list($width, $height, $type) = $imageInfo;
        $size = self::get_image_size($new_width,$new_height,$width,$height);
        $new_width = $size[0];
        $new_height = $size[1]; 
        
        // 检查是否需要处理（尺寸相同直接复制）
        if($width == $new_width && $height == $new_height){
            return copy($src_file, $target_file);
        }
        
        // 优先使用ImageMagick（性能更好）
        if(function_exists('magickresizeimage')){
            return $this->resizeWithImageMagick($src_file, $target_file, $new_width, $new_height);
        }
        
        // 使用GD库处理
        if(function_exists('imagecopyresampled')){
            return $this->resizeWithGD($src_file, $target_file, $new_width, $new_height, $width, $height, $type);
        }
        
        return false;
    }
    
    /**
     * 使用ImageMagick进行图片缩放
     */
    private function resizeWithImageMagick($src_file, $target_file, $new_width, $new_height){
        $rs = NewMagickWand();
        if(!MagickReadImage($rs, $src_file)){
            return false;
        }
        
        MagickResizeImage($rs, $new_width, $new_height, MW_QuadraticFilter, 0.3);
        MagickSetImageFormat($rs, 'image/jpeg');
        $result = MagickWriteImage($rs, $target_file);
        
        return $result;
    }
    
    /**
     * 使用GD库进行图片缩放（带内存管理）
     */
    private function resizeWithGD($src_file, $target_file, $new_width, $new_height, $width, $height, $type){
        // 检查内存使用量
        $requiredMemory = ($width * $height * 4) + ($new_width * $new_height * 4) + (1024 * 1024); // 额外1MB缓冲
        $memoryLimit = $this->getMemoryLimit();
        
        if($requiredMemory > $memoryLimit * 0.8){
            // 内存不足，使用分步处理
            return $this->resizeWithGDStepByStep($src_file, $target_file, $new_width, $new_height, $width, $height, $type);
        }
        
        $image_p = null;
        $image = null;
        
        try {
            $quality = 80;
            $image_p = imagecreatetruecolor($new_width, $new_height);
            if(!$image_p){
                return false;
            }
            
            imagealphablending($image_p, false);
            
            // 根据类型创建源图片
            switch($type){
                case IMAGETYPE_JPEG:
                    $image = imagecreatefromjpeg($src_file);
                    $func = 'imagejpeg';
                    break;
                case IMAGETYPE_GIF:
                    $image = imagecreatefromgif($src_file);
                    $func = 'imagegif';
                    break;
                case IMAGETYPE_PNG:
                    $image = imagecreatefrompng($src_file);
                    imagesavealpha($image, true);
                    $func = 'imagepng';
                    $quality = 8;
                    break;
                default:
                    return false;
            }
            
            if(!$image){
                return false;
            }
            
            imagesavealpha($image_p, true);
            imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            
            if($func){
                $func($image_p, $target_file, $quality);
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        } finally {
            // 确保资源释放
            if($image_p) imagedestroy($image_p);
            if($image) imagedestroy($image);
        }
    }
    
    /**
     * 分步处理大图片（避免内存不足）
     */
    private function resizeWithGDStepByStep($src_file, $target_file, $new_width, $new_height, $width, $height, $type){
        // 计算需要的中间步骤数量
        $ratio = max($width / $new_width, $height / $new_height);
        $steps = max(2, min(5, ceil($ratio / 2))); // 2-5步之间
        
        $currentWidth = $width;
        $currentHeight = $height;
        $tempFiles = array();
        
        for($i = 0; $i < $steps; $i++){
            if($i == $steps - 1){
                // 最后一步
                $stepWidth = $new_width;
                $stepHeight = $new_height;
                $stepFile = $target_file;
            } else {
                // 中间步骤，每次缩小到50-70%
                $shrinkRatio = 0.5 + (0.2 * ($i / ($steps - 1))); // 从0.5到0.7
                $stepWidth = intval($currentWidth * $shrinkRatio);
                $stepHeight = intval($currentHeight * $shrinkRatio);
                $stepFile = $target_file . '_step_' . $i . '.tmp';
                $tempFiles[] = $stepFile;
            }
            
            $sourceFile = ($i == 0) ? $src_file : $tempFiles[$i - 1];
            
            // 强制垃圾回收，释放内存
            if($i > 0){
                gc_collect_cycles();
            }
            
            if(!$this->resizeWithGDDirect($sourceFile, $stepFile, $stepWidth, $stepHeight, $currentWidth, $currentHeight, $type)){
                // 清理临时文件
                foreach($tempFiles as $tempFile){
                    @unlink($tempFile);
                }
                return false;
            }
            
            $currentWidth = $stepWidth;
            $currentHeight = $stepHeight;
        }
        
        // 清理临时文件
        foreach($tempFiles as $tempFile){
            @unlink($tempFile);
        }
        
        return true;
    }
    
    /**
     * 直接使用GD库进行图片缩放（不检查内存，用于分步处理）
     */
    private function resizeWithGDDirect($src_file, $target_file, $new_width, $new_height, $width, $height, $type){
        $image_p = null;
        $image = null;
        
        try {
            $quality = 80;
            $image_p = imagecreatetruecolor($new_width, $new_height);
            if(!$image_p){
                return false;
            }
            
            imagealphablending($image_p, false);
            
            // 根据类型创建源图片
            switch($type){
                case IMAGETYPE_JPEG:
                    $image = imagecreatefromjpeg($src_file);
                    $func = 'imagejpeg';
                    break;
                case IMAGETYPE_GIF:
                    $image = imagecreatefromgif($src_file);
                    $func = 'imagegif';
                    break;
                case IMAGETYPE_PNG:
                    $image = imagecreatefrompng($src_file);
                    imagesavealpha($image, true);
                    $func = 'imagepng';
                    $quality = 8;
                    break;
                default:
                    return false;
            }
            
            if(!$image){
                return false;
            }
            
            imagesavealpha($image_p, true);
            imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            
            if($func){
                $func($image_p, $target_file, $quality);
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        } finally {
            // 确保资源释放
            if($image_p) imagedestroy($image_p);
            if($image) imagedestroy($image);
        }
    }
    
    /**
     * 获取当前内存限制
     */
    private function getMemoryLimit(){
        $memoryLimit = ini_get('memory_limit');
        if(preg_match('/(\d+)(.)/', $memoryLimit, $matches)){
            $value = intval($matches[1]);
            $unit = strtoupper($matches[2]);
            
            switch($unit){
                case 'G': return $value * 1024 * 1024 * 1024;
                case 'M': return $value * 1024 * 1024;
                case 'K': return $value * 1024;
                default: return $value;
            }
        }
        return 128 * 1024 * 1024; // 默认128MB
    }
    function get_image_size($new_width,$new_height,$org_width,$org_height){
        $dest_width = $new_width;
        $dest_height = $new_height;
        if($org_width>$org_height){
            if($org_width>=$new_width){
                $dest_width = $new_width;
                $dest_height = round(($org_height/$org_width)*$new_width);
            }
        }else{
            if($org_height>=$new_height){
                $dest_height = $new_height;
                $dest_width = round(($org_width/$org_height)*$new_height);
            }
        }
        return array($dest_width,$dest_height);
    }
    function image_watermark(&$imgmdl,$file,$set){
        switch($set['wm_type']){
        case 'text':
            $mark_image = $set['wm_text_image'];
            break;
        case 'image':
            $mark_image = $set['wm_image'];
            break;
        default:
            return;
        }
        if($set['wm_text_preview']){
            $mark_image = $set['wm_text_image'];
        }else{
            $mark_image = $imgmdl->fetch($mark_image);
        }
        list($watermark_width,$watermark_height,$type) = getimagesize($mark_image);
        list($src_width,$src_height) = getimagesize($file);
        list($dest_x, $dest_y ) = self::get_watermark_dest($src_width,$src_height,$watermark_width,$watermark_height,$set['wm_loc']);

        if(function_exists('NewMagickWand')){
            $sourceWand = NewMagickWand();
            $compositeWand = NewMagickWand();
            MagickReadImage($compositeWand, $mark_image);
            MagickReadImage($sourceWand, $file);
            MagickSetImageIndex($compositeWand, 0);
            MagickSetImageType($compositeWand, MW_TrueColorMatteType);
            MagickEvaluateImage($compositeWand, MW_SubtractEvaluateOperator, ($set['wm_opacity']?$set['wm_opacity']:50)/100, MW_OpacityChannel) ;
            MagickCompositeImage($sourceWand, $compositeWand, MW_ScreenCompositeOp, $dest_x, $dest_y);
            MagickWriteImage($sourceWand, $file);
        }elseif(method_exists(image_clip,'imagecreatefrom')){
            $sourceimage = self::imagecreatefrom($file);
            $watermark = self::imagecreatefrom($mark_image);
            imagecolortransparent($watermark, imagecolorat($watermark,0,0));
            imagealphablending($watermark,1);
			$set['wm_opacity'] = intval($set['wm_opacity']);

			imagecopymerge($sourceimage, $watermark, $dest_x, $dest_y, 0,
				0, $watermark_width, $watermark_height, $set['wm_opacity']);				
           
            imagejpeg($sourceimage,$file);
            imagedestroy($sourceimage);
            imagedestroy($watermark);
        }
        @unlink($mark_image);
    }

    static function imagecreatefrom($file){
        list($w,$h,$type) = getimagesize($file);

        switch($type){
        case IMAGETYPE_JPEG:
            return imagecreatefromjpeg($file);
        case IMAGETYPE_GIF:
            return imagecreatefromgif($file);
        case IMAGETYPE_PNG:
            return imagecreatefrompng($file);
        }
    }

    static function get_watermark_dest($src_w,$src_h,$wm_w,$wm_h,$loc){
        switch($loc[0]){
        case 't':
            $dest_y = ($src_h - 5 >$wm_h)?5:0;
            break;
        case 'm':
            $dest_y = floor(($src_h - $wm_h)/2);
            break;
        default:
            $dest_y = ($src_h - 5 >$wm_h)?($src_h - $wm_h - 5):0;
        }

        switch($loc[1]){
        case 'l':
            $dest_x = ($src_w - 5 >$wm_w)?5:0;
            break;
        case 'c':
            $dest_x = floor(($src_w - $wm_w)/2);
            break;
        default:
            $dest_x = ($src_w - 5 >$wm_w)?($src_w - $wm_w - 5):0;
        }

        return array($dest_x,$dest_y);
    }
}
