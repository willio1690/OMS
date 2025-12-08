<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * redis 访问对像
 */
class taskmgr_connecter_redis extends taskmgr_connecter_abstract implements taskmgr_connecter_interface{

    //连接配置信息
    protected $_redismq_config = null;
    //对列名
    protected $_redismq_queue_name = null; 
    //rabbitmq 连接
    protected $_redismq_connect = null;

    /**
     * __destruct
     * @return mixed 返回值
     */

    public function __destruct(){
        //销毁类对象的时候断开mq连接
        $this->disconnect();
    }

    /**
     * 初始化数据访问对像
     * 
     * @param string $task 任务标识
     * @return void
     */
    public function load($task, $config) {

        $queue_prefix = $config['QUEUE_PREFIX'] ? $config['QUEUE_PREFIX'] : 'ERP';
        $queueName =sprintf('%s_TASK_%s_QUEUE', $queue_prefix, strtoupper($task));

        return $this->connect(array('config' => $config, 'queueName' => $queueName));
    }

    /**
     * 连接 redis 服务器 
     *  
     * @param $cfg Array 连接参数 
     */
    public function connect($cfg) {

        //分解参数
        $config         = $cfg['config'];
        $queueName      = $cfg['queueName'];

        if (!$this->_validCfg($config)) {
            return false;
        }

        $this->disconnect();

        $this->_redismq_connect = new Redis();
        try {
            $conn_res = $this->_redismq_connect->pconnect($config['HOST'], $config['PORT']);

            if(!$conn_res){
                return false;
            }

            //如果配置了该参数就验证
            if(isset($config['PASSWD'])){
                $auth_res = $this->_redismq_connect->auth($config['PASSWD']);
                if(!$auth_res){
                    return false;
                }
            }

            //Specify a database
            if(isset($config['DB']) && $config['DB'] >= 0){
                $this->_redismq_connect->select($config['DB']);
            }

            $queueName = trim($queueName);
            if (empty($queueName)) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }

        $this->_redismq_config = $config;
        $this->_redismq_queue_name = $queueName;

        return true;
    }

    /**
     * 断开redis连接 
     *  
     * @param void 
     * @retrun void 
     */
    public function disconnect(){
        if(is_object($this->_redismq_connect)){
            $this->_redismq_connect->close();
        }
    }

    /**
     * 向对列提交信息 
     *  
     * @param $message String 信息内容体 
     * @param $router String  
     * @return boolean 
     */
    public function publish($message, $router) {

        if ($this->_redismq_queue_name) {
            return $this->_redismq_connect->lPush($this->_redismq_queue_name, $message);
        } else {
            return false;
        }
    }

    /**
     * 使用 block 模式 
     *  
     * @param void 
     * @return void 
     */
    public function consume($function) {
        $msg = $this;
        do{
            // 检查REDIS是否活着
            if (!$this->_redismq_connect->ping()) {
                break;
            }

            //检查队列长度，没有任务直接休眠1s，再次执行
            $queueLenth = $this->length();
            if($queueLenth <= 0){
                sleep($this->_redismq_config['WAIT_TIME']);
            }else{
                $callback_res = call_user_func($function, $msg);
                if(!$callback_res){
                    break;
                }
                //每次执行完一个任务后，间隔休息一下，劳逸结合
                usleep(10);
            }
        } while (true);
    }

    /**
     * 获取Body
     * @return mixed 返回结果
     */
    public function getBody(){
        return $this->_redismq_connect->rPop($this->_redismq_queue_name);
    }

    /**
     * 获取DeliveryTag
     * @return mixed 返回结果
     */
    public function getDeliveryTag(){
    
    }

    /**
     * 获取队列信息条数 
     *  
     * @param void 
     * @return integer 
     */
    public function length() {
        return $this->_redismq_connect->lLen($this->_redismq_queue_name);
    }

    public function ack($tagId){
    
    }

    public function nack($tagId){
    }

    /**
     * redis 连接参数检查 
     *  
     * @param $config Array 要检查的参数数组 
     * @return Boolean 
     */
    protected function _validCfg($config) {

        //先只做简单检查 ，后期可能需对参数做完整检查
        if (!is_array($config) || empty($config)) {

            return false;
        } else {

            return true;
        }
    }

}