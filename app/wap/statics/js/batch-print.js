/**
 * Shopex OMS
 * 
 * Copyright (c) 2025 Shopex (http://www.shopex.cn)
 * Licensed under Apache-2.0 with additional terms (See LICENSE file)
 */

/**
 * 批量打单功能模块
 * @author AI Assistant
 * @date 2025-01-08
 */

class BatchPrintManager {
  constructor() {
    this.selectedOrders = new Set();
    this.isActive = false;
    this.apiUrl = '';
  }

  /**
   * 初始化批量打单功能
   * @param {string} apiUrl - 打印API地址
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
   * 切换批量打单模式
   */
  toggle() {
    const batchPrintBtn = document.getElementById('batchPrintBtn');
    
    if (this.isActive) {
      // 取消批量打单模式
      this.deactivate();
      batchPrintBtn.classList.remove('active');
    } else {
      // 激活批量打单模式
      this.activate();
      batchPrintBtn.classList.add('active');
      
      // 清除其他按钮的active状态
      const otherBtns = document.querySelectorAll('.date-select-item:not(#batchPrintBtn)');
      otherBtns.forEach(btn => btn.classList.remove('active'));
    }
  }

  /**
   * 激活批量打单模式
   */
  activate() {
    this.isActive = true;
    this.applyBatchPrintSelection();
    this.updateUI();

    // 如果没有可选订单，返回false表示激活失败
    return this.selectedOrders.size > 0;
  }

  /**
   * 激活批量打单模式（保持已选择状态但验证条件）
   */
  activateWithoutAutoSelect() {
    this.isActive = true;
    // 不清空已有选择，保持当前状态
    // this.selectedOrders.clear(); // 注释掉这行
    this.enableBatchPrintCheckboxes();
    this.updateUI();

    console.log('BatchPrintManager: 激活批量打单模式33，保持已有选择并验证条件');
  }

  /**
   * 启用批量打单模式的复选框（保持已选择状态但验证条件）
   */
  enableBatchPrintCheckboxes() {
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    let validSelectedCount = 0;
    let invalidSelectedCount = 0;

    orderCheckboxes.forEach(checkbox => {
      const listItem = checkbox.closest('.list-item');
      const deliveryId = checkbox.value;
      const wasSelected = checkbox.checked;

      // 检查是否满足批量打单条件
      if (this.canSelectForBatchPrint(listItem)) {
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
      this.showToast(`已取消${invalidSelectedCount}个不符合条件的订单选择，请重新选择需要打印的订单`);
    } else if (validSelectedCount > 0) {
      this.showToast(`保持${validSelectedCount}个订单的选择状态，可继续选择其他订单`);
    } else {
      this.showToast('请选择需要打印的订单');
    }

    console.log('BatchPrintManager: 3333保持有效选择', validSelectedCount, '个，取消无效选择', invalidSelectedCount, '33个');
  }

  /**
   * 取消批量打单模式
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
   * 应用批量打单的选择逻辑
   */
  applyBatchPrintSelection() {
    this.selectedOrders.clear();
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');

    orderCheckboxes.forEach(checkbox => {
      const listItem = checkbox.closest('.list-item');

      if (this.canSelectForBatchPrint(listItem)) {
        checkbox.checked = true;
        checkbox.disabled = false;
        this.selectedOrders.add(checkbox.value);
      } else {
        checkbox.checked = false;
        checkbox.disabled = true;
      }
    });

    console.log('BatchPrintManager: 应用批量打单选择逻辑，选中', this.selectedOrders.size, '个订单');
  }

  /**
   * 检查订单是否可以进行批量打单
   * @param {Element} listItem - 订单项DOM元素
   * @returns {boolean}
   */
  canSelectForBatchPrint(listItem) {
    const checkbox = listItem.querySelector('.order-checkbox');
    if (!checkbox) return false;

    // 获取订单状态数据
    const status = parseInt(checkbox.getAttribute('data-status'));
    const hasLogiNo = parseInt(checkbox.getAttribute('data-has-logi-no'));
    const printStatus = parseInt(checkbox.getAttribute('data-print-status'));

    // 批量打单条件：status == 0 && 有快递单号 && print_status != 3
    return status === 0 && hasLogiNo === 0 && printStatus ==0;
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

    console.log('BatchPrintManager: 更新UI状态，选中订单数33:', this.selectedOrders.size);
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
    const printBtn = document.getElementById('batchPrintNextBtn');
    const deliveryBtn = document.getElementById('batchDeliveryBtn');
    
    if (footer && printBtn) {
      printBtn.style.display = 'block';
      if (deliveryBtn) deliveryBtn.style.display = 'none';
      footer.style.display = 'block';
    }
  }

  /**
   * 隐藏操作按钮
   */
  hideActionButton() {
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
    if (!this.canSelectForBatchPrint(listItem)) {
      checkbox.checked = false;
      this.showToast('该订单不满足批量打单条件');
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
   * 执行批量打单
   */
  goToBatchPrint() {
    if (this.selectedOrders.size === 0) {
      this.showToast('请选择需要打印的订单');
      return;
    }

    const confirmMessage = `已选择${this.selectedOrders.size}个订单，将会为这些订单批量打印电子面单，请确认是否要继续操作？`;
    
    if (confirm(confirmMessage)) {
      this.startBatchPrint();
    }
  }

  /**
   * 开始批量打印
   */
  async startBatchPrint() {
    this.showProgress();
    
    const deliveryIds = Array.from(this.selectedOrders);
    const results = await this.processBatchPrint(deliveryIds);
    
    this.hideProgress();
    this.showResults(results);
  }

  /**
   * 处理批量打印
   * @param {Array} deliveryIds - 发货单ID数组
   * @returns {Object} 处理结果
   */
  async processBatchPrint(deliveryIds) {
    const totalCount = deliveryIds.length;
    let processedCount = 0;
    let successCount = 0;
    let failCount = 0;
    const errorMessages = [];

    for (let i = 0; i < deliveryIds.length; i++) {
      const deliveryId = deliveryIds[i];
      
      try {
        this.updateProgress('批量打印中', processedCount, totalCount, `正在处理订单 ${deliveryId}...`);

        const response = await this.callPrintAPI(deliveryId);
        
        if (response.rsp === 'succ') {
          successCount++;
        } else {
          failCount++;
          errorMessages.push(`订单 ${deliveryId}: ${response.msg || '打印失败'}`);
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
   * 调用打印API
   * @param {string} deliveryId - 发货单ID
   * @returns {Promise} API响应
   */
  async callPrintAPI(deliveryId) {
    return new Promise((resolve, reject) => {
      $.post(this.apiUrl, {
        'delivery_id': deliveryId,
        'only_print': 'true'
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
    if (window.showBatchPrintProgress) {
      window.showBatchPrintProgress();
    }
  }

  /**
   * 隐藏进度弹窗
   */
  hideProgress() {
    if (window.hideBatchPrintProgress) {
      window.hideBatchPrintProgress();
    }
  }

  /**
   * 更新进度
   */
  updateProgress(currentOrder, processed, total, status) {
    if (window.updatePrintProgress) {
      window.updatePrintProgress(currentOrder, processed, total, status);
    }
  }

  /**
   * 显示结果
   */
  showResults(results) {
    if (window.showBatchPrintResult) {
      window.showBatchPrintResult(results);
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
window.BatchPrintManager = BatchPrintManager;
