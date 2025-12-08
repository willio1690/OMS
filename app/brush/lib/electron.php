<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-15
 * @describe 特殊订单获取电子面单类
 */
class brush_electron{
    public $logiId;
    public $delivery;
    public $channel;
    public $corp;
    public $msg;

    /**
     * __construct
     * @param mixed $param param
     * @return mixed 返回值
     */

    public function __construct($param){
        if($param['logi_id']) {
            $this->logiId = $param['logi_id'];
        }
        if($param['channel']) {
            $this->channel = $param['channel'];
        }
        $this->delivery = $param['delivery'];
    }

    /**
     * isElectron
     * @return mixed 返回值
     */
    public function isElectron(){
        if(empty($this->logiId)) {
            return false;
        }
        $objCorp = app::get('ome')->model('dly_corp');
        $this->corp = $objCorp->dump($this->logiId);
        if($this->corp['tmpl_type'] == 'electron') {
            $channelObj = app::get("logisticsmanager")->model("channel");
            $cFilter = array(
                'channel_id' => $this->corp['channel_id'],
                'status'=>'true',
            );
            $this->channel = $channelObj->dump($cFilter);
            return true;
        }
        return false;
    }

    //获取电子面单，判断直连 或 缓存池
    /**
     * 获取WaybillNumber
     * @return mixed 返回结果
     */
    public function getWaybillNumber(){
        $objEle = $this->getEleChannel();
        if($objEle) {
            return $objEle->bufferGetWaybill();
        } else {
            return false;
        }
    }

    //从缓存池中获取电子面单
    /**
     * 获取BufferWaybill
     * @return mixed 返回结果
     */
    public function getBufferWaybill(){
        $objEle = $this->getEleChannel();
        if($objEle) {
            return $objEle->getBufferWaybill();
        } else {
            return false;
        }
    }

    //直连获取电子面单
    /**
     * 获取DirectWaybill
     * @param mixed $delivery_id ID
     * @return mixed 返回结果
     */
    public function getDirectWaybill($delivery_id){
        $objEle = $this->getEleChannel();
        if($objEle) {
            return $objEle->directGetWaybill($delivery_id);
        } else {
            return false;
        }
    }

    //获取大头笔
    /**
     * 获取WaybillExtend
     * @return mixed 返回结果
     */
    public function getWaybillExtend(){
        $objEle = $this->getEleChannel();
        if($objEle) {
            if(method_exists($objEle, 'getWaybillExtend')) {
                return $objEle->getWaybillExtend();
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function getEleChannel() {
        $channelType = $this->channel['channel_type'];
        try {
            if (class_exists('brush_electron_' . $channelType)) {
                $data = array();
                $data['channel'] = $this->channel;
                $data['delivery'] = $this->delivery;
                $objEle = kernel::single('brush_electron_' . $channelType);
                $objEle->init($data);
                return $objEle;
            }
        } catch (Exception $e) {
            try {
                if (class_exists('brush_electron_common')) {
                    $data = array();
                    $data['channel'] = $this->channel;
                    $data['delivery'] = $this->delivery;
                    $objEle = kernel::single('brush_electron_common');
                    $objEle->init($data);
                    return $objEle;
                }
            } catch (Exception $e) {
                $this->msg = '没有该类型的电子面单';
                return false;
            }
        }
    }
}