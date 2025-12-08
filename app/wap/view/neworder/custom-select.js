/**
 * Shopex OMS
 * 
 * Copyright (c) 2025 Shopex (http://www.shopex.cn)
 * Licensed under Apache-2.0 with additional terms (See LICENSE file)
 */

/**
 * 自定义 Select 组件
 */
class CustomSelect {
  // 静态属性：存储所有实例
  static instances = [];
  static currentOpenInstance = null;

  constructor(element, options = {}) {
    this.element = element;
    this.options = options;
    this.isOpen = false;
    this.selectedValue = '';
    this.selectedText = '';
    
    // 将实例添加到全局管理中
    CustomSelect.instances.push(this);
    
    this.init();
  }

  init() {
    this.createCustomSelect();
    this.bindEvents();
    this.setInitialValue();
  }

  createCustomSelect() {
    // 隐藏原始select
    this.element.style.display = 'none';
    
    // 创建自定义select容器
    this.customSelect = document.createElement('div');
    this.customSelect.className = 'custom-select';
    
    // 创建触发器
    this.trigger = document.createElement('div');
    this.trigger.className = 'custom-select-trigger';
    
    this.valueElement = document.createElement('span');
    this.valueElement.className = 'custom-select-value';
    
    this.arrow = document.createElement('img');
    this.arrow.className = 'custom-select-arrow';
    this.arrow.src = './icon-arrow-down.png';
    
    this.trigger.appendChild(this.valueElement);
    this.trigger.appendChild(this.arrow);
    
    // 创建下拉列表
    this.dropdown = document.createElement('div');
    this.dropdown.className = 'custom-select-dropdown';
    this.dropdown.style.display = 'none';
    
    this.customSelect.appendChild(this.trigger);
    this.customSelect.appendChild(this.dropdown);
    
    // 插入到原始select后面
    this.element.parentNode.insertBefore(this.customSelect, this.element.nextSibling);
    
    this.updateOptions();
  }

  updateOptions() {
    this.dropdown.innerHTML = '';
    
    Array.from(this.element.options).forEach((option, index) => {
      const optionElement = document.createElement('div');
      optionElement.className = 'custom-select-option';
      optionElement.textContent = option.text;
      optionElement.dataset.value = option.value;
      optionElement.dataset.index = index;
      
      if (option.disabled) {
        optionElement.classList.add('disabled');
      }
      
      if (option.selected) {
        optionElement.classList.add('selected');
        this.selectedValue = option.value;
        this.selectedText = option.text;
      }
      
      this.dropdown.appendChild(optionElement);
    });
  }

  bindEvents() {
    // 点击触发器
    this.trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      this.toggle();
    });

    // 点击选项
    this.dropdown.addEventListener('click', (e) => {
      if (e.target.classList.contains('custom-select-option') && !e.target.classList.contains('disabled')) {
        this.selectOption(e.target);
      }
    });

    // 键盘事件
    this.customSelect.addEventListener('keydown', (e) => {
      this.handleKeydown(e);
    });
  }

  selectOption(optionElement) {
    const value = optionElement.dataset.value;
    const text = optionElement.textContent;
    const index = parseInt(optionElement.dataset.index);
    
    // 更新原始select
    this.element.selectedIndex = index;
    
    // 更新显示
    this.selectedValue = value;
    this.selectedText = text;
    this.valueElement.textContent = text;
    this.valueElement.classList.remove('placeholder');
    
    // 更新选中状态
    this.dropdown.querySelectorAll('.custom-select-option').forEach(opt => {
      opt.classList.remove('selected');
    });
    optionElement.classList.add('selected');
    
    // 触发change事件
    const event = new Event('change', { bubbles: true });
    this.element.dispatchEvent(event);
    
    this.close();
  }

  setInitialValue() {
    const selectedOption = this.element.options[this.element.selectedIndex];
    if (selectedOption) {
      this.selectedValue = selectedOption.value;
      this.selectedText = selectedOption.text;
      this.valueElement.textContent = selectedOption.text;
    } else {
      this.valueElement.textContent = this.options.placeholder || '请选择';
      this.valueElement.classList.add('placeholder');
    }
  }

  toggle() {
    if (this.isOpen) {
      this.close();
    } else {
      this.open();
    }
  }

  open() {
    // 关闭其他所有打开的select
    CustomSelect.closeAll();
    
    this.isOpen = true;
    CustomSelect.currentOpenInstance = this;
    this.dropdown.style.display = 'block';
    this.trigger.classList.add('active');
    this.arrow.classList.add('open');
    
    // 滚动到选中项
    const selectedOption = this.dropdown.querySelector('.custom-select-option.selected');
    if (selectedOption) {
      selectedOption.scrollIntoView({ block: 'nearest' });
    }
  }

  close() {
    this.isOpen = false;
    if (CustomSelect.currentOpenInstance === this) {
      CustomSelect.currentOpenInstance = null;
    }
    this.dropdown.style.display = 'none';
    this.trigger.classList.remove('active');
    this.arrow.classList.remove('open');
  }

  // 静态方法：关闭所有打开的select
  static closeAll() {
    CustomSelect.instances.forEach(instance => {
      if (instance.isOpen) {
        instance.close();
      }
    });
  }

  handleKeydown(e) {
    if (!this.isOpen) {
      if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
        e.preventDefault();
        this.open();
      }
      return;
    }

    const options = Array.from(this.dropdown.querySelectorAll('.custom-select-option:not(.disabled)'));
    const currentIndex = options.findIndex(opt => opt.classList.contains('selected'));

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        const nextIndex = currentIndex < options.length - 1 ? currentIndex + 1 : 0;
        this.selectOption(options[nextIndex]);
        break;
      case 'ArrowUp':
        e.preventDefault();
        const prevIndex = currentIndex > 0 ? currentIndex - 1 : options.length - 1;
        this.selectOption(options[prevIndex]);
        break;
      case 'Enter':
        e.preventDefault();
        if (currentIndex >= 0) {
          this.selectOption(options[currentIndex]);
        }
        break;
      case 'Escape':
        e.preventDefault();
        this.close();
        break;
    }
  }

  // 销毁组件
  destroy() {
    // 从全局实例列表中移除
    const index = CustomSelect.instances.indexOf(this);
    if (index > -1) {
      CustomSelect.instances.splice(index, 1);
    }
    
    if (CustomSelect.currentOpenInstance === this) {
      CustomSelect.currentOpenInstance = null;
    }
    
    if (this.customSelect && this.customSelect.parentNode) {
      this.customSelect.parentNode.removeChild(this.customSelect);
    }
    this.element.style.display = '';
  }

  // 设置值
  setValue(value) {
    const option = Array.from(this.element.options).find(opt => opt.value === value);
    if (option) {
      this.element.selectedIndex = option.index;
      this.selectedValue = option.value;
      this.selectedText = option.text;
      this.valueElement.textContent = option.text;
      this.valueElement.classList.remove('placeholder');
      
      // 更新选中状态
      this.dropdown.querySelectorAll('.custom-select-option').forEach(opt => {
        opt.classList.remove('selected');
      });
      this.dropdown.querySelector(`[data-value="${value}"]`).classList.add('selected');
    }
  }

  // 获取值
  getValue() {
    return this.selectedValue;
  }
}

// 初始化所有自定义select
function initCustomSelects() {
  const selects = document.querySelectorAll('select');
  selects.forEach(select => {
    if (!select.dataset.customized) {
      new CustomSelect(select);
      select.dataset.customized = 'true';
    }
  });
}

// 全局点击事件：关闭所有打开的select
document.addEventListener('click', (e) => {
  // 检查点击是否在任何custom-select内部
  const isInsideCustomSelect = e.target.closest('.custom-select');
  if (!isInsideCustomSelect) {
    CustomSelect.closeAll();
  }
});

// 导出供全局使用
window.CustomSelect = CustomSelect;
window.initCustomSelects = initCustomSelects; 