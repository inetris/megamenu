<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponentTemplate $this */

$menuList = array();
$level = 0;
$lastInd = 0;
$parents = array();
foreach ($arResult as $arItem) {
    $level = $arItem['DEPTH_LEVEL'];

    if ($arItem['IS_PARENT']) {
        $arItem['CHILDREN'] = array();
    }

    if ($level == 1) {
        $menuList[] = $arItem;
        $lastInd = count($menuList) - 1;
        $parents[$level] = &$menuList[$lastInd];
    } else {
        $parents[$level - 1]['CHILDREN'][] = $arItem;
        $lastInd = count($parents[$level - 1]['CHILDREN']) - 1;
        $parents[$level] = &$parents[$level - 1]['CHILDREN'][$lastInd];
    }

}

$arResult = $menuList;
$this->__component->SetResultCacheKeys(array("CACHED_TPL"));