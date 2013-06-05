<?php

function smarty_compiler_script($params,  $smarty){
    $strResourceApiPath = preg_replace('/[\\/\\\\]+/', '/', dirname(__FILE__) . '/FISResource.class.php');
    $strFramework = $params['framework'];
    $strCode  = '<?php ';
    if (isset($strFramework)) {
        $strCode .= 'if(!class_exists(\'FISResource\')){require_once(\'' . $strResourceApiPath . '\');}';
        $strCode .= 'FISResource::setFramework(FISResource::getUri('.$strFramework.', $_smarty_tpl->smarty));';
    }
    $strCode .= 'ob_start();';
    $strCode .= ' ?>';
    return $strCode;
}

function smarty_compiler_scriptclose($params,  $smarty){
    $strResourceApiPath = preg_replace('/[\\/\\\\]+/', '/', dirname(__FILE__) . '/FISResource.class.php');
    $strCode  = '<?php ';
    $strCode .= '$script=ob_get_clean();';
    $strCode .= 'if($script!==false){';
    $strCode .= 'if(!class_exists(\'FISResource\')){require_once(\'' . $strResourceApiPath . '\');}';
    $strCode .= 'FISResource::addScriptPool($script);';
    $strCode .= '}?>';
    return $strCode;
}