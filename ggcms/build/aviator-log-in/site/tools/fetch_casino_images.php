<?php
/**
 * Fetch one main image per donor page and save to images/casinos/.
 * Run: php tools/fetch_casino_images.php
 */
$rootDir = dirname(__DIR__);
$donors = [
    'battery-aviator'    => 'https://aviatorgameonline.game/battery-aviator/',
    'aviator-bet365'     => 'https://aviatorgameonline.game/aviator-bet365/',
    'bet9ja-aviator'     => 'https://aviatorgameonline.game/bet9ja-aviator/',
    'aviator-betplay'    => 'https://aviatorgameonline.game/aviator-betplay/',
    'betway-aviator'     => 'https://aviatorgameonline.game/betway-aviator/',
    'bluechip-aviator'   => 'https://aviatorgameonline.game/bluechip-aviator/',
    'dafabet-aviator'    => 'https://aviatorgameonline.game/dafabet-aviator/',
    'bolabet-aviator'    => 'https://aviatorgameonline.game/bolabet-aviator/',
    'elephant-bet-aviator' => 'https://aviatorgameonline.game/elephant-bet-aviator/',
    '1win-aviator'       => 'https://aviatorgameonline.game/1win-aviator/',
    '1xbet-aviator'      => 'https://aviatorgameonline.game/1xbet-aviator/',
    '4rabet-aviator'     => 'https://aviatorgameonline.game/4rabet-aviator/',
    '888bets-aviator'    => 'https://aviatorgameonline.game/888bets-aviator/',
    'aviator-golden-crown' => 'https://aviatorgameonline.game/aviator-golden-crown/',
    'parimatch-aviator'  => 'https://aviatorgameonline.game/parimatch-aviator/',
    'pin-up-aviator'     => 'https://aviatorgameonline.game/pin-up-aviator/',
    'premier-bet-aviator'=> 'https://aviatorgameonline.game/premier-bet-aviator/',
    'mostbet-aviator'    => 'https://aviatorgameonline.game/mostbet-aviator/',
    'msport-aviator'     => 'https://aviatorgameonline.game/msport-aviator/',
    'sportybet-aviator'  => 'https://aviatorgameonline.game/sportybet-aviator/',
];

$baseDir = $rootDir . '/images/casinos';
if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);

$skipPatterns = ['Aviator-Logo', 'favicon', 'curacao', 'mga'];
$results = [];
foreach ($donors as $slug => $url) {
    echo "Fetching: $slug ... ";
    $html = @file_get_contents($url);
    if ($html === false) { echo "FAIL (fetch)\n"; $results[$slug] = null; continue; }
    if (!preg_match_all('#<img[^>]+src="(https?://[^"]+wp-content/uploads/[^"]+\.(?:jpg|jpeg|png|webp|gif))"#i', $html, $m)) { echo "FAIL (no images)\n"; $results[$slug] = null; continue; }
    $imgUrl = null;
    foreach ($m[1] as $src) {
        $skip = false;
        foreach ($skipPatterns as $p) { if (stripos($src, $p) !== false) { $skip = true; break; } }
        if (!$skip) { $imgUrl = $src; break; }
    }
    if (!$imgUrl) $imgUrl = $m[1][0];
    $ext = pathinfo(parse_url($imgUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
    if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp', 'gif'])) $ext = 'webp';
    $filename = $slug . '.' . $ext;
    $path = $baseDir . '/' . $filename;
    $imgData = @file_get_contents($imgUrl);
    if ($imgData === false || file_put_contents($path, $imgData) === false) { echo "FAIL (download)\n"; $results[$slug] = null; continue; }
    echo "OK -> $filename\n";
    $results[$slug] = $filename;
}

$jsonPath = $rootDir . '/json/casinos/casino_articles-import.json';
$data = json_decode(file_get_contents($jsonPath), true);
if (!$data || empty($data['rows'])) { echo "JSON not found or empty.\n"; exit(1); }
foreach ($data['rows'] as $i => &$row) {
    $slug = isset($row['url']) ? $row['url'] : '';
    if ($slug && isset($results[$slug])) $row['img'] = $results[$slug];
}
file_put_contents($jsonPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Updated JSON img fields.\n";
