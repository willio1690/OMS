<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_saas_format_data {
    
    private $site;
    
    private $hostId;
    private $serviceId;
    private $tenantId;
    private $orderId;
    private $resourceId;
    private $certiId;
    private $nodeId;
    private $hostName;
    private $dbServer;
    private $dbHost;
    private $dbPort;
    private $dbName;
    private $dbUser;
    private $dbPasswd;
    private $status;
    private $sourceType;
    private $cycleStart;
    private $cycleEnd;
    private $createTime;
    private $activeTime;
    private $loginTime;
    private $lastTime;
    private $token;
    private $limitShop = 0;
    
    /**
     * 设置LimitShop
     * @param mixed $number number
     * @return mixed 返回操作结果
     */
    public function setLimitShop($number) {
        $this->limitShop = $number;
    }
    
    /**
     * 获取LimitShop
     * @return mixed 返回结果
     */
    public function getLimitShop() {
        return $this->limitShop;
    }
    
    /**
     * 设置Site
     * @param mixed $site site
     * @return mixed 返回操作结果
     */
    public function setSite(&$site){
        $this->site = $site;
    }
    
    /**
     * 获取Site
     * @return mixed 返回结果
     */
    public function getSite(){
        return $this->site;
    }

    /**
     * 获取DbHost
     * @return mixed 返回结果
     */
    public function getDbHost() {
        return $this->dbHost;
    }

    /**
     * 设置DbHost
     * @param mixed $dbHost dbHost
     * @return mixed 返回操作结果
     */
    public function setDbHost($dbHost) {
        $this->dbHost = $dbHost;
    }

    /**
     * 获取HostId
     * @return mixed 返回结果
     */
    public function getHostId() {
        return $this->hostId;
    }

    /**
     * 获取ServiceId
     * @return mixed 返回结果
     */
    public function getServiceId() {
        return $this->serviceId;
    }

    /**
     * 获取TenantId
     * @return mixed 返回结果
     */
    public function getTenantId() {
        return $this->tenantId;
    }

    /**
     * 获取OrderId
     * @return mixed 返回结果
     */
    public function getOrderId() {
        return $this->orderId;
    }

    /**
     * 获取ResourceId
     * @return mixed 返回结果
     */
    public function getResourceId() {
        return $this->resourceId;
    }

    /**
     * 获取CertiId
     * @return mixed 返回结果
     */
    public function getCertiId() {
        return $this->certiId;
    }

    /**
     * 获取NodeId
     * @return mixed 返回结果
     */
    public function getNodeId() {
        return $this->nodeId;
    }

    /**
     * 获取HostName
     * @return mixed 返回结果
     */
    public function getHostName() {
        return $this->hostName;
    }

    /**
     * 获取DbName
     * @return mixed 返回结果
     */
    public function getDbName() {
        return $this->dbName;
    }

    /**
     * 获取DbServer
     * @return mixed 返回结果
     */
    public function getDbServer() {
        return $this->dbServer;
    }

    /**
     * 获取DbUser
     * @return mixed 返回结果
     */
    public function getDbUser() {
        return $this->dbUser;
    }

    /**
     * 获取DbPasswd
     * @return mixed 返回结果
     */
    public function getDbPasswd() {
        return $this->dbPasswd;
    }

    /**
     * 获取DbPort
     * @return mixed 返回结果
     */
    public function getDbPort() {
        return $this->dbPort;
    }

    /**
     * 获取Status
     * @return mixed 返回结果
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * 获取SourceType
     * @return mixed 返回结果
     */
    public function getSourceType() {
        return $this->sourceType;
    }

    /**
     * 获取CycleStart
     * @return mixed 返回结果
     */
    public function getCycleStart() {
        return $this->cycleStart;
    }

    /**
     * 获取CycleEnd
     * @return mixed 返回结果
     */
    public function getCycleEnd() {
        return $this->cycleEnd;
    }

    /**
     * 获取CreateTime
     * @param mixed $format format
     * @return mixed 返回结果
     */
    public function getCreateTime($format=null) {
        if(empty($format)) {
            return $this->createTime;
        } else {
            return date($format, $this->createTime);
        }
    }

    /**
     * 获取ActiveTime
     * @param mixed $format format
     * @return mixed 返回结果
     */
    public function getActiveTime($format=null) {
        if(empty($format)) {
            return $this->activeTime;
        } else {
            return date($format, $this->activeTime);
        }
    }

    /**
     * 获取LoginTime
     * @return mixed 返回结果
     */
    public function getLoginTime() {
        return $this->loginTime;
    }

    /**
     * 获取LastTime
     * @return mixed 返回结果
     */
    public function getLastTime() {
        return $this->lastTime;
    }

    /**
     * 设置HostId
     * @param mixed $hostId ID
     * @return mixed 返回操作结果
     */
    public function setHostId($hostId) {
        $this->hostId = $hostId;
    }

    /**
     * 设置ServiceId
     * @param mixed $serviceId ID
     * @return mixed 返回操作结果
     */
    public function setServiceId($serviceId) {
        $this->serviceId = $serviceId;
    }

    /**
     * 设置TenantId
     * @param mixed $tenantId ID
     * @return mixed 返回操作结果
     */
    public function setTenantId($tenantId) {
        $this->tenantId = $tenantId;
    }

    /**
     * 设置OrderId
     * @param mixed $orderId ID
     * @return mixed 返回操作结果
     */
    public function setOrderId($orderId) {
        $this->orderId = $orderId;
    }

    /**
     * 设置ResourceId
     * @param mixed $resourceId ID
     * @return mixed 返回操作结果
     */
    public function setResourceId($resourceId) {
        $this->resourceId = $resourceId;
    }

    /**
     * 设置CertiId
     * @param mixed $certiId ID
     * @return mixed 返回操作结果
     */
    public function setCertiId($certiId) {
        $this->certiId = $certiId;
    }

    /**
     * 设置NodeId
     * @param mixed $nodeId ID
     * @return mixed 返回操作结果
     */
    public function setNodeId($nodeId) {
        $this->nodeId = $nodeId;
    }

    /**
     * 设置HostName
     * @param mixed $hostName hostName
     * @return mixed 返回操作结果
     */
    public function setHostName($hostName) {
        $this->hostName = $hostName;
    }

    /**
     * 设置DbName
     * @param mixed $dbName dbName
     * @return mixed 返回操作结果
     */
    public function setDbName($dbName) {
        $this->dbName = $dbName;
    }

    /**
     * 设置DbServer
     * @param mixed $dbServer dbServer
     * @return mixed 返回操作结果
     */
    public function setDbServer($dbServer) {
        $this->dbServer = $dbServer;
    }

    /**
     * 设置DbUser
     * @param mixed $dbUser dbUser
     * @return mixed 返回操作结果
     */
    public function setDbUser($dbUser) {
        $this->dbUser = $dbUser;
    }
    
    /**
     * 设置DbPasswd
     * @param mixed $dbPassword dbPassword
     * @return mixed 返回操作结果
     */
    public function setDbPasswd($dbPassword) {
        $this->dbPasswd = $dbPassword;
    }

    /**
     * 设置DbPort
     * @param mixed $dbPort dbPort
     * @return mixed 返回操作结果
     */
    public function setDbPort($dbPort) {
        $this->dbPort = $dbPort;
    }

    /**
     * 设置Status
     * @param mixed $status status
     * @return mixed 返回操作结果
     */
    public function setStatus($status) {
        $this->status = $status;
    }

    /**
     * 设置SourceType
     * @param mixed $sourceType sourceType
     * @return mixed 返回操作结果
     */
    public function setSourceType($sourceType) {
        $this->sourceType = $sourceType;
    }

    /**
     * 设置CycleStart
     * @param mixed $cycleStart cycleStart
     * @return mixed 返回操作结果
     */
    public function setCycleStart($cycleStart) {
        $this->cycleStart = $cycleStart;
    }

    /**
     * 设置CycleEnd
     * @param mixed $cycleEnd cycleEnd
     * @return mixed 返回操作结果
     */
    public function setCycleEnd($cycleEnd) {
        $this->cycleEnd = $cycleEnd;
    }

    /**
     * 设置CreateTime
     * @param mixed $createTime createTime
     * @return mixed 返回操作结果
     */
    public function setCreateTime($createTime) {
        $this->createTime = $createTime;
    }

    /**
     * 设置ActiveTime
     * @param mixed $activeTime activeTime
     * @return mixed 返回操作结果
     */
    public function setActiveTime($activeTime) {
        $this->activeTime = $activeTime;
    }

    /**
     * 设置LoginTime
     * @param mixed $loginTime loginTime
     * @return mixed 返回操作结果
     */
    public function setLoginTime($loginTime) {
        $this->loginTime = $loginTime;
    }

    /**
     * 设置LastTime
     * @param mixed $lastTime lastTime
     * @return mixed 返回操作结果
     */
    public function setLastTime($lastTime) {
        $this->lastTime = $lastTime;
    }
    
    /**
     * 设置Token
     * @param mixed $token token
     * @return mixed 返回操作结果
     */
    public function setToken($token) {
        $this->token = $token;
    }
    
    /**
     * 获取Token
     * @return mixed 返回结果
     */
    public function getToken() {
        return $this->token;
    }
    
    /**
     * isActive
     * @return mixed 返回值
     */
    public function isActive() {
        return $this->status == 'HOST_STATUS_ACTIVE';
    }
    
}