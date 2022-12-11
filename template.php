<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
Bitrix\Main\Loader::includeModule('inetris.megamenu');
use Inetris\Megamenu\Megamenu;?>

<?php ob_start(); ?>
    <div class="row">
        <div class="col-lg-12">
            <nav class="megamenu navbar navbar-expand-lg navbar-dark bg-<?=$arParams["MENU_THEME"]?>">
				<div class="container-fluid">
					<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-collapse-grid1"
							aria-controls="navbar-collapse-grid1" aria-expanded="false" aria-label="Toggle navigation">
						<span class="navbar-toggler-icon"></span>
					</button>
					<div class="navbar-collapse collapse" id="navbar-collapse-grid1">
						<ul class="navbar-nav mr-auto">
							<? if (!empty($arResult)): ?>
								<?= Megamenu::getItems($arResult); ?>
							<? endif ?>
						</ul>
					</div>
                </div>
            </nav>
        </div>
    </div>


<?php
$this->__component->arResult["CACHED_TPL"] = @ob_get_contents();
ob_get_clean();
?>