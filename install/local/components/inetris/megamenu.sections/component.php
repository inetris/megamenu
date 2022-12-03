<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Data\TaggedCache;
use Bitrix\Main\Entity;
use Bitrix\Main\Application;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Query\Join;

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 36000000;

$arParams["ID"] = intval(($arParams["ID"] ?? 0));
$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);

$arParams["DEPTH_LEVEL"] = intval($arParams["DEPTH_LEVEL"]);
if($arParams["DEPTH_LEVEL"]<=0)
	$arParams["DEPTH_LEVEL"]=1;

$arResult["SECTIONS"] = array();
$arResult["ELEMENT_LINKS"] = array();
$aMenuLinksExt = [];
if($this->StartResultCache())
{
	if(!CModule::IncludeModule("iblock"))
	{
		$this->AbortResultCache();
	}
	else
	{
		$arFilter = array(
			"IBLOCK_ID"=>$arParams["IBLOCK_ID"],
			"GLOBAL_ACTIVE"=>"Y",
			"IBLOCK_ACTIVE"=>"Y",
			"<="."DEPTH_LEVEL" => $arParams["DEPTH_LEVEL"],
		);
		$arOrder = array(
			"left_margin"=>"asc",
		);

		$iblocks = IblockTable::getList(array(
			'select' => array('SECTION_PAGE_URL', 'CODE'),
			'filter' => array('=ID' => $arParams["IBLOCK_ID"]),
			'limit' => 1
		));

		if ($iblock = $iblocks->fetch()) {

			$connection = Application::getConnection();

			$entityTableName = sprintf('b_uts_iblock_%s_section', $arParams["IBLOCK_ID"]);

			$entityTableExists = $connection->isTableExists($entityTableName);

			if ($entityTableExists) {
				
			}

			$entityEnum = Entity\Base::compileEntity('megamenu1',
				[
					'ID' => ['data_type' => 'integer'],
					'XML_ID' => ['data_type' => 'string'],
				],
				['table_name' => 'b_user_field_enum']
			);


			$sectionEntity = (\Bitrix\Iblock\Model\Section::compileEntityByIblock($arParams["IBLOCK_ID"]))::getEntity();


			$sectionEntity->addField(
				(
				new Reference(
					'MEGAMENU_SUBMENU_TYPE',
					$entityEnum,
					Join::on('this.UF_MEGAMENU_SUBMENU_TYPE', 'ref.ID')
				)
				)->configureJoinType(Join::TYPE_LEFT)
			);

			$sectionEntity->addField(
				(
				new Reference(
					'MEGAMENU_LINK_TYPE',
					$entityEnum,
					Join::on('this.UF_MEGAMENU_LINK_TYPE', 'ref.ID')
				)
				)->configureJoinType(Join::TYPE_LEFT)
			);


			$query = (new Query($sectionEntity))
				->setSelect([
					'CODE',
					'NAME',
					'DESCRIPTION',
					'ID',
					'DEPTH_LEVEL',
					'IBLOCK_SECTION_ID',
					'UF_*',
					'MEGAMENU_SUBMENU_TYPE_' => 'MEGAMENU_SUBMENU_TYPE',
					'MEGAMENU_LINK_TYPE_' => 'MEGAMENU_LINK_TYPE',
					'XML_ID'
				])
				->setOrder([
					'LEFT_MARGIN' => 'ASC',
				])
				// ->setLimit(15)
				->setFilter([
					'=IBLOCK_ID' => $arParams["IBLOCK_ID"],
					'<=DEPTH_LEVEL' => 4,
					'=ACTIVE' => 'Y',
					'=GLOBAL_ACTIVE' => 'Y',
				]);

			$result = $query->exec();



			$sections = [];

			while ($section = $result->fetch()) {	//TODO optimize: merge with foreach
				$sections[$section['ID']] = $section;

//				print_r('<pre>');
//				print_r($section);
//				 print_r('</pre>');
			}

			unset($section);

			foreach ($sections as $section) {

				$sectionCodes = [
					$section['CODE']
				];

				$parentId = $section['IBLOCK_SECTION_ID'];

				while (isset($parentId)) {

					if (isset($sections[$parentId])) {
						$sectionCodes[] = $sections[$parentId]['CODE'];

						$parentId = $sections[$parentId]['IBLOCK_SECTION_ID'];
					} else {
						$parentId = null;
					}

				}

				$arResult["SECTIONS"][] = array(
						'ID' => $section['ID'],
						'~NAME' => $section['NAME'],
						'DEPTH_LEVEL' => $section['DEPTH_LEVEL'],
						'CODE' => $section['CODE'],
						'IBLOCK_SECTION_ID' => $section['IBLOCK_SECTION_ID'],
						'MEGAMENU_DESCRIPTION' => $section['DESCRIPTION'],

						'MEGAMENU_SUBMENU_TYPE' => $section['MEGAMENU_SUBMENU_TYPE_XML_ID'],
						'MEGAMENU_LINK_TYPE' => $section['MEGAMENU_LINK_TYPE_XML_ID'],
						'MEGAMENU_ITEM_CLASS' => $section['UF_MEGAMENU_ITEM_CLASS'],
						'MEGAMENU_LINK_CLASS' => $section['UF_MEGAMENU_LINK_CLASS'],

						'~SECTION_PAGE_URL' => str_replace(
							array(
								'#SITE_DIR#',
								'#IBLOCK_CODE#',
								'#SECTION_CODE#',
							),
							array(
								SITE_DIR,
								$iblock['CODE'],
								//implode('/', array_reverse($sectionCodes))
								$section['CODE']
							),
							$iblock['SECTION_PAGE_URL']
						),
				);
			}
		}
		$this->EndResultCache();
	}
}

//In "SEF" mode we'll try to parse URL and get ELEMENT_ID from it
if($arParams["IS_SEF"] === "Y")
{
	$engine = new CComponentEngine($this);
	if (CModule::IncludeModule('iblock'))
	{
		$engine->addGreedyPart("#SECTION_CODE_PATH#");
		$engine->setResolveCallback(array("CIBlockFindTools", "resolveComponentEngine"));
	}
	$componentPage = $engine->guessComponentPath(
		$arParams["SEF_BASE_URL"],
		array(
			"section" => $arParams["SECTION_PAGE_URL"],
			"detail" => $arParams["DETAIL_PAGE_URL"],
		),
		$arVariables
	);
	if($componentPage === "detail")
	{
		CComponentEngine::InitComponentVariables(
			$componentPage,
			array("SECTION_ID", "ELEMENT_ID"),
			array(
				"section" => array("SECTION_ID" => "SECTION_ID"),
				"detail" => array("SECTION_ID" => "SECTION_ID", "ELEMENT_ID" => "ELEMENT_ID"),
			),
			$arVariables
		);
		$arParams["ID"] = intval($arVariables["ELEMENT_ID"]);
	}
}

if(($arParams["ID"] > 0) && (intval($arVariables["SECTION_ID"]) <= 0) && CModule::IncludeModule("iblock"))
{
	$arSelect = array("ID", "IBLOCK_ID", "DETAIL_PAGE_URL", "IBLOCK_SECTION_ID");
	$arFilter = array(
		"ID" => $arParams["ID"],
		"ACTIVE" => "Y",
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
	);
	$rsElements = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
	if(($arParams["IS_SEF"] === "Y") && ($arParams["DETAIL_PAGE_URL"] <> ''))
		$rsElements->SetUrlTemplates($arParams["SEF_BASE_URL"].$arParams["DETAIL_PAGE_URL"]);
	while($arElement = $rsElements->GetNext())
	{
		$arResult["ELEMENT_LINKS"][$arElement["IBLOCK_SECTION_ID"]][] = $arElement["~DETAIL_PAGE_URL"];
	}
}

$aMenuLinksNew = array();
$menuIndex = 0;
$previousDepthLevel = 1;
foreach($arResult["SECTIONS"] as $arSection)
{
	if ($menuIndex > 0)
		$aMenuLinksNew[$menuIndex - 1][3]["IS_PARENT"] = $arSection["DEPTH_LEVEL"] > $previousDepthLevel;
	$previousDepthLevel = $arSection["DEPTH_LEVEL"];

	$arResult["ELEMENT_LINKS"][$arSection["ID"]][] = urldecode($arSection["~SECTION_PAGE_URL"]);
	$aMenuLinksNew[$menuIndex++] = array(
		htmlspecialcharsbx($arSection["~NAME"]),
		$arSection["~SECTION_PAGE_URL"],
		$arResult["ELEMENT_LINKS"][$arSection["ID"]],
		array(
			"FROM_IBLOCK" => true,
			"IS_PARENT" => false,
			"DEPTH_LEVEL" => $arSection["DEPTH_LEVEL"],
			"MEGAMENU_LINK_TYPE" => $arSection["MEGAMENU_LINK_TYPE"],
			"MEGAMENU_SUBMENU_TYPE" => $arSection["MEGAMENU_SUBMENU_TYPE"],
			"MEGAMENU_LINK_CLASS" => $arSection["MEGAMENU_LINK_CLASS"],
			"MEGAMENU_ITEM_CLASS" => $arSection["MEGAMENU_ITEM_CLASS"],
			"MEGAMENU_DESCRIPTION" => $arSection["MEGAMENU_DESCRIPTION"],
		),
	);
}

return $aMenuLinksNew;
?>
