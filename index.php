<?php
use Ddeboer\Tesseract\Tesseract;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Mink\Driver\GoutteDriver;
include 'vendor/autoload.php';

$ocr = new Tesseract('/usr/bin/tesseract');

function getTextFromImage($file) {
    $background = imagecreatefromjpeg('background.jpg');
    $image = imagecreatefromstring($file);
    $black = imagecolorallocate($image, 0, 0, 0);
    $min_visible_y = $max_y = imagesy($image);
    $min_visible_x = $max_x = imagesx($image);
    $max_visible_x = $max_visible_y = 0;
    for($y=0;$y<$max_y;$y++) {
        for($x=0;$x<$max_x;$x++) {
            $pixel = ImageColorAt($image, $x, $y);
            $colors = imagecolorsforindex($image, $pixel);
            $pixel_bg = ImageColorAt($background, $x, $y);
            $colors_bg = imagecolorsforindex($background, $pixel_bg);
            $range = 35;
            if($colors['red']+$range > $colors_bg['red'] && $colors['red']-$range < $colors_bg['red']) {
                imagesetpixel($image, $x, $y, $black);
            } else {
                $min_visible_x = $min_visible_x > $x ? $x : $min_visible_x;
                $max_visible_x = $max_visible_x < $x ? $x : $max_visible_x;
                $min_visible_y = $min_visible_y > $y ? $y : $min_visible_y;
                $max_visible_y = $max_visible_y < $y ? $y : $max_visible_y;
            }
        }
    }
    
    $image = imagecrop($image, ['x' => $min_visible_x, 'y' => $min_visible_y, 'width' => $max_visible_x, 'height' => $max_visible_y]);
    
    imagefilter($image, IMG_FILTER_GRAYSCALE);
    $tmpfname = tempnam("/tmp", "OCR");
    imagepng($image, $tmpfname);
    $txt = $ocr->recognize($tmpfname, ['eng'], 3);
    unlink($tmpfname);
    return str_replace("\n", "", $txt);
}
// Get cURL resource
$curl = curl_init();
// Set some options - we are passing in a useragent too here
curl_setopt_array($curl, array(
    CURLOPT_VERBOSE => 1,
    CURLOPT_CERTINFO => 0,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_FOLLOWLOCATION => 1,
//     CURLOPT_SSL_VERIFYHOST => 0,
//     CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_SSLVERSION => 3,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Connection: keep-alive',
        'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:41.0) Gecko/20100101 Firefox/41.0',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Encoding: gzip, deflate',
        'Cache-Control: max-age=0'
    ],
    CURLOPT_URL => 'https://agenciavirtual.light.com.br/LASView/captcha.jpg',
    CURLOPT_USERAGENT => 'Codular Sample cURL Request'
));
// Send the request & save response to $resp
$resp = curl_exec($curl);
var_dump(curl_error($curl));
var_dump(curl_getinfo($curl, CURLINFO_HTTP_CODE));
// var_dump(curl_getinfo($curl));
var_dump($resp);
// Close request to clear up some resources
curl_close($curl);

return;
$mink = new Mink([
    'goutte' => new Session(new GoutteDriver())
]);
$session = $mink->getSession('goutte');
$session->visit('https://agenciavirtual.light.com.br/LASView/av/emissaosegundavia/emissaoSegundaVia.do');
$return = $session->getPage();

echo getTextFromImage($file);