<?php

namespace YOOtheme\Widgetkit\Joomla\Zl;

use YOOtheme\Widgetkit\Joomla\Zoo\Transformer as baseTransformer;

class Transformer extends baseTransformer
{
    public static function getSubscribedEvents()
    {
        return array(
            'joomla.zoo.render.imagepro'      => 'renderImage',
            'joomla.zoo.render.mediapro'      => 'renderMedia',
            'joomla.zoo.render.googlemapspro' => 'renderGooglemaps',
            'joomla.zoo.render.itemlinkpro'   => 'renderItemlink'
        );
    }
}
