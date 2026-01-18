<?php
/**
 * Mp4Embed 插件：用简洁的短代码在文章中插入 MP4 视频，默认自适应宽度、非自动播放，点击后开始播放。
 *
 * 用法：
 *   1) [mp4]https://example.com/video.mp4[/mp4]
 *   2) [mp4 src="https://example.com/video.mp4" poster="https://example.com/cover.jpg"]
 *
 * @package Mp4Embed
 * @author ChatGPT
 * @version 1.0.0
 * @link https://typecho.org/
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class Mp4Embed_Plugin implements Typecho_Plugin_Interface
{
    /** @var bool 防止 header 重复注入 */
    private static $headerInjected = false;

    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Mp4Embed_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('Mp4Embed_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Archive')->header = array('Mp4Embed_Plugin', 'header');
        return _t('Mp4Embed 插件已启用，可使用 [mp4] 短代码插入视频。');
    }

    public static function deactivate(){}

    public static function config(Typecho_Widget_Helper_Form $form){}
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 在页面 <head> 注入所需的样式与脚本
     */
    public static function header()
    {
        if (self::$headerInjected) return;
        self::$headerInjected = true;
        $options = Helper::options();
        // 插件静态资源 URL
        $base = $options->pluginUrl . '/Mp4Embed/assets';
        echo '<link rel="stylesheet" href="' . $base . '/style.css?v=1.0.0" />' . "\n";
        echo '<script>window.__MP4EMBED_BASE__ = ' . json_encode($base) . ';</script>' . "\n";
        echo '<script src="' . $base . '/embed.js?v=1.0.0" defer></script>' . "\n";
    }

    /**
     * 解析短代码，将 [mp4] 替换为 HTML 结构
     * 支持：
     *   [mp4]URL[/mp4]
     *   [mp4 src="URL" poster="POSTER"]
     */
    public static function parse($content, $widget, $lastResult)
    {
        $text = empty($lastResult) ? $content : $lastResult;

        // 1) [mp4]...[/mp4]
        $text = preg_replace_callback('/\[mp4\](.+?)\[\/mp4\]/is', function($m) {
            $src = trim($m[1]);
            return Mp4Embed_Plugin::buildHtml($src, null);
        }, $text);

        // 2) [mp4 src="..." poster="..."]
        $text = preg_replace_callback('/\[mp4\s+([^\]]+)\]/i', function($m) {
            $attr = $m[1];
            $src = null; $poster = null;
            if (preg_match('/src\s*=\s*"([^"]+)"/i', $attr, $mm)) {
                $src = trim($mm[1]);
            }
            if (preg_match('/poster\s*=\s*"([^"]+)"/i', $attr, $mm)) {
                $poster = trim($mm[1]);
            }
            return Mp4Embed_Plugin::buildHtml($src, $poster);
        }, $text);

        return $text;
    }

    /**
     * 构建最终 HTML。默认不自动播放，点击覆盖层后设置 src 并播放。
     */
    private static function buildHtml($src, $poster = null)
    {
        if (!$src || !filter_var($src, FILTER_VALIDATE_URL)) {
            // 非法 src，原样返回（不渲染）
            return '';
        }
        // 仅允许 mp4
        $low = strtolower(parse_url($src, PHP_URL_PATH));
        if ($low === null || !preg_match('/\.mp4(\?.*)?$/', $low)) {
            return '';
        }
        $posterAttr = $poster && filter_var($poster, FILTER_VALIDATE_URL) ? ' data-poster="' . htmlspecialchars($poster) . '"' : '';
        $srcAttr = ' data-src="' . htmlspecialchars($src) . '"';
        $html = <<<HTML
<div class="ty-mp4-embed"$srcAttr$posterAttr>
  <div class="ty-mp4-overlay" role="button" tabindex="0" aria-label="点击播放视频">▶ 点击播放</div>
  <video class="ty-mp4-video" preload="metadata" playsinline webkit-playsinline></video>
</div>
HTML;
        return $html;
    }
}
