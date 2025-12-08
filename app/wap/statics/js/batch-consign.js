/**
 * Shopex OMS
 * 
 * Copyright (c) 2025 Shopex (http://www.shopex.cn)
 * Licensed under Apache-2.0 with additional terms (See LICENSE file)
 */

/**
 * 批量发货管理器
 * 基于批量打印功能实现，用于批量发货操作
 * 
 * @author AI Assistant
 * @date 2025-07-08
 */

class BatchConsignManager {
  constructor() {
    this.selectedOrders = new Set();
    this.isActive = false;
    this.apiUrl = '';
  }

  /**
   * 初始化批量发货功能
   * @param {string} apiUrl - 发货API地址
   */
  init(apiUrl) {
    this.apiUrl = apiUrl;
    this.bindEvents();
  }

  /**
   * 绑定事件
   */
  bindEvents() {
    // 注意：按钮事件由主页面的onclick属性处理，这里不重复绑定
  }

  /**
   * 切换批量发货模式
   */
  toggle() {
    const batchConsignBtn = document.getElementById('batchConsignBtn');
    
    if (this.isActive) {
      // 取消批量发货模式
      this.deactivate();
      batchConsignBtn.classList.remove('active');
    } else {
      // 激活批量发货模式
      this.activate();
      batchConsignBtn.classList.add('active');
      
      // 清除其他按钮的active状态
      const otherBtns = document.querySelectorAll('.date-select-item:not(#batchConsignBtn)');
      otherBtns.forEach(btn => btn.classList.remove('active'));
    }
  }

  /**
   * 激活批量发货模式
   */
  activate() {
    this.isActive = true;
    this.applyBatchConsignSelection();
    this.updateUI();

    // 如果没有可选订单，返回false表示激活失败
    return this.selectedOrders.size > 0;
  }

  /**
   * 激活批量发货模式（保持已选择状态但验证条件）
   */
  activateWithoutAutoSelect() {
    this.isActive = true;
    // 不清空已有选择，保持当前状态
    // this.selectedOrders.clear(); // 注释掉这行
    this.enableBatchConsignCheckboxes();
    this.updateUI();

    console.log('BatchConsignManager: 激活批量发货模式，保持已有选择并验证条件');
  }

  /**
   * 启用批量发货模式的复选框（保持已选择状态但验证条件）
   */
  enableBatchConsignCheckboxes() {
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    let validSelectedCount = 0;
    let invalidSelectedCount = 0;

    orderCheckboxes.forEach(checkbox => {
      const listItem = checkbox.closest('.list-item');
      const deliveryId = checkbox.value;
      const wasSelected = checkbox.checked;

      // 检查是否满足批量发货条件
      if (this.canSelectForBatchConsign(listItem)) {
        // 符合条件的订单
        checkbox.disabled = false;

        if (wasSelected) {
          // 保持已选择状态
          checkbox.checked = true;
          this.selectedOrders.add(deliveryId);
          validSelectedCount++;
        } else {
          // 未选择的订单保持未选择状态
          checkbox.checked = false;
        }
      } else {
        // 不符合条件的订单
        checkbox.disabled = true;

        if (wasSelected) {
          // 如果之前选中但现在不符合条件，取消选择
          checkbox.checked = false;
          this.selectedOrders.delete(deliveryId);
          invalidSelectedCount++;
        } else {
          checkbox.checked = false;
        }
      }
    });

    // 提示用户选择状态
    if (invalidSelectedCount > 0) {
      this.showToast(`已取消${invalidSelectedCount}个不符合条件的订单选择，请重新选择需要发货的订单`);
    } else if (validSelectedCount > 0) {
      this.showToast(`保持${validSelectedCount}个订单的选择状态，可继续选择其他订单`);
    } else {
      this.showToast('请选择需要发货的订单');
    }

    console.log('BatchConsignManager: 保持有效选择', validSelectedCount, '个，取消无效选择', invalidSelectedCount, '个');
  }

  /**
   * 取消批量发货模式
   */
  deactivate() {
    this.isActive = false;
    this.selectedOrders.clear();
    this.resetOrderSelection();
    this.hideActionButton();

    // 注意：不自动移除按钮的active状态，由调用方决定是否移除
    // 这样可以支持保持tab选中状态的需求
  }

  /**
   * 应用批量发货的选择逻辑
   */
  applyBatchConsignSelection() {
    this.selectedOrders.clear();
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');

    orderCheckboxes.forEach(checkbox => {
      const listItem = checkbox.closest('.list-item');

      if (this.canSelectForBatchConsign(listItem)) {
        checkbox.checked = true;
        checkbox.disabled = false;
        this.selectedOrders.add(checkbox.value);
      } else {
        checkbox.checked = false;
        checkbox.disabled = true;
      }
    });

    console.log('BatchConsignManager: 应用批量发货选择逻辑，选中', this.selectedOrders.size, '个订单');
  }

  /**
   * 检查订单是否可以进行批量发货
   * @param {Element} listItem - 订单项DOM元素
   * @returns {boolean}
   */
  canSelectForBatchConsign(listItem) {
    const checkbox = listItem.querySelector('.order-checkbox');
    if (!checkbox) return false;

    // 获取订单状态数据
    const status = parseInt(checkbox.getAttribute('data-status'));
    const hasLogiNo = parseInt(checkbox.getAttribute('data-has-logi-no'));
    const print_status=parseInt(checkbox.getAttribute('data-print-status'))//
    // 批量发货条件：status == 0 && 有快递单号（已获取物流单号且未发货）
    return status === 0 && print_status === 1;
  }

  /**
   * 重置订单选择状态
   */
  resetOrderSelection() {
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    orderCheckboxes.forEach(checkbox => {
      checkbox.disabled = false;
      checkbox.checked = false;
    });
  }

  /**
   * 更新UI状态
   */
  updateUI() {
    this.updateSelectedCount();
    if (this.selectedOrders.size > 0) {
      this.showActionButton();
    } else {
      this.hideActionButton();
    }

    console.log('BatchConsignManager: 更新UI状态，选中订单数:', this.selectedOrders.size);
  }

  /**
   * 更新选中数量显示
   */
  updateSelectedCount() {
    // 调用主页面的updateSelectedCount函数，确保状态同步
    if (window.updateSelectedCount) {
      window.updateSelectedCount();
    } else {
      // 降级处理：直接更新DOM
      const countElement = document.getElementById('selectedCount');
      if (countElement) {
        countElement.textContent = this.selectedOrders.size;
      }
    }
  }

  /**
   * 显示操作按钮
   */
  showActionButton() {
    const footer = document.getElementById('batchActionFooter');
    const consignBtn = document.getElementById('batchConsignNextBtn');
    const deliveryBtn = document.getElementById('batchDeliveryBtn');
    const printBtn = document.getElementById('batchPrintNextBtn');

    if (footer && consignBtn) {
      consignBtn.style.display = 'block';
      if (deliveryBtn) deliveryBtn.style.display = 'none';
      if (printBtn) printBtn.style.display = 'none';
      footer.style.display = 'block';
    }
  }

  /**
   * 隐藏操作按钮
   */
  hideActionButton() {
    // 只隐藏下一步按钮，不退出批量模式
    const footer = document.getElementById('batchActionFooter');
    if (footer) {
      footer.style.display = 'none';
    }
  }

  /**
   * 处理订单选择变化
   * @param {Element} checkbox - 复选框元素
   */
  handleOrderSelect(checkbox) {
    if (!this.isActive) return;

    const listItem = checkbox.closest('.list-item');
    if (!this.canSelectForBatchConsign(listItem)) {
      checkbox.checked = false;
      this.showToast('该订单不满足批量发货条件');
      return;
    }

    const deliveryId = checkbox.value;
    if (checkbox.checked) {
      this.selectedOrders.add(deliveryId);
    } else {
      this.selectedOrders.delete(deliveryId);
    }

    this.updateUI();

    // 同步主页面的选中数量显示
    if (window.updateSelectedCount) {
      window.updateSelectedCount();
    }
  }

  /**
   * 执行批量发货
   */
  goToBatchConsign() {
    if (this.selectedOrders.size === 0) {
      this.showToast('请选择需要发货的订单');
      return;
    }

    const confirmMessage = `即将为 ${this.selectedOrders.size} 个订单执行发货操作。\n\n发货后订单状态将变更为"已发货"，无法撤销。\n\n请确认是否继续？`;
    
    if (confirm(confirmMessage)) {
      this.startBatchConsign();
    }
  }

  /**
   * 开始批量发货
   */
  async startBatchConsign() {
    this.showProgress();
    
    const deliveryIds = Array.from(this.selectedOrders);
    const results = await this.processBatchConsign(deliveryIds);
    
    this.hideProgress();
    this.showResults(results);
  }

  /**
   * 处理批量发货
   * @param {Array} deliveryIds - 发货单ID数组
   * @returns {Object} 处理结果
   */
  async processBatchConsign(deliveryIds) {
    const totalCount = deliveryIds.length;
    let processedCount = 0;
    let successCount = 0;
    let failCount = 0;
    const errorMessages = [];

    for (let i = 0; i < deliveryIds.length; i++) {
      const deliveryId = deliveryIds[i];
      
      try {
        this.updateProgress(deliveryId, processedCount, totalCount, `正在发货订单 ${deliveryId}...`);

        const response = await this.callConsignAPI(deliveryId);
        
        if (response.rsp === 'succ' || response.res === 'succ') {
          successCount++;
        } else if (response.res === 'error') {
          failCount++;
          errorMessages.push(`订单 ${deliveryId}: ${response.msg || '发货失败'}`);
        } else {
          // 处理其他可能的响应格式
          failCount++;
          errorMessages.push(`订单 ${deliveryId}: 未知响应格式`);
        }

        processedCount++;
        await this.delay(500); // 延迟500ms避免请求过快

      } catch (error) {
        failCount++;
        errorMessages.push(`订单 ${deliveryId}: 网络错误或系统异常`);
        processedCount++;
      }
    }

    return {
      total: totalCount,
      success: successCount,
      fail: failCount,
      errors: errorMessages
    };
  }

  /**
   * 调用发货API
   * @param {string} deliveryId - 发货单ID
   * @returns {Promise} API响应
   */
  async callConsignAPI(deliveryId) {
    return new Promise((resolve, reject) => {
      $.post(this.apiUrl, {
        'delivery_id': deliveryId
      })
      .done(response => {
        try {
          const result = JSON.parse(response);
          resolve(result);
        } catch (e) {
          reject(new Error('响应解析失败'));
        }
      })
      .fail(() => {
        reject(new Error('网络请求失败'));
      });
    });
  }

  /**
   * 延迟函数
   * @param {number} ms - 延迟毫秒数
   * @returns {Promise}
   */
  delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  /**
   * 显示进度弹窗
   */
  showProgress() {
    // 进度弹窗的实现将在主文件中处理
    if (window.showBatchConsignProgress) {
      window.showBatchConsignProgress();
    }
  }

  /**
   * 隐藏进度弹窗
   */
  hideProgress() {
    if (window.hideBatchConsignProgress) {
      window.hideBatchConsignProgress();
    }
  }

  /**
   * 更新进度
   */
  updateProgress(currentOrder, processed, total, status) {
    if (window.updateConsignProgress) {
      window.updateConsignProgress(currentOrder, processed, total, status);
    }
  }

  /**
   * 显示结果
   */
  showResults(results) {
    if (window.showBatchConsignResult) {
      window.showBatchConsignResult(results);
    }
  }

  /**
   * 显示提示消息
   */
  showToast(message) {
    if (window.showToast) {
      window.showToast(message);
    }
  }
}

// 导出到全局
window.BatchConsignManager = BatchConsignManager;
