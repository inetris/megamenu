<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use \Bitrix\Main\Application;
use \Bitrix\Main\IO\Directory;
use \Bitrix\Main\IO\File;

Loc::loadMessages(__FILE__);


class inetris_megamenu extends CModule
{
    public $VENDOR = "inetris";
    public $MODULE_ID = "inetris.megamenu";
    public $iBlockTypeId = "megamenu";
    public $iBlockCode = "megamenu_catalog";


    function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $this->MODULE_NAME = Loc::getMessage("MEGAMENU_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("MEGAMENU_DESCRIPTION");
        $this->MODULE_GROUP_RIGHTS = "N";

        $this->PARTNER_NAME = Loc::getMessage("MEGAMENU_PARTNER_NAME");
        $this->PARTNER_URI = "https://inetris.ru";
    }


    function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallDB();
        $this->InstallFiles();
        $this->InstallEvents();
    }


    function DoUninstall()
    {
        $this->UnInstallDB();
        $this->UnInstallEvents();
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    function InstallFiles()
    {
        if (Directory::isDirectoryExists($path = $this->GetPath() . "/install")) {

            CopyDirFiles(
                $this->GetPath()."/install/local/templates/.default/components/bitrix/menu/megamenu/",
            $_SERVER["DOCUMENT_ROOT"] . "/local/templates/.default/components/bitrix/menu/megamenu/",
             true, true);

            CopyDirFiles(
                $this->GetPath()."/install/local/components/{$this->VENDOR}/megamenu.sections/",
            $_SERVER["DOCUMENT_ROOT"] . "/local/components/{$this->VENDOR}/megamenu.sections/",
             true, true);

            CopyDirFiles(
                $this->GetPath()."/install/site/",
                $_SERVER["DOCUMENT_ROOT"],
                true, true);
            return TRUE;
        } else {
            throw new InvalidPathException($path);
            return true;
        }
    }

    function UnInstallFiles()
    {
        if (Directory::isDirectoryExists($path = $this->GetPath() . "/install")) {	//TODO verify files

            Directory::deleteDirectory(
                $_SERVER["DOCUMENT_ROOT"] . "/local/templates/.default/components/bitrix/menu/megamenu/");

            Directory::deleteDirectory(
                $_SERVER["DOCUMENT_ROOT"] . "/local/components/{$this->VENDOR}/");

            //$path = Application::getDocumentRoot() . "/.top.menu.php";
            //File::isFileExists($path);
            //File::deleteFile($path);
			
			$path = Application::getDocumentRoot() . "/.top.menu_ext.php";
            File::isFileExists($path);
            File::deleteFile($path);
			

            return TRUE;
        } else {
            throw new InvalidPathException($path);
            return true;
        }
    }


    function InstallDB()
    {

        Loader::includeModule($this->MODULE_ID);
        Loader::includeModule("iblock");
		global $APPLICATION;

        $iBlockTypeId = $this->iBlockTypeId;

        $iBlockCode = $this->iBlockCode;
        $iBlockName = Loc::getMessage("MEGAMENU_IBLOCK_NAME");


        $obTypesIblock = CIBlockType::GetList(
            [],
            ["ID" => $iBlockTypeId]
        );
        $arTypesIblock = $obTypesIblock->Fetch();
        if (empty($arTypesIblock)) {
            $arFields = [
                "ID" => $iBlockTypeId,
                "SECTIONS" => "Y",
                "IN_RSS" => "N",
                "SORT" => 500,
                "LANG" => [
                    "ru" => [
                        "NAME" => Loc::getMessage("MEGAMENU_NAME"),
                    ],
                    "en" => [
                        "NAME" => "Megamenu",
                    ]
                ]
            ];
            (new CIBlockType)->Add($arFields);
        }


        $obIblock = CIBlock::GetList(
            [],
            [
                "CODE" => $iBlockCode,
                "IBLOCK_TYPE_ID" => $iBlockName
            ]
        );
        $arIblock = $obIblock->Fetch();
        if (empty($arIblock)) {
            $obLids = CSite::GetList(
                $sBy = "sort",
                $sOrder = "desc",
                []
            );
            $arLids = [];
            while ($arLid = $obLids->Fetch()) {
                $arLids[] = $arLid["ID"];
            }

            $arNewIblockFields = [
                "NAME" => $iBlockName,
                "CODE" => $iBlockCode,
                "IBLOCK_TYPE_ID" => $iBlockTypeId,
				"XML_ID" => $iBlockCode,
                "LID" => $arLids,
                "INDEX_ELEMENT" => "N",
                "LIST_PAGE_URL" => "",
                "SECTION_PAGE_URL" => "#SECTION_CODE#",
                "DETAIL_PAGE_URL" => "#SECTION_CODE#",
                "FIELDS" => [
                    "SECTION_CODE" => [
                        "IS_REQUIRED" => "N",
                        "DEFAULT_VALUE" => [
                            "UNIQUE" => "N",
                            "TRANSLITERATION" => "Y",
                            "TRANS_LEN" => "30",
                            "TRANS_CASE" => "L",
                            "TRANS_SPACE" => "_",
                            "TRANS_OTHER" => "_"
                        ],
                    ],
                    "SECTION_DESCRIPTION" => [
                        "IS_REQUIRED" => "N",
                    ],
                    "CODE" => [
                        "IS_REQUIRED" => "N",
                        "DEFAULT_VALUE" => [
                            "UNIQUE" => "N",
                            "TRANSLITERATION" => "Y",
                            "TRANS_LEN" => "30",
                            "TRANS_CASE" => "L",
                            "TRANS_SPACE" => "_",
                            "TRANS_OTHER" => "_"
                        ]
                    ]
                ]
            ];
            $iBlockId = (new CIBlock())->Add($arNewIblockFields);
            if ($iBlockId) {

                CIBlock::SetPermission(
                    $iBlockId,
                    [
                        "2" => "R"
                    ]
                );

                $info = array();

                function oGetMessage($key, $fields) {
                    $messages = array(
                        'USER_TYPE_UPDATE' => Loc::getMessage('USER_TYPE_UPDATE'),
                        'USER_TYPE_UPDATE_ERROR' => Loc::getMessage('USER_TYPE_UPDATE_ERROR'),
                        'USER_TYPE_ADDED' => Loc::getMessage('USER_TYPE_ADDED'),
                        'USER_TYPE_ADDED_ERROR' => Loc::getMessage('USER_TYPE_ADDED_ERROR'),
                        'USER_TYPE_ENUMS_SET_ERROR' => Loc::getMessage('USER_TYPE_ENUMS_SET_ERROR'),
                    );
                    return isset($messages[$key])
                        ? str_replace(array_keys($fields), array_values($fields), $messages[$key])
                        : '';
                }

                $aUserFields = array(
                    array(
                        'ENTITY_ID' => "IBLOCK_{$iBlockId}_SECTION",
                        'FIELD_NAME' => 'UF_MEGAMENU_SUBMENU_TYPE',
                        'USER_TYPE_ID' => 'enumeration',
                        'SORT' => 500,
                        'MULTIPLE' => 'N',
                        'MANDATORY' => 'Y',
                        'IS_SEARCHABLE' => 'N',
                        'SETTINGS' => array(
                            'DEFAULT_VALUE' => '',
                            'SIZE' => '40',
                            'ROWS' => '3',
                        ),
                        'EDIT_FORM_LABEL' => array(
                            'ru' => Loc::getMessage('UF_MEGAMENU_SUBMENU_TYPE'),
                            'en' => 'Submenu type',
                        ),
                        'LIST_COLUMN_LABEL' => array(
                            'ru' => Loc::getMessage('UF_MEGAMENU_SUBMENU_TYPE'),
                            'en' => 'Submenu type',
                        ),
                        'LIST_FILTER_LABEL' => array(
                            'ru' => Loc::getMessage('UF_MEGAMENU_SUBMENU_TYPE'),
                            'en' => 'Submenu type',
                        ),
                        'VALUES' => array(
                            array(
                                'VALUE'     => Loc::getMessage('UF_MEGAMENU_SUBMENU_TYPE_dropdown_grid'),
                                'XML_ID'    => 'dropdown_grid',
                                'DEF'       => 'N',
                                'SORT'      => 100,
                            ),
                            array(
                                'VALUE'     => Loc::getMessage('UF_MEGAMENU_SUBMENU_TYPE_dropdown_list'),
                                'XML_ID'    => 'dropdown_list',
                                'DEF'       => 'N',
                                'SORT'      => 200,
                            ),
                            array(
                                'VALUE'     => Loc::getMessage('UF_MEGAMENU_SUBMENU_TYPE_list'),
                                'XML_ID'    => 'list',
                                'DEF'       => 'Y',
                                'SORT'      => 200,
                            ),
							array(
                                'VALUE'     => Loc::getMessage('UF_MEGAMENU_SUBMENU_TYPE_card'),
                                'XML_ID'    => 'card',
                                'DEF'       => 'N',
                                'SORT'      => 300,
                            ),
                        ),
                    ),
                    array(
                        'ENTITY_ID' => "IBLOCK_{$iBlockId}_SECTION",
                        'FIELD_NAME' => 'UF_MEGAMENU_LINK_TYPE',
                        'USER_TYPE_ID' => 'enumeration',
                        'SORT' => 500,
                        'MULTIPLE' => 'N',
                        'MANDATORY' => 'Y',
                        'IS_SEARCHABLE' => 'N',
                        'SETTINGS' => array(
                            'DEFAULT_VALUE' => '',
                            'SIZE' => '40',
                            'ROWS' => '3',
                        ),
                        'EDIT_FORM_LABEL' => array(
                            'ru' => Loc::getMessage('UF_MEGAMENU_LINK_TYPE'),
                            'en' => 'Link type',
                        ),
                        'LIST_COLUMN_LABEL' => array(
                            'ru' => Loc::getMessage('UF_MEGAMENU_LINK_TYPE'),
                            'en' => 'Link type',
                        ),
                        'LIST_FILTER_LABEL' => array(
                            'ru' => Loc::getMessage('UF_MEGAMENU_LINK_TYPE'),
                            'en' => 'Link type',
                        ),
                        'VALUES' => array(
                            array(
                                'VALUE'     => Loc::getMessage('UF_MEGAMENU_LINK_TYPE_span'),
                                'XML_ID'    => 'span',
                                'DEF'       => 'Y',
                                'SORT'      => 100,
                            ),
                            array(
                                'VALUE'     => Loc::getMessage('UF_MEGAMENU_LINK_TYPE_h3'),
                                'XML_ID'    => 'h3',
                                'DEF'       => 'N',
                                'SORT'      => 200,
                            ),
                            array(
                                'VALUE'     => Loc::getMessage('UF_MEGAMENU_LINK_TYPE_button'),
                                'XML_ID'    => 'button',
                                'DEF'       => 'N',
                                'SORT'      => 300,
                            ),
                            array(
                                'VALUE'     => Loc::getMessage('UF_MEGAMENU_LINK_TYPE_p'),
                                'XML_ID'    => 'p',
                                'DEF'       => 'N',
                                'SORT'      => 400,
                            ),
                        ),
                    ),
                    array(
                        'ENTITY_ID' => "IBLOCK_{$iBlockId}_SECTION",
                        'FIELD_NAME' => 'UF_MEGAMENU_ITEM_CLASS',
                        'USER_TYPE_ID' => 'string',
                        'SORT' => 500,
                        'MULTIPLE' => 'N',
                        'MANDATORY' => 'N',
                        'IS_SEARCHABLE' => 'N',
                        'SETTINGS' => array(
                            'DEFAULT_VALUE' => '',
                            'SIZE' => '40',
                            'ROWS' => '1',
                        ),
                        'EDIT_FORM_LABEL' => array(
                            'ru' => Loc::getMessage('UF_MEGAMENU_ITEM_CLASS'),
                            'en' => 'Item class',
                        ),
                        'LIST_COLUMN_LABEL' => array(
                            'ru' => Loc::getMessage('UF_MEGAMENU_ITEM_CLASS'),
                            'en' => 'Item class',
                        ),
                        'LIST_FILTER_LABEL' => array(
                            'ru' => Loc::getMessage('UF_MEGAMENU_ITEM_CLASS'),
                            'en' => 'Item class',
                        ),
                    ),
					array(
                        'ENTITY_ID' => "IBLOCK_{$iBlockId}_SECTION",
                        'FIELD_NAME' => 'UF_MEGAMENU_LINK_CLASS',
                        'USER_TYPE_ID' => 'string',
                        'SORT' => 500,
                        'MULTIPLE' => 'N',
                        'MANDATORY' => 'N',
                        'IS_SEARCHABLE' => 'N',
                        'SETTINGS' => array(
                            'DEFAULT_VALUE' => '',
                            'SIZE' => '40',
                            'ROWS' => '1',
                        ),
                        'EDIT_FORM_LABEL' => array(
                            'ru' => Loc::getMessage('UF_MEGAMENU_LINK_CLASS'),
                            'en' => 'Link class',
                        ),
                        'LIST_COLUMN_LABEL' => array(
                            'ru' => Loc::getMessage('UF_MEGAMENU_LINK_CLASS'),
                            'en' => 'Link class',
                        ),
                        'LIST_FILTER_LABEL' => array(
                            'ru' => Loc::getMessage('UF_MEGAMENU_LINK_CLASS'),
                            'en' => 'Link class',
                        ),
                    ),

                );

                $oUserTypeEntity = new CUserTypeEntity();

                foreach ($aUserFields as $aUserField) {

                    $resProperty = CUserTypeEntity::GetList(
                        array(),
                        array('ENTITY_ID' => $aUserField['ENTITY_ID'], 'FIELD_NAME' => $aUserField['FIELD_NAME'])
                    );

                    if ($aUserHasField = $resProperty->Fetch()) {
                        $idUserTypeProp = $aUserHasField['ID'];
                        if ($oUserTypeEntity->Update($idUserTypeProp, $aUserField)) {
                            $info[] = oGetMessage('USER_TYPE_UPDATE', array(
                                '#FIELD_NAME#' => $aUserHasField['FIELD_NAME'],
                                '#ENTITY_ID#' => $aUserHasField['ENTITY_ID'],
                            ));
                        } else {
                            if (($ex = $APPLICATION->GetException())) {
                                throw new \Bitrix\Main\SystemException(oGetMessage('USER_TYPE_UPDATE_ERROR', array(
                                    '#FIELD_NAME#' => $aUserHasField['FIELD_NAME'],
                                    '#ENTITY_ID#' => $aUserHasField['ENTITY_ID'],
                                    '#ERROR#' => $ex->GetString(),
                                )));
                            }
                        }
                    } else {
                        if ($idUserTypeProp = $oUserTypeEntity->Add($aUserField)) {
                            $info[] = oGetMessage('USER_TYPE_ADDED', array(
                                '#FIELD_NAME#' => $aUserField['FIELD_NAME'],
                                '#ENTITY_ID#' => $aUserField['ENTITY_ID'],
                            ));
                        } else {
                            if (($ex = $APPLICATION->GetException())) {
                                throw new \Bitrix\Main\SystemException(oGetMessage('USER_TYPE_ADDED_ERROR', array(
                                    '#FIELD_NAME#' => $aUserField['FIELD_NAME'],
                                    '#ENTITY_ID#' => $aUserField['ENTITY_ID'],
                                    '#ERROR#' => $ex->GetString(),
                                )));
                            }
                        }
                    }

                    $obEnum = new CUserFieldEnum;

                    $valuesEnums = array();
                    foreach ($aUserField['VALUES'] as $arUserFieldEnum) {
                        $valuesEnums[] = $arUserFieldEnum + array('USER_FIELD_ID' => $idUserTypeProp);
                    }

                    $userTypeEnumsIterator = CUserFieldEnum::GetList(array('SORT' => 'ASC'), array('USER_FIELD_ID' => $idUserTypeProp));
                    if ($userTypeEnumsIterator->SelectedRowsCount()) {
                        $valuesEnumsNews = array();
                        foreach ($valuesEnums as $idValueEnum => $valueEnum) {
                            $userTypeEnumsHasIterator = CUserFieldEnum::GetList(array(), array('USER_FIELD_ID' => $idUserTypeProp, 'VALUE' => $valueEnum['VALUE']));
                            if ($arTypeEnumsHasIterator = $userTypeEnumsHasIterator->Fetch()) {
                                $valuesEnumsNews[$arTypeEnumsHasIterator['ID']] = $valueEnum;
                            } else {
                                $valuesEnumsNews['n' . $idValueEnum] = $valueEnum;
                            }
                        }
                    } else {
                        $valuesEnumsNews = array();
                        foreach ($valuesEnums as $idValueEnum => $valueEnum) {
                            $valuesEnumsNews['n' . $idValueEnum] = $valueEnum;
                        }
                    }

                    if (!$obEnum->SetEnumValues($idUserTypeProp, $valuesEnumsNews)) {
                        $strError = '';
                        if ($ex = $APPLICATION->GetException()) {
                            $strError = $ex->GetString();
                        }
                        throw new \Bitrix\Main\SystemException(oGetMessage('USER_TYPE_ENUMS_SET_ERROR', array(
                            '#FIELD_NAME#' => $aUserField['FIELD_NAME'],
                            '#ENTITY_ID#' => $aUserField['ENTITY_ID'],
                            '#ERROR#' => $strError,
                        )));
                    }
                }


            }
        }
    }


    function UnInstallDB()
    {

        Loader::includeModule($this->MODULE_ID);
        Loader::includeModule("iblock");


        $iBlockTypeId = $this->iBlockTypeId;

        $iBlockCode = $this->iBlockCode;


        $obIblocks = CIBlock::GetList(
            [],
            ["TYPE" => $iBlockTypeId]
        );
        $arIblocks = [];
        while ($arIblock = $obIblocks->Fetch()) {
            $arIblocks[$arIblock["CODE"]] = $arIblock;
            if ($arIblock["CODE"] == $iBlockCode) {
                $iBlockIdRss = $arIblock["ID"];
            }
        }
        unset($arIblocks[$iBlockCode]);
        if (empty($arIblocks)) {
            CIBlockType::Delete($iBlockTypeId);
        } else {
            if (isset($iBlockIdRss)) {
                CIBlock::Delete($iBlockIdRss);
            }
        }


    }


    function InstallEvents()
    {
    }


    function UnInstallEvents()
    {
    }

    public function GetPath($notDocumentRoot=false)
    {
        if($notDocumentRoot)
            return str_ireplase(Application::getDocumentRoot(),'',dirname(__DIR__));
        else
            return dirname(__DIR__);

    }


}
