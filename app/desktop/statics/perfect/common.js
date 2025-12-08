/**
 * Shopex OMS
 * 
 * Copyright (c) 2025 Shopex (http://www.shopex.cn)
 * Licensed under Apache-2.0 with additional terms (See LICENSE file)
 */

(function () {

  document.body.addEventListener('click', onClickBody)

  var quickMenuBtn = document.querySelector('#quick-menu-btn')
  quickMenuBtn.addEventListener('click', onOpenQuickDrawer)

  function onClickBody() {
    var e = window.event
    var drawer = document.querySelector('#quick-drawer')
    if (
      !quickMenuBtn.contains(e.target)
      && quickMenuBtn !== e.target
      && drawer !== e.target
      && !drawer.contains(e.target)
      && drawer.style.right === '0px'
    ) {
      onOpenQuickDrawer()
    }
  }

  /**
   * 打开快捷操作侧边栏
   */
  function onOpenQuickDrawer() {
    var drawer = document.querySelector('#quick-drawer')
    drawer.style.right = drawer.style.right === '0px' ?  '-550px' : '0px'
  }
})()

