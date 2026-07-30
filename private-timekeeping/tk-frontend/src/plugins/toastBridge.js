import { ElMessage } from 'element-plus'
import { notify } from '../services/notify'

const messageBridge = (type) => (message, options) => notify[type](message, options)

ElMessage.success = messageBridge('success')
ElMessage.error = messageBridge('error')
ElMessage.warning = messageBridge('warning')
ElMessage.info = messageBridge('info')


