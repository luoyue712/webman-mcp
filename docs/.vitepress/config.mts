import { defineConfig } from 'vitepress'

export default defineConfig({
  title: "webman-mcp",
  description: "Webman Model Context Protocol (MCP) 插件文档",
  lang: 'zh-CN',
  base: '/webman-mcp/',
  themeConfig: {
    siteTitle: 'webman-mcp',
    search: {
      provider: 'local',
      options: {
        translations: {
          button: {
            buttonText: '搜索文档',
            buttonAriaLabel: '搜索文档'
          },
          modal: {
            noResultsText: '无法找到相关结果',
            resetButtonTitle: '清除查询条件',
            footer: {
              selectText: '选择',
              navigateText: '切换',
              closeText: '关闭'
            }
          }
        }
      }
    },
    nav: [
      { text: '指南', link: '/guide/getting-started' },
      { text: 'GitHub', link: 'https://github.com/luoyue712/webman-mcp' }
    ],
    sidebar: [
      {
        text: '开发指南',
        items: [
          { text: '快速开始', link: '/guide/getting-started' },
          { text: '配置详解', link: '/guide/configuration' },
          { text: '注解与开发', link: '/guide/annotations' },
          { text: '命令行工具', link: '/guide/commands' },
          { text: '内置开发工具', link: '/guide/dev-tools' },
          { text: '日志与调试', link: '/guide/logging-debugging' },
          { text: 'FAQ', link: '/guide/faq' }
        ]
      }
    ],
    socialLinks: [
      { icon: 'github', link: 'https://github.com/luoyue712/webman-mcp' }
    ],
    footer: {
      message: 'Released under the MIT and Apache-2.0 License.',
      copyright: 'Copyright © 2026-present luoyue712'
    }
  }
})
