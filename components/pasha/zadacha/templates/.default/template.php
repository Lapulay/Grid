<?php
if (!defined("B_PROLOG_INCLUDED")|| B_PROLOG_INCLUDED!== true)die();
use \Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\PageNavigation;

CModule::IncludeModule("iblock");
$iListId = 'Tablelist';
$obGridOptions = new GridOptions($iListId);
$sort = $obGridOptions->GetSorting(['sort' => ['DATE_CREATE' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);
$arNavParams = $obGridOptions->GetNavParams();

$obNav = new PageNavigation($iListId);
$obNav->allowAllRecords(true)
    ->setPageSize($arNavParams['nPageSize'])
    ->initFromUri();
if ($obNav->allRecordsShown()) {
    $arNavParams = false;
} else {
    $arNavParams['iNumPage'] = $obNav->getCurrentPage();
}
$arFilter['IBLOCK_ID'] = 1;
$arFilter = [
    ['id' => 'ID', 'name' => 'Номер задачи',  'default' => true],
    ['id' => 'NAME', 'name' => 'Название', 'type'=>'text', 'default' => true],
    ['id' => 'PROPERTY_SROK', 'name' => 'Крайний срок', 'type'=>'text', 'default' => true],
    ['id' => 'PROPERTY_STATUS_VALUE', 'name' => 'Статус', 'type'=>'list', 'items'=>['Новая'=>'Новая',
        'Выполнена'=>'Выполнена', 'Завершена'=>'Завершена', 'Отменена'=>'Отменена', 'Отклонена'=>'Отклонена' ,
        'Выполняется'=>'Выполняется',],'default' => true],
];
?>

<div>
    <?$APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
        'FILTER_ID' => $iListId,
        'GRID_ID' => $iListId,
        'FILTER' => $arFilter,
        'ENABLE_LIVE_SEARCH' => true,
        'ENABLE_LABEL' => true
    ]);?>
</div>
<div style="clear: both;"></div>

<?php
$obFilterOption = new Bitrix\Main\UI\Filter\Options($iListId);
$arFilterData = $obFilterOption->getFilter([]);

foreach ($arFilterData as $k => $v) {
    $filterData['NAME'] = "%".$arFilterData['FIND']."%";
}

$arFilterData['IBLOCK_ID'] = 1;
$arFilterData['ACTIVE'] = "Y";
$arColumns = [];
$arColumns[] = ['id' => 'ID', 'name' => 'Номер задачи', 'sort' => 'ID', 'default' => true];
$arColumns[] = ['id' => 'NAME', 'name' => 'Название', 'sort' => 'NAME', 'default' => true];
$arColumns[] = ['id' => 'OPISANIE', 'name' => 'Описание', 'sort' => 'OPISANIE', 'default' => true];
$arColumns[] = ['id' => 'SROK', 'name' => 'Крайний срок', 'sort' => 'SROK', 'default' => true];
$arColumns[] = ['id' => 'STATUS', 'name' => 'Статус', 'sort' => 'STATUS', 'default' => true];

$obRes = \CIBlockElement::GetList($sort['sort'], $arFilterData, false, $arNavParams,
    ["ID", "IBLOCK_ID", "NAME",  "PROPERTY_OPISANIE", "PROPERTY_SROK", "PROPERTY_STATUS"]
);
$obNav->setRecordCount($obRes->selectedRowsCount());
while($arRow = $obRes->GetNext()) {
    $arList[] = [
        'data' => [
            "ID" => $arRow['ID'],
            "NAME" => $arRow['NAME'],
            "OPISANIE" => $arRow['PROPERTY_OPISANIE_VALUE'],
            "SROK" => $arRow['PROPERTY_SROK_VALUE'],
            "STATUS" => $arRow['PROPERTY_STATUS_VALUE'],
        ],
        'actions' => [
            [
                'text'    => 'Добавить',
                'default' => true,
                'onclick' => 'document.location.href="?iblock_Add=Y"'
            ], [
                'text'    => 'Изменить статус',
                'default' => true,
                'onclick' => 'document.location.href="?iblock_status=Y"'
            ],
            [
                'text'    => 'Удалить',
                'default' => true,
                'onclick' => 'if(confirm("Точно?")){document.location.href="?ID='.$arRow['ID'].'"}'
            ],
        ]
    ];
}

$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
    'GRID_ID' => $iListId,
    'COLUMNS' => $arColumns,
    'ROWS' => $arList,
    'SHOW_ROW_CHECKBOXES' => false,
    'NAV_OBJECT' => $obNav,
    'AJAX_MODE' => 'Y',
    'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
    'PAGE_SIZES' =>  [
        ['NAME' => '5', 'VALUE' => '5'],
        ['NAME' => '10', 'VALUE' => '10'],
        ['NAME' => '15', 'VALUE' => '15']
    ],
    'AJAX_OPTION_JUMP'          => 'N',
    'SHOW_CHECK_ALL_CHECKBOXES' => false,
    'SHOW_ROW_ACTIONS_MENU'     => true,
    'SHOW_GRID_SETTINGS_MENU'   => true,
    'SHOW_NAVIGATION_PANEL'     => true,
    'SHOW_PAGINATION'           => true,
    'SHOW_SELECTED_COUNTER'     => true,
    'SHOW_TOTAL_COUNTER'        => true,
    'SHOW_PAGESIZE'             => true,
    'SHOW_ACTION_PANEL'         => true,
    'ALLOW_COLUMNS_SORT'        => true,
    'ALLOW_COLUMNS_RESIZE'      => true,
    'ALLOW_HORIZONTAL_SCROLL'   => true,
    'ALLOW_SORT'                => true,
    'ALLOW_PIN_HEADER'          => true,
    'AJAX_OPTION_HISTORY'       => 'N'
]);
?>
