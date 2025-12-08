<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class  taoexlib_sms_event_abstract
{

    /**
     * 获取TmplConf
     * @return mixed 返回结果
     */
    public function getTmplConf(){
        return array(
            'title' =>$this->_tmplTitle,
            'content' =>$this->_tmplContent,
            'variables' =>$this->getTmplVariables(),
        );
    }
}