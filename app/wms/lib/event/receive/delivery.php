<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class wms_event_receive_delivery extends wms_event_response{

    /**
     * 加载发货单据的处理Lib类
     * @var wms_receipt_delivery
     */
    private $__dlyReceiptLib = null;

    /**
     * 当前传入的参数
     */
    private $__inputParams = array();

    /**
     *
     * 初始化核心所需的加载类
     * @param void
     */
    private function _instanceObj(){
        $this->__dlyReceiptLib = kernel::single('wms_receipt_delivery');
    }

    /**
     *
     * 初始化发货单信息
     * @param array $params 传入参数
     * @param string $msg 错误信息
     */
    private function _initDlyInfo($params, &$msg){
        //接口传入参数
        $this->__inputParams = $params;

        return true;
    }

    /**
     * 发货单创建事件
     * @param array $data
     */
    public function create($data){

        //初始化类的对象
        $this->_instanceObj();

        //初始化当前处理发货单的数据
        if(!$this->_initDlyInfo($data, $msg)){
            return $this->send_error($msg, $msg_code, $data);
        }

        //发货单创建前的检查
        if(!$this->_checkWhenCreate($msg)){
            return $this->send_error($msg, $msg_code, $this->__inputParams);
        }

        //加入事务机制
        kernel::database()->beginTransaction();
        //创建发货单
        $res = $this->__dlyReceiptLib->create($this->__inputParams, $msg);
        if($res){
            //事务提交
            kernel::database()->commit();
            return $this->send_succ('发货单创建成功');
        }else{
            //事务回滚
            kernel::database()->rollBack();
            return $this->send_error($msg, $msg_code, $this->__inputParams);
        }
    }

    /**
     *
     * 执行创建发货单前，检查发货单相关信息
     * @param string $msg 错误信息
     */
    private function _checkWhenCreate(&$msg){
        //校验传入参数
        if(!$this->__dlyReceiptLib->checkCreateParams($this->__inputParams,$error_msg)){
            $msg = '发货单参数检验失败,具体原因:'.$error_msg;
            return false;
        }

        //检查发货通知单是否已经存在
        if($this->__dlyReceiptLib->checkOuterExist($this->__inputParams['outer_delivery_bn'])){
            $msg = '发货单外部单号'.$this->__inputParams['outer_delivery_bn']."已存在";
            return false;
        }

        return true;
    }

    /**
     * 发货单取消事件处理
     * @param array $data
     */
    public function cancel($data){

        //初始化类的对象
        $this->_instanceObj();

        //初始化当前处理发货单的数据
        if(!$this->_initDlyInfo($data, $msg)){
            return $this->send_error($msg, $msg_code, $data);
        }

        //发货单取消前的检查
        if(!$this->_checkWhenCancel($msg)){
            return $this->send_error($msg, $msg_code, $this->__inputParams);
        }

        //加入事务机制
        kernel::database()->beginTransaction();
        //执行发货单取消
        $res = $this->__dlyReceiptLib->cancelDlyByOuterDlyBn($this->__inputParams['outer_delivery_bn']);
        if($res){
            //事务提交
            kernel::database()->commit();
            return $this->send_succ('发货单取消成功'.$msg);
        }else{
            //事务回滚
            kernel::database()->rollBack();
            return $this->send_error($msg, $msg_code, $this->__inputParams);
        }
    }

    /**
     *
     * 执行取消发货单前，检查发货单相关信息
     * @param string $msg 错误信息
     */
    private function _checkWhenCancel(&$msg){

        if(!isset($this->__inputParams['outer_delivery_bn']) || empty($this->__inputParams['outer_delivery_bn'])){
            $msg = '发货单外部单号不存在，单号:'.$this->__inputParams['outer_delivery_bn'];
            return false;
        }

        //检查发货单是否存在
        if(!$this->__dlyReceiptLib->checkOuterExist($this->__inputParams['outer_delivery_bn'])){
            $msg = '发货单外部单号不存在，单号:'.$this->__inputParams['outer_delivery_bn'];
            return true;
        }

        //检查发货单当前状态是否有效，可操作
        if(!$this->__dlyReceiptLib->checkDlyStatusByOuterDlyBn($this->__inputParams['outer_delivery_bn'],wms_receipt_delivery::__CANCEL,$msg)){
            return false;
        }

        return true;
    }

    /**
     * 发货单暂停事件处理
     * @param array $data
     */
    public function pause($data){

        //初始化类的对象
        $this->_instanceObj();

        //初始化当前处理发货单的数据
        if(!$this->_initDlyInfo($data, $msg)){
            return $this->send_error($msg, $msg_code, $data);
        }

        //发货单暂停前的检查
        if(!$this->_checkWhenPause($msg)){
            return $this->send_error($msg, $msg_code, $this->__inputParams);
        }

        //加入事务机制
        kernel::database()->beginTransaction();
        //执行发货单暂停
        $res = $this->__dlyReceiptLib->pauseDlyByOuterDlyBn($data['outer_delivery_bn']);
        if($res){
            //事务提交
            kernel::database()->commit();
            return $this->send_succ('发货单暂停成功');
        }else{
            //事务回滚
            kernel::database()->rollBack();
            return $this->send_error($msg, $msg_code, $this->__inputParams);
        }
    }

    /**
     *
     * 执行暂停发货单前，检查发货单相关信息
     * @param string $msg 错误信息
     */
    private function _checkWhenPause(&$msg){

        if(!isset($this->__inputParams['outer_delivery_bn']) || empty($this->__inputParams['outer_delivery_bn'])){
            $msg = '发货单外部单号不存在，单号:'.$this->__inputParams['outer_delivery_bn'];
            return false;
        }

        //检查发货单是否存在
        if(!$this->__dlyReceiptLib->checkOuterExist($this->__inputParams['outer_delivery_bn'])){
            $msg = '发货单外部单号不存在，单号:'.$this->__inputParams['outer_delivery_bn'];
            return false;
        }

        //检查发货单当前状态是否有效，可操作
        if(!$this->__dlyReceiptLib->checkDlyStatusByOuterDlyBn($this->__inputParams['outer_delivery_bn'],wms_receipt_delivery::__PAUSE,$msg)){
            return false;
        }

        return true;
    }

    /**
     * 发货单恢复事件处理
     * @param array $data
     */
    public function renew($data){

        //初始化类的对象
        $this->_instanceObj();

        //初始化当前处理发货单的数据
        if(!$this->_initDlyInfo($data, $msg)){
            return $this->send_error($msg, $msg_code, $data);
        }

        //发货单暂停前的检查
        if(!$this->_checkWhenRenew($msg)){
            return $this->send_error($msg, $msg_code, $this->__inputParams);
        }

        //加入事务机制
        kernel::database()->beginTransaction();
        //执行发货单暂停
        $res = $this->__dlyReceiptLib->renewDlyByOuterDlyBn($data['outer_delivery_bn']);
        if($res){
            //事务提交
            kernel::database()->commit();
            return $this->send_succ('发货单恢复成功');
        }else{
            //事务回滚
            kernel::database()->rollBack();
            return $this->send_error($msg, $msg_code, $this->__inputParams);
        }
    }

    /**
     *
     * 执行恢复发货单前，检查发货单相关信息
     * @param string $msg 错误信息
     */
    private function _checkWhenRenew(&$msg){

        if(!isset($this->__inputParams['outer_delivery_bn']) || empty($this->__inputParams['outer_delivery_bn'])){
            $msg = '发货单外部单号不存在，单号:'.$this->__inputParams['outer_delivery_bn'];
            return false;
        }

        //检查发货单是否存在
        if(!$this->__dlyReceiptLib->checkOuterExist($this->__inputParams['outer_delivery_bn'])){
            $msg = '发货单外部单号不存在，单号:'.$this->__inputParams['outer_delivery_bn'];
            return false;
        }

        //检查发货单当前状态是否有效，可操作
        if(!$this->__dlyReceiptLib->checkDlyStatusByOuterDlyBn($this->__inputParams['outer_delivery_bn'],wms_receipt_delivery::__RENEW,$msg)){
            return false;
        }

        return true;
    }
}

?>
