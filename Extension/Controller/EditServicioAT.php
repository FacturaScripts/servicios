<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\Servicios\Extension\Controller;

use Closure;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\PortalCliente\Extension\Controller\CommonFileTrait;

/**
 * Comportamiento de la extensión del controlador de edición de servicios (ServicioAT),
 * para añadir el botón «Compartir» en la vista principal y darle funcionalidad con PortalCliente.
 * 
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
class EditServicioAT
{
    use CommonFileTrait;

    public function loadData(): Closure
    {
        return function($viewName, $view) {
            $mvn = $this->getMainViewName();
            if ($viewName !== $mvn || false === $view->model->exists()) {
                return;
            }

            $this->addButton($viewName, [
                'action' => Tools::siteUrl() . '/' . $view->model->url('public-share'),
                'color' => 'info',
                'icon' => 'fa-solid fa-share',
                'label' => 'share',
                'type' => 'link',
                'target' => '_blank',
            ]);
        };
    }
}
