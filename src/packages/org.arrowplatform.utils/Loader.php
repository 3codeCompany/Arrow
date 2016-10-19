<?php
namespace Arrow\Package\Utils;

class Loader
{

    public final static function registerAutoload()
    {
        {
            $classes = array(
                'arrow\\package\\utils\\dictionary' => '/utils/dictionary/Dictionary.php',
                'arrow\\package\\utils\\dictionaryctrl' => '/utils/dictionary/dictionaryCtrl/DictionaryCtrl.php',
                'arrow\\package\\utils\\dictionaryvalidator' => '/utils/dictionary/DictionaryValidator.php',
                'arrow\\package\\utils\\developer' => '/utils/Developer.php',
                'arrow\\package\\utils\\itrackable' => '/utils/track/ITrackable.php',
                'arrow\\package\\utils\\stringhelper' => '/utils/helpers/StringHelper.php',
                'arrow\\package\\utils\\track' => '/utils/track/Track.php',
                'arrow\\package\\utils\\utilsdictionary' => '/utils/dictionary/UtilsDictionary.php',
                'arrow\\package\\utils\\console' => '/utils/developer/Console.php',
                'bithelper' => '/utils/helpers/BitHelper.php',
                'imagetransform' => '/utils/images/ImageTransform.php',
                'validatehelper' => '/utils/helpers/ValidateHelper.php',
                'arrow\\package\\utils\\utilscontroller' => '/../controllers/UtilsController.php',
                'arrow\\package\\utils\\developercontroller' => '/../controllers/DeveloperController.php',
            );
            \Arrow\Models\Loader::registerClasses(__DIR__."/models", $classes);

        }


    }

}