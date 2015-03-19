<?php

namespace YOOtheme\Widgetkit\Joomla\Zl;

use YOOtheme\Widgetkit\Joomla\Zoo\Transformer as baseTransformer;

class Transformer extends baseTransformer
{
	public function renderFile($event, $element)
    {
        $event['value'] = $element->get('file');
    }

    public static function getSubscribedEvents()
    {
        return array(
            'joomla.zoo.render.imagepro'      => 'renderFile',
            'joomla.zoo.render.downloadpro'   => 'renderFile',
            'joomla.zoo.render.mediapro'      => 'renderMedia',
            'joomla.zoo.render.googlemapspro' => 'renderGooglemaps',
            'joomla.zoo.render.itemlinkpro'   => 'renderItemlink'
        );
    }
}
