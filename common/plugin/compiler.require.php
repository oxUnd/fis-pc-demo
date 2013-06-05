<?php

function smarty_compiler_require($arrParams,  $smarty){
    $strName = $arrParams['name'];
    $strAsync = $arrParams['async'];
    $async = false;
    if (isset($strAsync)) {
        $async = trim($strAsync, '"\' ');
    }
    $strCode = '';
    if($strName){
        $strResourceApiPath = preg_replace('/[\\/\\\\]+/', '/', dirname(__FILE__) . '/FISResource.class.php');
        $strCode .= '<?php if(!class_exists(\'FISResource\')){require_once(\'' . $strResourceApiPath . '\');}';
        if ($async) {
            $strCode .= 'FISResource::loadAsync(' . $strName . ', $_smarty_tpl->smarty);';
        } else {
            $strCode .= 'FISResource::load(' . $strName . ',$_smarty_tpl->smarty);';
        }
        $strCode .= '?>';
    }
    return $strCode;
}