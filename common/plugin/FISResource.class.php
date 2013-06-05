<?php

class FISResource {
    
    const CSS_LINKS_HOOK = '<!--[FIS_CSS_LINKS_HOOK]-->';
    
    private static $arrMap = array();
    private static $arrLoaded = array();
    private static $arrStaticCollection = array();
    //收集require.async组件
    private static $requireAsyncCollection = array();
    private static $arrScriptPool = array();
    private static $libs = array(
        'tangram',
        'fis',
        'magic',
        'gmu',
        'wpo'
    );
    
    public static function reset(){
        self::$arrMap = array();
        self::$arrLoaded = array();
        self::$arrStaticCollection = array();
        self::$arrScriptPool = array();
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
                //return $smarty->joined_template_dir . $arrRes['uri'];
                return $smarty->joined_template_dir . substr($strName, $intPos + 1);
            }
        }
    }

    public static function render($type){
        $html = '';
        if(!empty(self::$arrStaticCollection[$type])){
            $arrURIs = &self::$arrStaticCollection[$type];
            if($type === 'js'){
                foreach ($arrURIs as $uri) {
                    //require.resourceMap要在mod.js加载以后执行
                    if (preg_match('/\/mod\.js$/i', $uri)) {
                        $html .= '<script type="text/javascript" src="' . $uri . '"></script>' . PHP_EOL;
                        $resourceMap = self::getResourceMap();
                        $html .= '<script type="text/javascript">' . self::getAliasFunc() . '</script>';
                        if ($resourceMap) {
                            $html .= '<script type="text/javascript">';
                            $html .= 'require.resourceMap('.$resourceMap.');';
                            $html .= '</script>';
                        }
                    } else {
                        $html .= '<script type="text/javascript" src="' . $uri . '"></script>' . PHP_EOL;
                    }
                }
                //$html = '<script type="text/javascript" src="' . implode('"></script><script type="text/javascript" src="', $arrURIs) . '"></script>';
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

    public static function getAliasFunc() {
        return 'require.alias = function(id) {
            var libs = "'.implode(',', self::$libs).'";
            if (!/\.js$/.test(id)) {
                id = id.replace(/([^:/]+):((?:[^/]+)|(?:[\s\S]+)?\/([^/]+))$/gi,
                    function(m, namespace, prefix, compName) {
                        if (!compName) {
                            compName = prefix;
                        }
                        if (libs.indexOf(namespace) !== -1) {
                            m = "common:static/common/lib/" + namespace + "/" + prefix + "/" + compName + ".js";
                        } else {
                            m = namespace + ":static/" + namespace + "/ui/" + prefix + "/" + compName + ".js";
                        }
                        return m;
                    }
                );
            }
            return id;
        }';
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

    //short path support
    public static function getShortPath($strName, $type) {
        if (!preg_match('/\.(?:css|js|tpl|html)$/i', $strName)) {
            $intPos = strpos($strName, ':');
            $namespace = substr($strName, 0, $intPos);
            $prefix = substr($strName, $intPos + 1);
            $intPos = strrpos($prefix, '/');
            if (false !== $intPos) {
                $compName = substr($prefix, $intPos + 1);
            } else {
                $compName = $prefix;
            }
            if (in_array($namespace, self::$libs)) {
                $strName = "common:static/common/lib/$namespace/$prefix/$compName.$type";
            } else {
                $strName = "$namespace:static/$namespace/ui/$prefix/$compName.$type";
            }
        }
        return $strName;
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
                    self::$arrLoaded[$strName] = true;
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
                                self::$arrStaticCollection[$arr['type']][] = $arr['uri'];
                                self::$arrLoaded[$uri] = true;
                            }
                        } else {
                            unset($deps[$key]);
                        }
                    }
                    if (isset($arrRes['extras']) && isset($arrRes['extras']['async'])) {
                        $deps = array_merge($deps, $arrRes['extras']['async']);
                    }
                    self::$requireAsyncCollection['res'][$strName] = array(
                        'url' => $arrRes['uri'],
                        'deps' => $deps
                    );
                    foreach ($deps as $uri) {
                        self::loadAsync(self::getShortPath($uri, 'js'), $arrMap);
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
                        //require.async
                        if (isset($arrRes['extras']) && isset($arrRes['extras']['async'])) {
                            foreach ($arrRes['extras']['async'] as $uri) {
                                self::loadAsync(self::getShortPath($uri, 'js'), $smarty);
                            }
                        }

                        if(isset($arrRes['deps'])){
                            foreach ($arrRes['deps'] as $strDep) {
                                self::load(self::getShortPath($strDep, $arrRes['type']), $smarty);
                            }
                        }
                        $strURI = $arrRes['uri'];
                        self::$arrLoaded[$strName] = $strURI;
                    }
                    self::$arrStaticCollection[$arrRes['type']][] = $strURI;
                    return $strURI;
                } else {
                    trigger_error('undefined resource "' . $strName . '"', E_USER_ERROR);
                }
            } else {
                trigger_error('missing map file of "' . $strNamespace . '"', E_USER_ERROR);
            }
        }
        trigger_error('unknown resource load error', E_USER_ERROR);
    }
}
