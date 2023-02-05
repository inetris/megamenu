<?php
namespace Inetris\Megamenu;

use Inetris\Megamenu\helpers\HtmlBase as Html;


class Megamenu
{
    public static function getItems($a, $parentType = null)
    {
        ob_start();
        foreach ($a as $arItem) {
            $tagElement = 'div';
            if ($arItem['DEPTH_LEVEL'] == 1) {
                $tagElement = 'li';
            }

            $optionsElement = [];
            //$optionsElement = ['class' => 'nav-item'];

            if ($arItem["SELECTED"]) {
                Html::addCssClass($optionsElement, ['active']);
            }

            if ($arItem["IS_PARENT"]
                && ($arItem['PARAMS']['MEGAMENU_SUBMENU_TYPE'] == "dropdown_grid")
                && ($arItem['DEPTH_LEVEL'] == 1)
            ) {
                Html::addCssClass($optionsElement, ['dropdown', 'megamenu-fullwidth']);
            }

            if ($arItem["IS_PARENT"]
                && ($arItem['PARAMS']['MEGAMENU_SUBMENU_TYPE'] == "dropdown_list")
                && ($arItem['DEPTH_LEVEL'] == 1)
            ) {
                Html::addCssClass($optionsElement, ['dropdown']);
            }

            if (!empty($arItem['PARAMS']['MEGAMENU_ITEM_CLASS'])) {
                Html::addCssClass($optionsElement, [$arItem['PARAMS']['MEGAMENU_ITEM_CLASS']]);
            } elseif ($parentType == 'dropdown_grid') {
                Html::addCssClass($optionsElement, ['col-lg-4']);
            } elseif ($parentType == 'card') {
                Html::addCssClass($optionsElement, ['list-group-item']);
            }

            echo Html::beginTag($tagElement, $optionsElement);

            //INNER
            $tagLink = 'span';
            $optionsLink = $optionsInner = $optionsDesc = [];
            $optionsInner['href'] = $arItem["LINK"] ?? '#';

            $tagInner = 'a';
            if ($arItem['PARAMS']['MEGAMENU_LINK_TYPE'] == 'h3') {
                $tagLink = 'h4';
                if ($arItem['DEPTH_LEVEL'] > 1) {
                    Html::addCssClass($optionsInner, ['dropdown-item']);
                } else {
                    Html::addCssClass($optionsInner, ['nav-link']);
                }
            } elseif ($arItem['PARAMS']['MEGAMENU_LINK_TYPE'] == 'button') {
                Html::addCssClass($optionsInner, ['btn', 'btn-secondary']);
            } elseif ($parentType == 'card') {

            }else{
                if ($arItem['DEPTH_LEVEL'] > 1) {
                    Html::addCssClass($optionsInner, ['dropdown-item']);
                }else{
                    Html::addCssClass($optionsInner, ['nav-link']);
                }
            }

            if (empty($arItem['LINK']) && $arItem['DEPTH_LEVEL'] > 1) {    //TODO exclude dropdown
                Html::addCssClass($optionsInner, ['disabled']);
            }

            if (!empty($arItem['PARAMS']['MEGAMENU_LINK_CLASS'])) {
                Html::addCssClass($optionsInner, [$arItem['PARAMS']['MEGAMENU_LINK_CLASS']]);
            }

            if ($arItem["IS_PARENT"]
                && (
                    $arItem['PARAMS']['MEGAMENU_SUBMENU_TYPE'] == 'dropdown_grid'
                    || $arItem['PARAMS']['MEGAMENU_SUBMENU_TYPE'] == 'dropdown_list'
                )
                && ($arItem['DEPTH_LEVEL'] == 1)
            ) { //TODO duplicate
                $optionsInner['data'] = ['toggle' => 'dropdown'];
                Html::addCssClass($optionsInner, ['dropdown-toggle']);
            }

            echo Html::beginTag($tagInner, $optionsInner);
                echo Html::tag($tagLink, $arItem["TEXT"], $optionsLink);
            echo Html::endTag($tagInner);

            if ($parentType == 'card') {
                Html::addCssClass($optionsDesc, ['small', 'text-muted']);
                echo Html::tag('p', $arItem['PARAMS']['MEGAMENU_DESCRIPTION'], $optionsDesc);
            }

            //CHILD
                if ($arItem["IS_PARENT"]) {
                    $tagWrap = $tagWrap2 = 'div';
                    $tagChild = 'div';
                    $optionsChild = array();
                    $optionsWrap = $optionsWrap2 = array();

                    if ($arItem["IS_PARENT"]
                        && (
                            $arItem['PARAMS']['MEGAMENU_SUBMENU_TYPE'] == 'dropdown_grid'
                            || $arItem['PARAMS']['MEGAMENU_SUBMENU_TYPE'] == 'dropdown_list'
                        )
                        && ($arItem['DEPTH_LEVEL'] == 1)
                    ) { //TODO duplicate
                        Html::addCssClass($optionsChild, ['dropdown-menu', 'p-0']);
                    }

                    if ($arItem['PARAMS']['MEGAMENU_SUBMENU_TYPE'] == 'dropdown_grid') {
                        Html::addCssClass($optionsWrap2, ['megamenu-content'.$arItem['DEPTH_LEVEL']]);
                        Html::addCssClass($optionsWrap, ['row']);
                    }

                    if ($arItem['PARAMS']['MEGAMENU_SUBMENU_TYPE'] == 'card') {
                        Html::addCssClass($optionsWrap2, ['card']);
                        Html::addCssClass($optionsWrap, ['list-group','list-group-flush']);
                    }

                    echo Html::beginTag($tagChild, $optionsChild);
                        echo Html::beginTag($tagWrap2, $optionsWrap2);
                        echo Html::beginTag($tagWrap, $optionsWrap);

                             echo self::getItems($arItem["CHILDREN"], $arItem['PARAMS']['MEGAMENU_SUBMENU_TYPE']);

                        echo Html::endTag($tagWrap);
                        echo Html::endTag($tagWrap2);
                    echo Html::endTag($tagChild);
                }
            echo Html::endTag($tagElement);
        }
        return ob_get_clean();
    }
} 
