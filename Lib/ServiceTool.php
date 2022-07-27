<?php
/**
 * Copyright (C) 2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Plugins\Servicios\Lib;

/**
 * Description of ServiceTool
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class ServiceTool
{
    public static function getSiteUrl()
    {
        $url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $url .= '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        return substr($url, 0, strrpos($url, '/'));
    }
}