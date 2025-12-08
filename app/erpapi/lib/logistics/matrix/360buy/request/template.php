<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_logistics_matrix_360buy_request_template extends erpapi_logistics_request_template {

    /**
     * syncStandardTpl
     * @return mixed 返回值
     */
    public function syncStandardTpl() {
        return $this->succ();
    }

    /**
     * syncUserTpl
     * @return mixed 返回值
     */
    public function syncUserTpl() {
        
        $this->title = '获取京东模板';
        $rs = $this->requestCall(STORE_WAYBILL_STANDARD_TEMPLATE, array());
        
        if($rs['rsp'] == 'succ' && $rs['data']) {
            $data = json_decode($rs['data'], true);
          
            $rs['data'] = array();
            
            $sDatas    = $data['jingdong_printing_template_getTemplateList_responce']['returnType']['datas']['sDatas']?:$data['sDatas'];       //所有物流公司标准模板列表
            $uDatas    = $data['jingdong_printing_template_getTemplateList_responce']['returnType']['datas']['uDatas']?:$data['uDatas'];       //用户使用的模板
            $diyDatas  = $data['jingdong_printing_template_getTemplateList_responce']['returnType']['datas']['diyDatas']?:$data['diyDatas'];   //ISV自定义模板以及自定义项内容
            $udiyDatas = $data['jingdong_printing_template_getTemplateList_responce']['returnType']['datas']['udiyDatas']?:$data['udiyDatas']; //商家自定义区内容

            $cpCodes = array();
            if($uDatas) {
                foreach ($uDatas as $uVal) {
                    $cpCodes[] = $uVal['cpCode'];
                }
            }

            if($sDatas) {
                foreach ($sDatas as $val) {
                 
                    $cpCode          = $val['cpCode'];
                    foreach ($val['standardTemplates'] as $sVal) {
                        $outTemplateId = $sVal['standardTemplateId'];
                        $rs['data'][] = array(
                            'tpl_index'       => 'standard-'. $outTemplateId,
                            'cp_code'         => $val['cpCode'],
                            'out_template_id' => $outTemplateId,
                            'template_name'   => $sVal['standardTemplateName'].'(京东)',
                            'template_type'   => 'jd_standard',
                            'template_data'   => 'url:' . $sVal['standardTemplateUrl']
                        );
                        
                        //获取自定义
                        if(!in_array($cpCode, $cpCodes)){
                             continue;
                        }
                
                        $sdf = array(
                           'way_template_type' => $sVal['standardWaybillType'],
                           'cp_code'           => $cpCode,
                        );
                        $udtRs = $this->requestCall(STORE_WAYBILL_STANDARD_TEMPLATE, $sdf);

                        if($udtRs['rsp'] == 'succ' && $udtRs['data']) {
                            $udt_data = json_decode($udtRs['data'], true);
                            $customResult = $udt_data['uDatas'];
                            foreach ($customResult as $crs) {
                                if($crs['userStdTemplates']) {

                                    $uDataCpCode = $crs['cpCode'];
                                    if($uDataCpCode != $cpCode){
                                       continue;
                                    }

                                    foreach ($crs['userStdTemplates'] as $cr) {
                                        //获取自定义打印项
                                        $printItems = $this->getPrintItems($cr['userStdTemplateUrl']);
                                        $rs['data'][] = array(
                                            'tpl_index'       => 'user-' . $outTemplateId . '-' . $cr['userStdTemplateId'],
                                            'cp_code'         => $crs['cpCode'],
                                            'out_template_id' => $outTemplateId . '-' . $cr['userStdTemplateId'],
                                            'template_name'   => $cr['userStdTemplateName'] . '#' . $sVal['standardTemplateName'] .'(京东)',
                                            'template_type'   => 'jd_user',
                                            'template_data'   => 'url:' . $sVal['standardTemplateUrl'],
                                            'template_select' => array('user_url' => $cr['userStdTemplateUrl'],'print_items'=>$printItems),     
                                        );
                                    }
                                }
                            }
                        }
                        //获取自定义

                    }
                }
            }

            if($diyDatas) {
                foreach ($diyDatas as $dVal) {
                    $firstTemplateId = $dVal[0]['resourceId'];
                    $templateType    = $dVal['resourceId'] == 1 ? 'standard' : 'user';
                    $outTemplateId   = $dVal['resourceId'] ? : (int) $firstTemplateId;
                    $rs['data'][] = array(
                        'tpl_index'       => $templateType . '-' . $outTemplateId,
                        'cp_code'         => $dVal['resourceType'],
                        'out_template_id' => $outTemplateId,
                        'template_name'   => $dVal['resourceName'].'(京东)',
                        'template_type'   => 'jd_user',
                        'template_data'   => 'url:' . $dVal['resourceUrl']
                    );  
                }
            }
            
            //用户自定义
            if($udiyDatas) {
                foreach ($udiyDatas as $udiyVal) {
                    $printItems      = array();
                    if(!empty($udiyVal['customAreaKeys'])){
                       foreach ($udiyVal['customAreaKeys'] as $key => $value) {
                            if(preg_match('/@{(\S+)}/', $value['key'],$m)){
                               $printItems[] = trim($m[1]);
                            }
                       }
                    }
                    if(!$udiyVal['standardTemplateId']){
                         $outTemplateId   = $udiyVal['customAreaId'];
                         $rs['data'][] = array(
                            'tpl_index'       => 'user-'.$outTemplateId,
                            'cp_code'         => $udiyVal['cpCode'],
                            'out_template_id' => $outTemplateId,
                            'template_name'   => $udiyVal['customAreaName'].'(京东)',
                            'template_type'   => 'jd_user',
                            'template_data'   => 'url:' . $udiyVal['customAreaUrl'],
                        );  
                    }else{
                         $outTemplateId   = $udiyVal['standardTemplateId'].'_'.$udiyVal['customAreaId'];
                         $rs['data'][] = array(
                            'tpl_index'       => 'user-'.$outTemplateId,
                            'cp_code'         => $udiyVal['cpCode'],
                            'out_template_id' => $outTemplateId,
                            'template_name'   => $udiyVal['customAreaName'].'(京东)',
                            'template_type'   => 'jd_user',
                            'template_data'   => 'url:' . $udiyVal['standardTemplateUrl'],
                            'template_select' => array('user_url' => $udiyVal['customAreaUrl'],'print_items'=>array_unique($printItems)),     
                        );  
                    }
                    
                }
            }


        } else {
            $rs['data'] = array();
        }
      
        return $rs;
    }

    //获取自定义打印项
    /**
     * 获取PrintItems
     * @param mixed $userStdTemplateUrl userStdTemplateUrl
     * @return mixed 返回结果
     */
    public function getPrintItems($userStdTemplateUrl){
       $items       = array();
       $userStdData = json_decode(base64_decode(file_get_contents($userStdTemplateUrl)),1);
       if($userStdData['items']){
           foreach ($userStdData['items'] as $key => $value) {
                $content =  $value['content'];
                if(preg_match('/@{(\S+)}/', $content,$m)){
                   $items[] = trim($m[1]);
                }
            }
       }
       return array_unique($items);
    }

}