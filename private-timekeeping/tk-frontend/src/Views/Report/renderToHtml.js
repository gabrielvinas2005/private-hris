// Best-effort utilities to render Vue SFCs to HTML strings for PDF generation.
// Falls back to provided HTML when SSR renderer is unavailable in the client bundle.

import { createSSRApp, createApp, h } from 'vue'
import ReportsHeader from './ReportsHeader.vue'
import ReportsFooter from './ReportsFooter.vue'

async function renderComponentToString(component, props) {
	try {
		// Dynamically import server renderer to avoid bundling issues in some builds
		const { renderToString } = await import('@vue/server-renderer')
		const app = createSSRApp({
			render() {
				return h(component, props || {})
			}
		})
		return await renderToString(app)
	} catch (err) {
		console.warn('SSR render not available, falling back to static HTML:', err?.message || err)
		return ''
	}
}

export async function tryRenderHeaderFooterToString(headerProps, footerProps) {
	const [header, footer] = await Promise.all([
		renderComponentToString(ReportsHeader, headerProps || {}),
		renderComponentToString(ReportsFooter, footerProps || {})
	])
	return { headerHtml: header, footerHtml: footer }
}

// Client-side immediate render to HTML strings (no SSR dependency)
export function renderHeaderFooterClient(headerProps = {}, footerProps = {}) {
  const mountAndGetHtml = (component, props) => {
    const container = document.createElement('div')
    const app = createApp({ render: () => h(component, props) })
    app.mount(container)
    const html = container.innerHTML || ''
    app.unmount()
    return html
  }

  const headerHtml = mountAndGetHtml(ReportsHeader, headerProps)
  const footerHtml = mountAndGetHtml(ReportsFooter, footerProps)
  return { headerHtml, footerHtml }
}


