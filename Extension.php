<?php

namespace Bolt\Extension\Animal\ImageCleanup;

use Bolt\BaseExtension;
use Bolt\Translation\Translator;

class Extension extends BaseExtension
{
    public function initialize()
    {
        if($this->app['config']->getWhichEnd() === 'backend') {
            $path = $this->app['config']->get('general/branding/path') . '/extensions/image-cleanup';
            $this->app->mount($path, new Controller\ImageCleanupAdminController());
            
            $this->app['extensions.Image cleanup for Bolt']->addMenuOption(Translator::__('Image Cleanup'), $this->app['resources']->getUrl('bolt') . 'extensions/image-cleanup', 'fa:pencil-square-o');
        }
    }

    /**
     * Set the defaults for configuration parameters.
     *
     * @return array
     */
    protected function getDefaultConfig()
    {
        return array(
        );
    }

    public function getName()
    {
        return 'Image cleanup for Bolt';
    }
}
