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

namespace FacturaScripts\Plugins\Servicios\Extension\Model;

use Closure;
use FacturaScripts\Dinamic\Lib\PortalDocShare;

/**
 * Extensión del modelo ServicioAT que añade el comportamiento del portal del cliente.
 * 
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
class ServicioAT
{
    /**
     * Asigna el identificador público (pc_uuid) al documento si aún no lo tiene.
     *
     * @return Closure
     */
    public function test(): Closure
    {
        return function () {
            if (empty($this->pc_uuid)) {
                $this->pc_uuid = uniqid();
            }
        };
    }

    /**
     * Añade los tipos de url públicos del portal: «public» (ver el servicio), «public-print»
     * (imprimirlo) y sus variantes «public-share» y «public-print-share», que incluyen el
     * código de compartición para acceder sin estar identificado.
     *
     * Cuando el servicio tiene pc_uuid se usa la url amigable PortalServicio/{uuid}; si no,
     * se recurre a la clave primaria.
     *
     * @return Closure
     */
    public function url(): Closure
    {
        return function ($type, $list) {
            switch ($type) {
                case 'public':
                    return empty($this->pc_uuid)
                        ? 'PortalServicio?code=' . $this->id()
                        : 'PortalServicio/' . $this->pc_uuid;

                case 'public-share':
                    $url = $this->url('public', $list);
                    $url .= str_contains($url, '?') ? '&' : '?';
                    $url .= 'share=' . PortalDocShare::getCode($this);
                    return $url;

                case 'public-print':
                    return empty($this->pc_uuid)
                        ? 'PortalServicio?code=' . $this->id() . '&action=print'
                        : 'PortalServicio/' . $this->pc_uuid . '?action=print';

                case 'public-print-share':
                    $url = $this->url('public-print', $list);
                    $url .= str_contains($url, '?') ? '&' : '?';
                    $url .= 'share=' . PortalDocShare::getCode($this);
                    return $url;
            }
        };
    }
}
