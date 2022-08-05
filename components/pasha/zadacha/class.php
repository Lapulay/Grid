<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class CAddzadacha extends CBitrixComponent
{
    public $arEventFields = [];
    public $componentPage = "";

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
        $this->accessUser();
        $this->displayTemplate();
        $this->makeRequests();
        $this->includeComponentTemplate($this->componentPage);
    }
}
