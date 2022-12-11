<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arThemes = array();

$arThemesMessages = array(
	"primary" => GetMessage("F_THEME_SITE"),
	"secondary" => GetMessage("F_THEME_BLUE"),
	"warning" => GetMessage("F_THEME_YELLOW"),
	"success" => GetMessage("F_THEME_GREEN"),
	"dark" => 'dark',
	"info" => GetMessage("F_THEME_LIGHT"),
	"danger" => GetMessage("F_THEME_RED")
);


$arTemplateParameters = array(
	"MENU_THEME"=>array(
		"NAME" => GetMessage("MENU_THEME"),
		"TYPE" => "LIST",
		"VALUES" => $arThemesMessages,
		"PARENT" => "BASE",
		"DEFAULT" => "primary"
	)
);
?>
