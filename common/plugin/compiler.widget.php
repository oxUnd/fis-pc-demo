<?php
class fis_widget_map {
    
    private static $arrCached = array();
    
    public static function lookup(&$strFilename, &$smarty){
        $strPath = self::$arrCached[$strFilename];
        if(isset($strPath)){
            return $strPath;
        } else {
            $arrConfigDir = $smarty->getConfigDir();
            foreach ($arrConfigDir as $strDir) {
                $strPath = preg_replace('/[\\/\\\\]+/', '/', $strDir . '/' . $strFilename);
                if(is_file($strPath)){
                    self::$arrCached[$strFilename] = $strPath;
                    return $strPath;
                }
            }
        }
        trigger_error('missing map file "' . $strFilename . '"', E_USER_ERROR);
    }
}

function smarty_compiler_widget($arrParams,  $smarty){
    $strResourceApiPath = preg_replace('/[\\/\\\\]+/', '/', dirname(__FILE__) . '/FISResource.class.php');
    $strCode = '<?php if(!class_exists(\'FISResource\')){require_once(\'' . $strResourceApiPath . '\');}';
    $strCall = $arrParams['call'];
    $bHasCall = isset($strCall);
    $strName = $arrParams['name'];
    unset($arrParams['name']);
    if($bHasCall){
        unset($arrParams['call']);
        $arrFuncParams = array();
        foreach ($arrParams as $_key => $_value) {
            if (is_int($_key)) {
                $arrFuncParams[] = "$_key=>$_value";
            } else {
                $arrFuncParams[] = "'$_key'=>$_value";
            }
        }
        $strFuncParams = 'array(' . implode(',', $arrFuncParams) . ')';
        $strTplFuncName = '\'smarty_template_function_\'.' . $strCall;
        $strCallTplFunc = 'call_user_func('. $strTplFuncName . ',$_smarty_tpl,' . $strFuncParams . ');';
        
        $strCode .= 'if(is_callable('. $strTplFuncName . ')){';
        $strCode .= $strCallTplFunc;
        $strCode .= '}else{';
    }
    if($strName){
        $name = trim($strName, '\'" ');
        if (!preg_match('/\.tpl$/', $name)) {
            //widget短路径
            $intPos = strrpos($name, ':');
            if ($intPos === false) {
                trigger_error('widget call must given namespace, in file "' . $smarty->_current_file . '"', E_USER_ERROR);
            }
            $namespace = substr($name, 0, $intPos);
            $prefix = substr($name, $intPos + 1);
            $intPos = strrpos($prefix, '/');
            if (false !== $intPos) {
                $widgetName = substr($prefix, $intPos + 1);
            } else {
                $widgetName = $prefix;
            }
            if (!$widgetName) {
                trigger_error('undefined widget name in file "' . $smarty->_current_file . '"', E_USER_ERROR);
            }

            $strName = "\"$namespace:widget/$namespace/$prefix/$widgetName.tpl\"";
        }
        $strCode .= '$_tpl_path=FISResource::load(' . $strName . ',$_smarty_tpl->smarty);';
        $strCode .= 'if(isset($_tpl_path)){';
        if($bHasCall){
            $strCode .= '$_smarty_tpl->smarty->fetch($_tpl_path);';
            $strCode .= 'if(is_callable('. $strTplFuncName . ')){';
            $strCode .= $strCallTplFunc;
            $strCode .= '}else{';
            $strCode .= 'trigger_error(\'missing function define "\'.' . $strTplFuncName . '.\'" in tpl "\'.$_tpl_path.\'"\', E_USER_ERROR);';
            $strCode .= '}';
        } else {
            //保存初始值
            $strCode .= '$tpl_vars = $_smarty_tpl->tpl_vars;';
            foreach ($arrParams as $_key => $_value) {
                $strCode .= '$_smarty_tpl->tpl_vars["' . $_key . '"] = ' . $_value . ';';
            }
            $strCode .= 'echo $_smarty_tpl->smarty->fetch($_tpl_path);';
            //还原数据
            $strCode .= '$_smarty_tpl->tpl_vars = $tpl_vars;';
        }
        $strCode .= '}else{';
        $strCode .= 'trigger_error(\'unable to locale resource "\'.' . $strName . '.\'"\', E_USER_ERROR);';
        $strCode .= '}';
    } else {
        trigger_error('undefined widget name in file "' . $smarty->_current_file . '"', E_USER_ERROR);
    }
    if($bHasCall){
        $strCode .= '}';
    }
    $strCode .= '?>';
    return $strCode;
}