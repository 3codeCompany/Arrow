<?php
namespace Arrow\Package\CMS;

class Loader
{
    public final static function registerAutoload()
    {
        {
            $classes = array(
                'arrow\\package\\cms\\administrationextensionpoint' => '/panel/AdministrationExtensionPoint.php',
                'arrow\\package\\cms\\cmsarticle' => '/articles/CMSArticle.php',
                'arrow\\package\\cms\\cmsarticlesconnections' => '/news/CMSArticlesConnections.php',
                'arrow\\package\\cms\\cmsbanner' => '/banners/CMSBanner.php',
                'arrow\\package\\cms\\gallery' => '/Gallery.php',
                'arrow\\package\\cms\\cmsgallery' => '/galleries/CMSGallery.php',
                'arrow\\package\\cms\\cmsgalleryphoto' => '/galleries/CMSGalleryPhoto.php',
                'arrow\\package\\cms\\news' => '/news/News.php',
                'arrow\\package\\cms\\cmsnewsvalidator' => '/news/CMSNewsValidator.php',
                'arrow\\package\\cms\\module' => '/pages/Module.php',
                'arrow\\package\\cms\\page' => '/pages/Page.php',
                'arrow\\package\\cms\\pageplace' => '/pages/PagePlace.php',
                'arrow\\package\\cms\\pageplaceconf' => '/pages/PagePlaceConf.php',
                'arrow\\package\\cms\\pageplaceschema' => '/pages/PagePlaceSchema.php',
                'arrow\\package\\cms\\pageschema' => '/pages/PageSchema.php',
                'arrow\\package\\cms\\controller' => '/../controllers/Controller.php',
            );
            \Arrow\Models\Loader::registerClasses(__DIR__ . "/models", $classes);

        }
    }

}