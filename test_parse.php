<?php
$domain = "127.0.0.1";
if (!preg_match('#^https?://#i', $domain)) {
    $domain = 'http://' . $domain;
}
echo parse_url($domain, PHP_URL_HOST) . "\n";
echo "WWWW\n";
$domain2 = "centralerp.planettecglobal.com";
if (!preg_match('#^https?://#i', $domain2)) {
    $domain2 = 'http://' . $domain2;
}
echo parse_url($domain2, PHP_URL_HOST) . "\n";
