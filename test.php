<?php

use PiedWeb\Google\Provider\Puphpeteer;
use PiedWeb\Google\Provider\PuphpeteerExtractor;
use PiedWeb\Google\Sleeper;

include 'vendor/autoload.php';

$url = 'https://www.google.com/search?q=pied%20web';
$puphpeteer = new Puphpeteer();
$puphpeteerExtractor = new PuphpeteerExtractor($puphpeteer);
$results = [];

$puphpeteer->instantiate();
$puphpeteer->get($url); // or load from html
//$results = array_merge($puphpeteerExtractor->getOrganicResults(), $results);
file_put_contents('var/cache/'.sha1($puphpeteer->getBrowserPage()->url()).'.html', $puphpeteer->getPageContent());
usleep(rand(1000000 * 2, 1000000 * 3)); // 2-3 seconds
$puphpeteer->getNextPage();
$results = array_merge($puphpeteerExtractor->getOrganicResults(), $results);
file_put_contents('var/cache/'.sha1($puphpeteer->getBrowserPage()->url()).'.html', $puphpeteer->getPageContent());

dd($results);