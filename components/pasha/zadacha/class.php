<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use \Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\PageNavigation;

class CAddzadacha extends CBitrixComponent
{
    public $arEventFields = [];
    public $componentPage = "";
    public $arNavParams = [];
    public $arFilterData;
    public $obSort = [];
    public $arFilter;
    public $iListId;

    function displayTemplate()
    {

        CModule::IncludeModule('iblock');
        $obResult = CIBlockElement::GetList([], ['IBLOCK_ID' => 1], false, false,
            ['ID', 'NAME', 'PROPERTY_OPISANIE', 'PROPERTY_SROK', 'PROPERTY_STATUS']);

        $arTasks = [];
        while ($arElement = $obResult->fetch()) {
            $arTasks[$arElement['ID']] = ['NAME' => $arElement['NAME'],
                'OPISANIE' => $arElement['PROPERTY_OPISANIE_VALUE'],
                'SROK' => $arElement['PROPERTY_SROK_VALUE'],
                'STATUS' => $arElement['PROPERTY_STATUS_VALUE']];
        }

        $this->arResult['TASKS'] = $arTasks;

        $arProperties = [];
        $obEnums = CIBlockPropertyEnum::GetList(array("DEF" => "DESC", "SORT" => "ASC"), array("IBLOCK_ID" => 1));
        while ($arEnum = $obEnums->GetNext()) {
            $arProperties[$arEnum['ID']] = ['VALUE' => $arEnum['VALUE'], 'CODE' => $arEnum['XML_ID']];
            [$arEnum['ID']] = $arEnum;
            [$arEnum['ID']] = $arEnum['VALUE'];
        }
        return $arProperties;
    }

    public function deleteElement($id)
    {
        CIBlockElement::Delete($id);
        return null;
    }

    function accessUser()
    {
        $arRoles = ['ACTIVE' => "", 'AVAILABLE_STATUS' => []];
        global $USER;
        $arGroups = $USER->GetUserGroupArray();
        $sGroupsSep = implode("|", $arGroups);
        $arCodeGroup = CGroup::GetList($by = "c_sort", $order = "asc", array("ID" => $sGroupsSep));

        while ($arEnum = $arCodeGroup->GetNext()) {
            $arTest[$arEnum['ID']] = $arEnum['STRING_ID'];
        }

        if (in_array("KURATOR", $arTest) || in_array("ADMIN", $arTest)) {
            $arRoles['ACTIVE'] = "KURATOR";
            $arRoles['AVAILABLE_STATUS'] = ["ZAVERSHENA", "OTMENENA"];
        }
        if (in_array("PODOPECHNIY", $arTest)) {
            $arRoles['ACTIVE'] = "PODOPECHNIY";
            $arRoles['AVAILABLE_STATUS'] = ["COMPLITED", "REJECTED", "PERFORMED"];
        }
        $this->arResult['ROLE'] = $arRoles;
        return null;
    }

    function createElement()
    {
        $obEl = new CIBlockElement;
        $arProp = array();
        $arProp['NAME'] = $_POST["NAME"];
        $arProp['OPISANIE'] = $_POST['OPISANIE'];
        $arProp['SROK'] = $_POST['SROK'];
        $arProp['STATUS'] = $_POST['STATUS'];
        $arLoadProductArray = array(
            'IBLOCK_ID' => 1,
            "NAME" => $_POST["NAME"],
            "ID" => $_POST["ID"],
            "PROPERTY_VALUES" => $arProp,

        );
        return $obEl->Add($arLoadProductArray);
    }

    function editingStatus($statusID, $elementId)
    {
        CModule::IncludeModule('iblock');
        $sPropertyCode = "STATUS";
        $iPropertyValue = $statusID;
        CIBlockElement::SetPropertyValuesEx($elementId, false, array($sPropertyCode =>  $iPropertyValue));
        return null;
    }

    function mailerByAdd()
    {
        $this->arEventFields = array(
            'IBLOCK_ID' => 1,
            "NAME" => ($_POST["NAME"]),
            "OPISANIE" => ($_POST["OPISANIE"]),
            "SROK" => ($_POST["SROK"]),
            "STATUS" => ($_POST["STATUS"]),
        );
        CEvent::Send('MAIL_NOTIFICATION', 's1', $this->arEventFields, 'N', '11', array());
        return null;
    }

    function mailerByModyfy()
    {
        $this->arEventFields = array(
            'IBLOCK_ID' => 1,
            'NAME' => ($_POST["TASK"]),
            "STATUS" => ($_POST["STATUS"]),
        );
        CEvent::Send('MAIL_NOTIFICATION', 's1', $this->arEventFields, 'N', '12', array());
        return null;
    }


    function tableBody()
    {
        $this->arFilterData['IBLOCK_ID'] = 1;
        $this->arFilterData['ACTIVE'] = "Y";
        $arColumns[] = [];
        $arColumns[] = ['id' => 'ID', 'name' => 'Номер задачи', 'sort' => 'ID', 'default' => true];
        $arColumns[] = ['id' => 'NAME', 'name' => 'Название', 'sort' => 'NAME', 'default' => true];
        $arColumns[] = ['id' => 'OPISANIE', 'name' => 'Описание', 'sort' => 'OPISANIE', 'default' => true];
        $arColumns[] = ['id' => 'SROK', 'name' => 'Крайний срок', 'sort' => 'SROK', 'default' => true];
        $arColumns[] = ['id' => 'STATUS', 'name' => 'Статус', 'sort' => 'STATUS', 'default' => true];
        $this->arResult['arColumns']= $arColumns;

        $obRes = \CIBlockElement::GetList($this->obSort['sort'], $this->arFilterData, false, $this->arNavParams,
            ["ID", "IBLOCK_ID", "NAME",  "PROPERTY_OPISANIE", "PROPERTY_SROK", "PROPERTY_STATUS"]
        );
        $this->arResult['obNav']->setRecordCount($obRes->selectedRowsCount());
        while($arRow = $obRes->GetNext()) {
            $this->arResult['arList'][] = [
                'data' => [
                    "ID" => $arRow['ID'],
                    "NAME" => $arRow['NAME'],
                    "OPISANIE" => $arRow['PROPERTY_OPISANIE_VALUE'],
                    "SROK" => $arRow['PROPERTY_SROK_VALUE'],
                    "STATUS" => $arRow['PROPERTY_STATUS_VALUE'],
                ],
                'actions' => [
                     [
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
    }

    function tableFilter()
    {
        CModule::IncludeModule("iblock");
        $this->arResult['iListId'] = 'Tablelist';
        $obGridOptions = new GridOptions( $this->arResult['iListId']);
        $this->obSort = $obGridOptions->GetSorting(['sort' => ['DATE_CREATE' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);
        $this->arNavParams = $obGridOptions->GetNavParams();

        $this->arResult['obNav'] = new PageNavigation( $this->arResult['iListId']);
        $this->arResult['obNav']->allowAllRecords(true)
            ->setPageSize($this->arNavParams['nPageSize'])
            ->initFromUri();
        if ( $this->arResult['obNav']->allRecordsShown()) {
            $this->arNavParams = false;
        } else {
            $this->arNavParams['iNumPage'] = $this->arResult['obNav']->getCurrentPage();
        }

        $this->arFilter['IBLOCK_ID'] = 1;
        $this->arResult['arFilter'] = [
            ['id' => 'ID', 'name' => 'Номер задачи',  'default' => true],
            ['id' => 'NAME', 'name' => 'Название', 'type'=>'text', 'default' => true],
            ['id' => 'PROPERTY_SROK', 'name' => 'Крайний срок', 'type'=>'text', 'default' => true],
            ['id' => 'PROPERTY_STATUS_VALUE', 'name' => 'Статус', 'type'=>'list', 'items'=>['Новая'=>'Новая',
                'Выполнена'=>'Выполнена', 'Завершена'=>'Завершена', 'Отменена'=>'Отменена', 'Отклонена'=>'Отклонена' ,
                'Выполняется'=>'Выполняется',],'default' => true],
        ];

        $obFilterOption = new Bitrix\Main\UI\Filter\Options($this->arResult['iListId']);
        $this->arFilterData = $obFilterOption->getFilter([]);
        foreach ($this->arFilterData as $k => $v) {
            $filterData['NAME'] = "%".$this->arFilterData['FIND']."%";
        }
    }

    function makeRequests()
    {
        $componentPage;

        if (!empty($_REQUEST["iblock_Add"])) {
            $this->componentPage = "template_test";
        }
        if (isset($_REQUEST['ID'])) {
            $this->deleteElement($_REQUEST['ID']);
        }
        if (!empty($_REQUEST["iblock_status"])) {
            $this->componentPage = "template_status";
        }
        if (isset($_POST['STATUS']) && $_POST['form_id'] == 'Add_status') {
            $this->editingStatus($_POST['STATUS'], $_POST['TASK']);
            $this->mailerByModyfy();
        }
        if ($_POST['form_id'] == 'Add_Item') {
            $this->createElement();
            $this->mailerByAdd();
        }
        return null;
    }

    function executeComponent()
    {
        $this->arResult['LIST_VALUES'] =  $this->displayTemplate();
        $this->tableFilter();
        $this->tableBody();
        $this->accessUser();
        $this->displayTemplate();
        $this->makeRequests();
        $this->includeComponentTemplate($this->componentPage);
    }
}
