<?php
namespace Pyncer\Snyppet\Communication;

use DOMDocument;
use DOMXPath;

use function Pyncer\he as pyncer_he;

function is_html(string $value): bool
{
    return preg_match('/<[^>]+>/', $value) ||
        preg_match('/&[a-z]+;|&#\d+;/', $value);
}

function text_to_html(string $text): string
{
    $text = trim($text);

    if ($text === '') {
        return '';
    }

    $text = str_replace(["\r\n", "\r"], "\n", $text);

    // Split by two+ newlines for paraphraphs
    $paragraphs = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);

    $html = '';
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);

        if ($paragraph === '') {
            continue;
        }

        $paragraph = pyncer_he($paragraph);

        $paragraph = preg_replace_callback(
            '#(https?://|www\.)[^\s<>"\']+[^\s<>"\']*[^\s<>"\']#i',
            function ($matches) {
                $url = $matches[0];

                if (str_starts_with($url, 'www.')) {
                    $url = 'https://' . $url;
                }

                $display = $matches[0];

                return '<a href="' . pyncer_he($url) . '">' .
                    pyncer_he($matches[0]) .
                    '</a>';
            },
            $paragraph
        );

        // Convert single newlines inside paragraph to <br>
        $paragraph = nl2br($paragraph, false);

        $html .= '<p>' . $paragraph . '</p>' . "\n";
    }

    return trim($html);
}

private function html_to_text(string $html): string
{
    $html = trim($html);

    if ($html === '') {
        return '';
    }

    $html = htmlentities($html, ENT_COMPAT | ENT_HTML401, 'UTF-8');
    $html = htmlspecialchars_decode($html, ENT_QUOTES | ENT_HTML401);
    $html = '<?xml encoding="UTF-8"?>' . $html;

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);

    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    foreach ($dom->childNodes as $item) {
        if ($item->nodeType === XML_PI_NODE) {
            $dom->removeChild($item);
            break;
        }
    }

    $xpath = new DOMXPath($dom);

    // Convert links to 'text (url)' format
    foreach ($xpath->query('//a[@href]') as $link) {
        $url = trim($link->getAttribute('href'));
        $text = trim($link->textContent);
        if ($url !== '') {
            if ($text === $url) {
                $replacement = $dom->createTextNode($url);
                $link->parentNode->replaceChild($replacement, $link);
            } elseif ($text !== '') {
                $replacement = $dom->createTextNode($text . ' (' . $url . ')');
                $link->parentNode->replaceChild($replacement, $link);
            }
        }
    }

    // Remove non text tags
    foreach (['script', 'style', 'head', 'title', 'meta', 'link', 'img', 'svg'] as $tag) {
        foreach ($xpath->query("//{$tag}") as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    $text = $dom->textContent;

    // Clean up spacing
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/\n\s*\n\s*\n/', "\n\n", $text);
    $text = trim($text);

    return $text;
}
