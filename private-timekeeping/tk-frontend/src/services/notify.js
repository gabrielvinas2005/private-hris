import { h } from 'vue'
import { ElNotification } from 'element-plus'
import { CircleCheckFilled, CircleCloseFilled, InfoFilled, WarningFilled } from '@element-plus/icons-vue'

const iconMap = {
  success: CircleCheckFilled,
  error: CircleCloseFilled,
  warning: WarningFilled,
  info: InfoFilled,
}

function renderIcon(type) {
  if (type === 'loading') {
    return h('span', { class: 'tk-toast__spinner' })
  }
  const Icon = iconMap[type] || InfoFilled
  return h(Icon, { class: 'tk-toast__icon' })
}

function showToast(type, message, options = {}) {
  if (!message) return null
  const { description, duration, replace, closable = true, onClose } = options
  if (replace?.close) replace.close()
  const resolvedDuration = duration ?? 3000
  const isLoading = type === 'loading'
  const notification = ElNotification({
    position: 'top-right',
    offset: 72,
    duration: resolvedDuration,
    showClose: closable,
    customClass: `tk-toast tk-toast--${type}`,
    onClose,
    message: h('div', { class: 'tk-toast__content' }, [
      h('div', { class: 'tk-toast__icon-wrap' }, [renderIcon(type)]),
      h('div', { class: 'tk-toast__body' }, [
        h('div', { class: 'tk-toast__title' }, message),
        description ? h('div', { class: 'tk-toast__desc' }, description) : null,
      ]),
      isLoading
        ? h(
            'div',
            { class: 'tk-toast__progress' },
            [
              h('div', {
                class: 'tk-toast__progress-bar',
                style: { animationDuration: `${resolvedDuration}ms` },
              }),
            ],
          )
        : null,
    ]),
  })
  return notification
}

export const notify = {
  success(message, options) {
    return showToast('success', message, options)
  },
  error(message, options) {
    return showToast('error', message, options)
  },
  warning(message, options) {
    return showToast('warning', message, options)
  },
  info(message, options) {
    return showToast('info', message, options)
  },
  loading(message, options) {
    return showToast('loading', message, { ...options, closable: false })
  },
  close(instance) {
    instance?.close?.()
  },
}

export default notify
