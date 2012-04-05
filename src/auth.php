<?php

require_once (dirname(__FILE__).'/consts.php');
require_once (dirname(__FILE__).'/yamoney/ym.php');

$scope = '';
$arr = YMScope::getScopeArray();
$co = count($arr);
for ($i = 0; $i < $co; $i++) {
    $key = trim($arr[$i]);
    if (array_key_exists($key, $_POST)) {
        $scope = $scope . '' . $_POST[$key];
    }
}

$uri = YandexMoney::authorizeUri(Consts::CLIENT_ID, $scope, Consts::REDIRECT_URL);
header('Location: ' . $uri);
?>
