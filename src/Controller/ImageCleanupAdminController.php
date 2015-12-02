<?php

namespace Bolt\Extension\Animal\ImageCleanup\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;

class ImageCleanupAdminController implements ControllerProviderInterface
{
    /**
     * @var \Silex\Application
     */
    private $app;

    /**
     * @var array
     */
    private $config;

    /**
     * @param \Silex\Application $app
     *
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $this->config = $app['extensions.Image cleanup for Bolt']->config;

        /*
         * @var \Silex\ControllerCollection
         */
        $ctr = $app['controllers_factory'];

        // Admin page
        $ctr->match('/', array($this, 'admin'))
            ->bind('ImageCleanup')
            ->method('GET');

        return $ctr;
    }

    /**
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @return \Twig_Markup
     */
    public function admin(Application $app, Request $request)
    {
        $app['twig.loader.filesystem']->addPath(dirname(dirname(__DIR__)).'/assets');

        $images = array(
            'contenttypes' => array(),
            'filesystem' => array(),
        );

        $basefolder = str_replace($_SERVER['DOCUMENT_ROOT'], '', $app['resources']->getPath('files')).'/';
        $contentTypes = $app['config']->get('contenttypes');
        foreach ($contentTypes as $name => $contentType) {
            foreach ($contentType['fields'] as $key => $field) {
                if ($field['type'] === 'image' || $field['type'] === 'imagelist' || $field['type'] === 'html') {
                    $contentTypeContent = $app['storage']->getContent($name, array());
                    foreach ($contentTypeContent as $content) {
                        switch ($field['type']) {
                            case 'imagelist':
                                foreach ($content[$key] as $imagelistContent) {
                                    $images['contenttypes'][] = $imagelistContent['filename'];
                                }
                                break;
                            case 'html':
                                preg_match_all('/src="([^"]+)"/', $content[$key], $htmlImages, PREG_PATTERN_ORDER);
                                $htmlImages = $htmlImages[1];
                                
                                foreach($htmlImages as &$htmlImage) {
                                    $htmlImage = str_replace($basefolder, '', $htmlImage);
                                }

                                $images['contenttypes'] = array_merge($images['contenttypes'], $htmlImages);
                                break;
                            default:
                                $images['contenttypes'][] = $content[$key];
                        }
                    }
                }
            }
        }

        $finder = new Finder();
        $finder
            ->files()
            ->in($app['resources']->getPath('files'))
            ->name('/\.(?:jpe?g|png|gif|tiff)$/i');

        foreach ($finder as $file) {
            $images['filesystem'][] = $file->getRelativePathname();
        }

        $images['contenttypes'] = array_unique($images['contenttypes']);
        $images['unused'] = array_diff($images['filesystem'], $images['contenttypes']);
        $images['missing'] = array_diff($images['contenttypes'], $images['filesystem']);
        $html = $app['render']->render('cleanimages.twig', array(
            'images' => $images,
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }
}
