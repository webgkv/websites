<?php
/**
 * Fetch full content from donor pages; update json/casinos/casino_articles-import.json.
 * Run: php tools/fetch_casino_full_content.php
 */
$rootDir = dirname(__DIR__);
$donors = [
    'battery-aviator'      => 'https://aviatorgameonline.game/battery-aviator/',
    'aviator-bet365'       => 'https://aviatorgameonline.game/aviator-bet365/',
    'bet9ja-aviator'       => 'https://aviatorgameonline.game/bet9ja-aviator/',
    'aviator-betplay'      => 'https://aviatorgameonline.game/aviator-betplay/',
    'betway-aviator'       => 'https://aviatorgameonline.game/betway-aviator/',
    'bluechip-aviator'     => 'https://aviatorgameonline.game/bluechip-aviator/',
    'dafabet-aviator'      => 'https://aviatorgameonline.game/dafabet-aviator/',
    'bolabet-aviator'      => 'https://aviatorgameonline.game/bolabet-aviator/',
    'elephant-bet-aviator' => 'https://aviatorgameonline.game/elephant-bet-aviator/',
    '1win-aviator'         => 'https://aviatorgameonline.game/1win-aviator/',
    '1xbet-aviator'        => 'https://aviatorgameonline.game/1xbet-aviator/',
    '4rabet-aviator'       => 'https://aviatorgameonline.game/4rabet-aviator/',
    '888bets-aviator'      => 'https://aviatorgameonline.game/888bets-aviator/',
    'aviator-golden-crown' => 'https://aviatorgameonline.game/aviator-golden-crown/',
    'parimatch-aviator'    => 'https://aviatorgameonline.game/parimatch-aviator/',
    'pin-up-aviator'       => 'https://aviatorgameonline.game/pin-up-aviator/',
    'premier-bet-aviator'  => 'https://aviatorgameonline.game/premier-bet-aviator/',
    'mostbet-aviator'      => 'https://aviatorgameonline.game/mostbet-aviator/',
    'msport-aviator'       => 'https://aviatorgameonline.game/msport-aviator/',
    'sportybet-aviator'    => 'https://aviatorgameonline.game/sportybet-aviator/',
];

$baseDir = $rootDir . '/images/casinos';
if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);

$skipImgPatterns = ['Aviator-Logo', 'favicon', 'curacao', 'mga', 'avatar'];
$results = [];

foreach ($donors as $slug => $url) {
    echo "Fetching content: $slug ... ";
    $html = @file_get_contents($url);
    if ($html === false) { echo "FAIL (fetch)\n"; $results[$slug] = ['text' => null, 'img' => null]; continue; }
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXPath($dom);
    $contentNode = null;
    $nodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' entry-content ')]");
    if ($nodes->length) $contentNode = $nodes->item(0);
    if (!$contentNode) {
        $nodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' post-content ')]");
        if ($nodes->length) $contentNode = $nodes->item(0);
    }
    if (!$contentNode) $contentNode = $xpath->query("//body")->item(0);
    if (!$contentNode) { echo "FAIL (no content)\n"; $results[$slug] = ['text' => null, 'img' => null]; continue; }

    $hostBase = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);
    $imageUrls = [];
    $images = $xpath->query(".//img[@src]", $contentNode);
    foreach ($images as $img) {
        $src = $img->getAttribute('src');
        if (strpos($src, 'wp-content/uploads') === false && strpos($src, 'uploads') === false) continue;
        foreach ($skipImgPatterns as $p) { if (stripos($src, $p) !== false) continue 2; }
        if (strpos($src, 'http') !== 0) $src = $src[0] === '/' ? $hostBase . $src : $hostBase . '/' . ltrim($src, '/');
        $imageUrls[] = $src;
    }
    $imageUrls = array_unique($imageUrls);

    $imgMap = [];
    $imgIndex = 0;
    foreach ($imageUrls as $imgUrl) {
        $ext = strtolower(pathinfo(parse_url($imgUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) $ext = 'webp';
        $imgIndex++;
        $filename = $slug . '-' . $imgIndex . '.' . $ext;
        $path = $baseDir . '/' . $filename;
        $data = @file_get_contents($imgUrl);
        if ($data !== false && file_put_contents($path, $data) !== false) {
            $localPath = '/images/casinos/' . $filename;
            $imgMap[$imgUrl] = $localPath;
            $rel = parse_url($imgUrl, PHP_URL_PATH);
            if ($rel) $imgMap[$rel] = $localPath;
        }
    }
    $firstImgFilename = null;
    foreach ($imageUrls as $u) { if (isset($imgMap[$u])) { $firstImgFilename = basename($imgMap[$u]); break; } }
    if (!$firstImgFilename) $firstImgFilename = $slug . '-1.webp';

    $innerHtml = '';
    foreach ($contentNode->childNodes as $child) $innerHtml .= $dom->saveHTML($child);
    $innerHtml = preg_replace_callback('/<img([^>]*)\ssrc="([^"]+)"/i', function ($m) use ($imgMap) {
        return '<img' . $m[1] . ' src="' . (isset($imgMap[$m[2]]) ? $imgMap[$m[2]] : $m[2]) . '"';
    }, $innerHtml);
    $innerHtml = preg_replace('/<a\s[^>]*href="[^"]*"[^>]*>/i', '<a href="#">', $innerHtml);
    $innerHtml = preg_replace('/\s*href="[^"]*"/i', ' href="#"', $innerHtml);
    $innerHtml = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $innerHtml);
    $innerHtml = preg_replace('/Table of Contents[\s\S]*?→ TABLE OF CONTENTS/i', '', $innerHtml);
    $innerHtml = preg_replace('/Other Aviator Casinos[\s\S]*?(?=<h2|$)/i', '', $innerHtml);
    $innerHtml = preg_replace('/Explore Casinos with Aviator[\s\S]*?(?=<h2|$)/i', '', $innerHtml);
    $innerHtml = trim($innerHtml);
    if (strpos($innerHtml, '<h1') === false && preg_match('/<h1[^>]*>.*?<\/h1>/is', $html, $h1)) $innerHtml = $h1[0] . "\n\n" . $innerHtml;
    $innerHtml = str_replace(['<p></p>', '<p> </p>'], '', $innerHtml);
    $innerHtml = preg_replace('/\n{3,}/', "\n\n", $innerHtml);
    $innerHtml = '<div class="casino-article-content">' . $innerHtml . '</div>';

    $results[$slug] = ['text' => $innerHtml, 'img' => $firstImgFilename ?: ($slug . '-1.webp')];
    echo "OK (images: " . count($imgMap) . ", len: " . strlen($innerHtml) . ")\n";
}

$jsonPath = $rootDir . '/json/casinos/casino_articles-import.json';
$data = json_decode(file_get_contents($jsonPath), true);
if (!$data || empty($data['rows'])) { echo "JSON not found or empty.\n"; exit(1); }
foreach ($data['rows'] as $i => &$row) {
    $slug = isset($row['url']) ? $row['url'] : '';
    if ($slug && isset($results[$slug]) && $results[$slug]['text'] !== null) {
        $row['text'] = $results[$slug]['text'];
        if ($results[$slug]['img']) $row['img'] = $results[$slug]['img'];
    }
}
file_put_contents($jsonPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Updated JSON.\n";
