# Mp4Embed (Typecho 插件)

一个极简的 Typecho 插件，用短代码插入 MP4 视频，**默认自适应页面宽度**、**不自动播放**，**点击后开始播放**。

## 安装

1. 解压本压缩包，将文件夹 **Mp4Embed** 上传到你站点的 `usr/plugins/` 目录：
   ```
   usr/plugins/Mp4Embed/
     ├─ Plugin.php
     └─ assets/
         ├─ embed.js
         └─ style.css
   ```
2. 后台 → 控制台 → 插件，启用 **Mp4Embed**。

## 用法

在文章或页面内容中加入以下短代码之一：

- 简单用法：
  ```
  [mp4]https://example.com/video.mp4[/mp4]
  ```

- 带封面（可选）：
  ```
  [mp4 src="https://example.com/video.mp4" poster="https://example.com/cover.jpg"]
  ```

- 多线路（使用 `|` 分隔）：
  ```
  [mp4]https://a.com/1.mp4|https://b.com/2.mp4[/mp4]
  ```

- 多线路 + 封面（可选）：
  ```
  [mp4 src="https://a.com/1.mp4|https://b.com/2.mp4" poster="https://example.com/cover.jpg"]
  ```

> 仅支持 `.mp4`，默认不会自动播放，点击覆盖层后开始播放；视频宽度自适应容器。  
> 多线路会显示“线路 1/2/...”，顺序与填写顺序一致。首次点击播放时按顺序自动检测可用线路，成功后不再自动检测，之后可手动切换线路。
