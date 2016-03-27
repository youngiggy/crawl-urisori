<?php
include __DIR__ . '/../vendor/autoload.php';

$client = new GuzzleHttp\Client();

$baseDir = '/home/plan9/urisori_cd';
$domain = 'http://www.urisori.co.kr';
$albumUrls = [
    '음반' => $domain . '/dokuwiki-home/doku.php?id=%EC%9D%8C%EB%B0%98%EA%B2%80%EC%83%89',
    '지역' => $domain . '/dokuwiki-home/doku.php?id=%EC%A7%80%EC%97%AD%EA%B2%80%EC%83%89',
    '분류' => $domain . '/dokuwiki-home/doku.php?id=%EB%B6%84%EB%A5%98%EA%B2%80%EC%83%89',
]
;
$album = '음반';
$albumUrl = $albumUrls[$album];
$res = $client->request('GET', $albumUrl);
$albumPage = $res->getBody();

/*
<li><a href="/dokuwiki-home/doku.php?id=cd-01" class="wikilink1" title="cd-01">CD-01: 농요[1] - 논고르기 / 모찌기 / 모심기 / 기타 농요</a></li>
*/
$subLinkPattern = '/li><a href="(\/[^"]*).*">(CD.*)<\/a><\/li/';
preg_match_all($subLinkPattern, $albumPage, $parsedAlbum);

if (empty($parsedAlbum[1])
    || empty($parsedAlbum[1])
    || count($parsedAlbum[1]) !== count($parsedAlbum[2])
) {
    throw new Exception('parsing album error');
}
foreach ($parsedAlbum[1] as $idx => $link) {
    $cdName = str_replace('/', '_', $parsedAlbum[2][$idx]);
//    echo $cdName . '=>' . $link . PHP_EOL;

    $res = $client->request('GET', $domain . $link);
    $cdPage = $res->getBody();

//    sleep(1);
    preg_match_all($subLinkPattern, $cdPage, $parsedCd);
    if (empty($parsedCd[1])
        || empty($parsedCd[1])
        || count($parsedCd[1]) !== count($parsedCd[2])
    ) {
        throw new Exception('parsing cd error');
    }
    foreach ($parsedCd[1] as $idxOfSong => $linkOfSong) {
        $songName = str_replace('/', '_', $parsedCd[2][$idxOfSong]);
        echo $cdName . ' | ' . $songName . '=>' . $linkOfSong . PHP_EOL;

        preg_match('/\d+$/', $linkOfSong, $id);

        $dir = $baseDir . '/' . $album;
        is_dir($dir) || mkdir($dir) || die("Can't Create album folder");

        $dir = $baseDir . '/' . $album . '/' . $cdName;
        is_dir($dir) || mkdir($dir) || die("Can't Create cd folder");

        $mp3Link = $domain . '/sound/se/se' . $id[0] . '.mp3';
        $filePath = $dir . '/' . $songName . '.mp3';

        if (!file_exists($filePath)) {
            $mp3 = file_get_contents($mp3Link);
            file_put_contents($filePath, $mp3);
        }
    }
    echo PHP_EOL;
}
