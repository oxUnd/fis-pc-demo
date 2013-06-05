<?php

class FISResource {
    
    const CSS_LINKS_HOOK = '<!--[FIS_CSS_LINKS_HOOK]-->';
    
    private static $arrMap = array();
    private static $arrLoaded = array();
    private static $arrStaticCollection = array();
    //收集require.async组件
    private static $requireAsyncCollection = array();
    private static $arrScriptPool = array();

    public static $framework = null;
    
    public static function reset(){
        self::$arrMap = array();
        self::$arrLoaded = array();
        self::$arrStaticCollection = array();
        self::$arrScriptPool = array();
        self::$framework  = null;
    }
    
    public static function cssHook(){
        return self::CSS_LINKS_HOOK;
    }
    
    public static function renderResponse($strContent){
        $intPos = strpos($strContent, self::CSS_LINKS_HOOK);
        if($intPos !== false){
            $strContent = substr_replace($strContent, self::render('css'), $intPos, strlen(self::CSS_LINKS_HOOK));
        }
        self::reset();
        return $strContent;
    }

    public static function setFramework($strFramework) {
        self::$framework = $strFramework;
    }

    public static function getUri($strName, $smarty) {
        $intPos = strpos($strName, ':');
        if($intPos === false){
            $strNamespace = '__global__';
        } else {
            $strNamespace = substr($strName, 0, $intPos);
        }
        if(isset(self::$arrMap[$strNamespace]) || self::register($strNamespace, $smarty)) {
            $arrMap = &self::$arrMap[$strNamespace];
            $arrRes = &$arrMap['res'][$strName];
            if (isset($arrRes)) {
                return $arrRes['uri'];
            }
        }
    }

    public static function getTemplate($strName, $smarty) {
        //绝对路径
        return $smarty->joined_template_dir . str_replace('/template', '', self::getUri($strName, $smarty));
    }

    public static function render($type){
        $html = '';
        if(!empty(self::$arrStaticCollection[$type])){
            $arrURIs = &self::$arrStaticCollection[$type];
            if($type === 'js'){
                //require.resourceMap要在mod.js加载以后执行
                if (self::$framework) {
                    $html .= '<script type="text/javascript" src="' . self::$framework . '"></script>' . PHP_EOL;
                    $resourceMap = self::getResourceMap();
                    if ($resourceMap) {
                        $html .= '<script type="text/javascript">';
                        $html .= 'require.resourceMap('.$resourceMap.');';
                        $html .= '</script>';
                    }
                }
                foreach ($arrURIs as $uri) {
                    if ($uri === self::$framework) {
                        continue;
                    }
                    $html .= '<script type="text/javascript" src="' . $uri . '"></script>' . PHP_EOL;
                }
            } else if($type === 'css'){
                $html = '<link rel="stylesheet" type="text/css" href="' . implode('"/><link rel="stylesheet" type="text/css" href="', $arrURIs) . '"/>';
            }
        }
        return $html;
    }
    
    public static function addScriptPool($str){
        self::$arrScriptPool[] = $str;
    }
    
    public static function renderScriptPool(){
        $html = '';
        if(!empty(self::$arrScriptPool)){
            $html = '<script type="text/javascript">(function(){' . implode("})();\n(function(){", self::$arrScriptPool) . '})();</script>';
        }
        return $html;
    }

    public static function getResourceMap() {
        $ret = '';
        if (self::$requireAsyncCollection) {
            $ret = str_replace('\\/', '/', json_encode(self::$requireAsyncCollection));
        }
       return  $ret;
    }

    public static function register($strNamespace, $smarty){
        if($strNamespace === '__global__'){
            $strMapName = 'map.json';
        } else {
            $strMapName = $strNamespace . '-map.json';
        }
        $arrConfigDir = $smarty->getConfigDir();
        foreach ($arrConfigDir as $strDir) {
            $strPath = preg_replace('/[\\/\\\\]+/', '/', $strDir . '/' . $strMapName);
            if(is_file($strPath)){
                self::$arrMap[$strNamespace] = json_decode(file_get_contents($strPath), true);
                return true;
            }
        }
        return false;
    }

    public static function loadAsync($strName, $smarty) {
        if (isset(self::$arrLoaded[$strName])) {
            return $strName;
        } else {
            $intPos = strpos($strName, ':');
            if($intPos === false){
                $strNamespace = '__global__';
            } else {
                $strNamespace = substr($strName, 0, $intPos);
            }
            if(isset(self::$arrMap[$strNamespace]) || self::register($strNamespace, $smarty)){
                $arrMap = &self::$arrMap[$strNamespace];
                $arrRes = &$arrMap['res'][$strName];
                if (isset($arrRes)) {
                    self::$arrLoaded[$strName] = $arrRes['uri'];
                    $deps = array();
                    if (isset($arrRes['deps'])) {
                        $deps = $arrRes['deps'];
                    }
                    foreach ($deps as $key => $uri) {
                        $arr = $arrMap['res'][$uri];
                        if (isset($arr)) {
                            //resourceMap不需要css
                            if ($arr['type'] === 'css') {
                                unset($deps[$key]);
                                //css 依赖
                                self::load($uri, $smarty);
                            }
                        } else {
                            unset($deps[$key]);
                        }
                    }
                    self::$requireAsyncCollection['res'][$strName] = array(
                        'url' => $arrRes['uri'],
                        'deps' => $deps
                    );
                    if (isset($arrRes['extras']) && isset($arrRes['extras']['async'])) {
                        $deps = array_merge($deps, $arrRes['extras']['async']);
                    }
                    foreach ($deps as $uri) {
                        self::loadAsync($uri, $smarty);
                    }
                }
            }
        }
    }

    public static function load($strName, $smarty){
        if(isset(self::$arrLoaded[$strName])) {
            return self::$arrLoaded[$strName];
        } else {
            $intPos = strpos($strName, ':');
            if($intPos === false){
                $strNamespace = '__global__';
            } else {
                $strNamespace = substr($strName, 0, $intPos);
            }
            if(isset(self::$arrMap[$strNamespace]) || self::register($strNamespace, $smarty)){
                $arrMap = &self::$arrMap[$strNamespace];
                $arrRes = &$arrMap['res'][$strName];
                if(isset($arrRes)) {
                    if(isset($arrRes['pkg'])){
                        $arrPkg = &$arrMap['pkg'][$arrRes['pkg']];
                        $strURI = $arrPkg['uri'];
                        foreach ($arrPkg['has'] as $strResId) {
                            //todo
                            self::$arrLoaded[$strName] = $strURI;
                        }
                    } else {
                        $strURI = $arrRes['uri'];
                        self::$arrLoaded[$strName] = $strURI;
                        //require.async
                        if (isset($arrRes['extras']) && isset($arrRes['extras']['async'])) {
                            foreach ($arrRes['extras']['async'] as $uri) {
                                self::loadAsync($uri, $smarty);
                            }
                        }

                        if(isset($arrRes['deps'])){
                            foreach ($arrRes['deps'] as $strDep) {
                                self::load($strDep, $smarty);
                            }
                        }
                    }
                    self::$arrStaticCollection[$arrRes['type']][] = $strURI;
                    return $strURI;
                } else {
                    trigger_error('undefined resource "' . $strName . '"', E_USER_NOTICE);
                }
            } else {
                trigger_error('missing map file of "' . $strNamespace . '"', E_USER_NOTICE);
            }
        }
        trigger_error('unknown resource load error', E_USER_NOTICE);
    }
}
