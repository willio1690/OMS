
(function(global){
  const id = '__CONTEXT_MENU_BOX__'
  const actions = {
    left: 'left',
    right: 'right',
    other: 'other',
    now: 'now'
  }
  const menu = document.getElementById(id)
  let currentIndex = null
  function onContextmenu(e, index) {
    e.preventDefault();
    currentIndex = index
    const contextLeft = document.querySelector('.context-menu-item-left')
    const contextRight = document.querySelector('.context-menu-item-right')
    const contextOther = document.querySelector('.context-menu-item-other')
    contextLeft.classList.remove('context-menu-item-disabled')
    contextRight.classList.remove('context-menu-item-disabled')
    contextOther.classList.remove('context-menu-item-disabled')
    switch(index) {
      case 0:
        contextLeft.classList.add('context-menu-item-disabled')
      case global.tabsController.tabs.length - 1:
        contextRight.classList.add('context-menu-item-disabled')
      default:
        if(global.tabsController.tabs.length === 1) {
          contextOther.classList.add('context-menu-item-disabled')
        }
    }
    menu.style.display = 'block';
    menu.style.left = e.clientX  + 15 + 'px';
    menu.style.top = e.clientY  + 15 + 'px';
  }
  function contextMenuClick(key) {
    switch(key) {
      case actions.left:
        global.tabsController.closeLeft(currentIndex)
        break;
      case actions.right:
        global.tabsController.closeRight(currentIndex)
        break;
      case actions.other:
        global.tabsController.closeOther(currentIndex)
        break;
      case actions.now:
        global.tabsController.close(tabsController.tabs[currentIndex].url)
        break;
    }
  }
  document.body.addEventListener('click', () => {
    document.body.removeEventListener('contextmenu', onContextmenu)
    menu.style.display = 'none';
  })
  global.contextMenuClick = contextMenuClick
  global.onContextmenu = onContextmenu
})(window)