<?php
/**
 * This file is part of Proyectos plugin for FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Description of Randomizer
 *
 * @author Jose Antonio Cuello  <yopli2000@gmail.com>
 */
class Randomizer
{

    protected function loadButtons()
    {
        return function() {
            $this->addButton('plugins', 'servicios', 'generated-services', 'services', 'fas fa-headset', 'Random\\Servicios', 'ServicioAT');
        };
    }
}
