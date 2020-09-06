<?php

namespace DynamicRouter;

use Illuminate\Support\Facades\Route;
use Facade\Ignition\Support\ComposerClassMap;


class Router
{

    static $controller_ending = 'Controller';

    // php artisan optimize
    // php artisan optimize:clear
    
    static function route(){
        if(app()->runningInConsole()){
            if(in_array('route:cache',$_SERVER['argv']) || in_array('optimize',$_SERVER['argv']) ){
                $classmap = (new ComposerClassMap)->listClasses();
                self::getGroup($group_prefix,$group_namespace);

                if(empty($group_namespace)){
                    $preg_group_namespace = '';
                }else{
                    $preg_group_namespace = str_replace('\\','\\\\',$group_namespace).'\\\\';
                }

                if(empty($group_prefix)){
                    $preg_group_prefix = '';
                }else{
                    $preg_group_prefix = str_replace('/','\\\\',$group_prefix).'\\\\';
                }

                $preg1 = '/^'.$preg_group_namespace.$preg_group_prefix.'/';
                $preg2 = '/[\w]+'.self::$controller_ending.'$/';

                //összes Route beállítása
                foreach($classmap as $class => $file){
                    if(preg_match($preg1,$class) && preg_match($preg2,$class)){
                        echo " - ".$class."\n";
                        // $out[] = $class;
                        self::_routeformat($class,$group_prefix,$group_namespace);
                    }
                }
            }
        }else{
            //aktuális route beállítása Autoloader alapján
            self::getGroup($group_prefix,$group_namespace);
            $class = (empty($group_namespace) ? '' : $group_namespace . '\\') . self::getCtFromRequest();
            self::_routeformat($class,$group_prefix,$group_namespace);
        }
    }
    
    static function getGroup(&$group_prefix,&$group_namespace){
        $groupStacks =  Route::getGroupStack();
        $lastGroupStacks = end($groupStacks);
        $group_namespace = $lastGroupStacks['namespace'];
        $group_prefix = $lastGroupStacks['prefix'];
    }
    
    static function getCtFromRequest(){
        $ct = preg_replace(['/^\//'],'',app('request')->getPathInfo());
        $tmpct = explode('/',$ct);
        $lastCtname = ucfirst(end($tmpct));
        $tmpct[key($tmpct)] = $lastCtname;
        $ct = implode('\\',$tmpct) . self::$controller_ending;
        return $ct;
    }
    
    static function cb($string,$tocut){
        return self::ca(substr($string,strlen($tocut)));
    }
    
    static function ca($string){
        return preg_replace(['/^\//','/^\\\\/'],'',$string);
    }
    
    static function _routeformat($class,$group_prefix,$group_namespace){
        $uri = self::cb($class,$group_namespace);
        $uri = self::cb($uri,$group_prefix);
        $uri = preg_replace('/'.self::$controller_ending.'$/','',$uri);
        $uri = strtolower($uri);
        $uri = str_replace('\\','/',$uri);
        $ct = self::cb($class,$group_namespace);
        $class = '\\'.$class;

        //file betöltése, ha autoladerben nem létezik
        if(!class_exists($class)){
            $fullpath = realpath(app()->basePath().'/'.$class.'.php');
            if($fullpath) require_once($fullpath);
        }
        // dd($uri,$class,$ct, class_exists($class) ,method_exists($class,'list'));
        self::_route($class,$uri,$ct);
    }

    static function _route($class,$uri,$ct){
        
        if(method_exists($class,'list'))Route::get($uri,$ct.'@list');
        if(method_exists($class,'view'))Route::get($uri.'/{id}',$ct.'@view');
        if(method_exists($class,'create'))Route::post($uri,$ct.'@create');
        if(method_exists($class,'update'))Route::put($uri.'/{id}',$ct.'@update');
        if(method_exists($class,'delete'))Route::delete($uri.'/{id}',$ct.'@delete');
    }
}
