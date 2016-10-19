<?php
namespace Arrow\Package\Langs;

class Loader
{

    public final static function registerAutoload()
    {
        {
            $classes = array(
                'arrow\\package\\langs\\lang' => '/lang/Lang.php',
                'arrow\\package\\langs\\langdata' => '/lang/LangData.php',
                'arrow\\package\\langs\\langvirtualdata' => '/lang/LangVirtualData.php',
                'arrow\\package\\langs\\translation' => '/lang/Translation.php'
            );
            \Arrow\Models\Loader::registerClasses(__DIR__."/models", $classes);

        }


    }

}