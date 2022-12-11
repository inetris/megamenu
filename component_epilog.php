<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $templateData */
/** @var @global CMain $APPLICATION */
use Bitrix\Main\Loader;
global $APPLICATION;
global $USER;

echo preg_replace_callback(
    "/{{usermenu_([\w.]+)}}/is".BX_UTF_PCRE_MODIFIER,
    create_function('$matches', '
        ob_start();

        ob_get_clean();
    return $returnStr;'),
    $arResult["CACHED_TPL"]
    );
?>

