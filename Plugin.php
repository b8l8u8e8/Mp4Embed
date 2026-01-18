<?php
/**
 * Mp4Embed 插件：用简洁的短代码在文章中插入 MP4 视频，默认自适应宽度、非自动播放，点击后开始播放。
 *
 * 用法：
 *   1) [mp4]https://example.com/video.mp4[/mp4]
 *   2) [mp4 src="https://example.com/video.mp4" poster="https://example.com/cover.jpg"]
 *   3) [mp4]https://a.com/1.mp4|https://b.com/2.mp4[/mp4]
 *   4) [mp4 src="https://a.com/1.mp4|https://b.com/2.mp4" poster="..."]
 *
 * @package Mp4Embed
 * @author 取舍
 * @version 1.1.0
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
        echo '<link rel="stylesheet" href="' . $base . '/style.css?v=1.1.0" />' . "\n";
        echo '<script>window.__MP4EMBED_BASE__ = ' . json_encode($base) . ';</script>' . "\n";
        echo '<script src="' . $base . '/embed.js?v=1.1.0" defer></script>' . "\n";
    }

    /**
     * 解析短代码，将 [mp4] 替换为 HTML 结构
     * 支持：
     *   [mp4]URL[/mp4]
     *   [mp4 src="URL" poster="POSTER"]
     *   [mp4]URL1|URL2[/mp4]
     *   [mp4 src="URL1|URL2" poster="POSTER"]
     */
    public static function parse($content, $widget, $lastResult)
    {
        $text = empty($lastResult) ? $content : $lastResult;
        $text = preg_replace('/(?:&#91;|&#x5B;|&lbrack;|&amp;#91;|&amp;#x5B;|&amp;lbrack;)\/mp4(?:&#93;|&#x5D;|&rbrack;|&amp;#93;|&amp;#x5D;|&amp;rbrack;)/i', '[/mp4]', $text);
        $text = preg_replace_callback('/(?:&#91;|&#x5B;|&lbrack;|&amp;#91;|&amp;#x5B;|&amp;lbrack;)\s*mp4([\\s\\S]*?)(?:&#93;|&#x5D;|&rbrack;|&amp;#93;|&amp;#x5D;|&amp;rbrack;)/i', function($m) {
            $inner = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
            $inner = html_entity_decode($inner, ENT_QUOTES, 'UTF-8');
            return '[mp4' . $inner . ']';
        }, $text);

        // 1) [mp4]...[/mp4]
        $text = preg_replace_callback('/\[mp4\](.+?)\[\/mp4\]/is', function($m) {
            $sources = Mp4Embed_Plugin::parseSources($m[1]);
            return Mp4Embed_Plugin::buildHtml($sources, null);
        }, $text);

        // 2) [mp4 src="..." poster="..."]
        $text = preg_replace_callback('/\[mp4\s+([^\]]+)\]/i', function($m) {
            $attr = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
            $attr = html_entity_decode($attr, ENT_QUOTES, 'UTF-8');
            $attr = str_replace(array('“', '”', '‘', '’'), array('"', '"', "'", "'"), $attr);
            $attr = str_replace("\xC2\xA0", ' ', $attr);
            $src = Mp4Embed_Plugin::getAttrValue($attr, 'src');
            $poster = Mp4Embed_Plugin::getAttrValue($attr, 'poster');
            $sources = Mp4Embed_Plugin::parseSources($src);
            $attrSources = Mp4Embed_Plugin::parseSources($attr);
            if (count($attrSources) > count($sources)) {
                $sources = $attrSources;
            }
            return Mp4Embed_Plugin::buildHtml($sources, $poster);
        }, $text);

        return $text;
    }

    /**
     * 解析多线路输入，允许使用 |、换行、逗号、顿号、分号分隔。
     */
    private static function getAttrValue($attr, $name)
    {
        $name = preg_quote($name, '/');
        if (preg_match('/' . $name . '\s*=\s*"([^"]+)"/i', $attr, $mm)
            || preg_match("/" . $name . "\s*=\s*'([^']+)'/i", $attr, $mm)
            || preg_match('/' . $name . '\s*=\s*([^\s"\']+)/i', $attr, $mm)) {
            return trim($mm[1]);
        }
        return null;
    }

    private static function parseSources($value)
    {
        if (!is_string($value)) {
            return array();
        }
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        $value = trim($value);
        if ($value === '') {
            return array();
        }
        $value = str_replace(array('｜', '¦'), '|', $value);
        $value = str_replace(array('&vert;', '&Vert;', '&#124;', '&#x7c;', '&#x7C;'), '|', $value);
        $value = str_replace(array('“', '”', '‘', '’'), array('"', '"', "'", "'"), $value);
        $value = str_replace("\xC2\xA0", ' ', $value);
        $value = preg_replace('/<br\s*\/?>/i', "\n", $value);
        $value = strip_tags($value);

        $sources = array();
        if (preg_match_all('/https?:\/\/[^\s"\'<>]+?\.mp4(?:\?[^\s"\'<>]*)?/i', $value, $matches)) {
            foreach ($matches[0] as $match) {
                $match = trim($match);
                if ($match !== '' && !in_array($match, $sources, true)) {
                    $sources[] = $match;
                }
            }
        }
        if (count($sources) >= 2) {
            return $sources;
        }

        $hasSeparator = preg_match('/[\r\n\|,;\x{FF0C}\x{3001}\x{FF1B}]/u', $value);
        if ($hasSeparator) {
            $parts = preg_split('/[\r\n\|,;\x{FF0C}\x{3001}\x{FF1B}]+/u', $value);
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                if (preg_match('/https?:\/\/[^\s"\'<>]+?\.mp4(?:\?[^\s"\'<>]*)?/i', $part, $mm)) {
                    $url = $mm[0];
                    if (!in_array($url, $sources, true)) {
                        $sources[] = $url;
                    }
                }
            }
        }
        return $sources;
    }

    /**
     * 构建最终 HTML。默认不自动播放，点击覆盖层后设置 src 并播放。
     */
    private static function buildHtml($sources, $poster = null)
    {
        if (!is_array($sources)) {
            $sources = self::parseSources((string)$sources);
        }
        if (empty($sources)) {
            return '';
        }

        $valid = array();
        foreach ($sources as $src) {
            $src = trim($src);
            if ($src === '') {
                continue;
            }
            if (!preg_match('/\.mp4(\?.*)?$/i', $src)) {
                continue;
            }
            $valid[] = $src;
        }

        if (empty($valid)) {
            return '';
        }

        $posterAttr = $poster && filter_var($poster, FILTER_VALIDATE_URL) ? ' data-poster="' . htmlspecialchars($poster, ENT_QUOTES, 'UTF-8') . '"' : '';
        if (count($valid) === 1) {
            $srcAttr = ' data-src="' . htmlspecialchars($valid[0], ENT_QUOTES, 'UTF-8') . '"';
            $html = <<<HTML
<div class="ty-mp4-embed"$srcAttr$posterAttr>
  <div class="ty-mp4-overlay" role="button" tabindex="0" aria-label="点击播放视频">▶ 点击播放</div>
  <video class="ty-mp4-video" preload="metadata" playsinline webkit-playsinline></video>
</div>
HTML;
            return $html;
        }

        $json = htmlspecialchars(json_encode($valid, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        $lines = array();
        for ($i = 0; $i < count($valid); $i++) {
            $index = $i + 1;
            $active = $i === 0 ? ' is-active' : '';
            $lines[] = '<button type="button" class="ty-mp4-line' . $active . '" data-index="' . $i . '">线路' . $index . '</button>';
        }
        $linesHtml = '<div class="ty-mp4-lines" role="tablist" aria-label="线路选择">' . implode('', $lines) . '</div>';
        $html = <<<HTML
<div class="ty-mp4-embed" data-sources="$json"$posterAttr>
  $linesHtml
  <div class="ty-mp4-overlay" role="button" tabindex="0" aria-label="点击播放视频">▶ 点击播放</div>
  <video class="ty-mp4-video" preload="metadata" playsinline webkit-playsinline></video>
</div>
HTML;
        return $html;
    }
}



