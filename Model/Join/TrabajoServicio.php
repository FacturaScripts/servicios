<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Servicios\Model\Join;

use FacturaScripts\Core\Model\Base\JoinModel;
use FacturaScripts\Plugins\Servicios\Model\ServicioAT;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class TrabajoServicio extends JoinModel
{
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->setMasterModel(new ServicioAT());
    }

    protected function getFields(): array
    {
        return [
            'cantidad' => 'serviciosat_trabajos.cantidad',
            'codagente' => 'serviciosat_trabajos.codagente',
            'descripcion' => 'serviciosat_trabajos.descripcion',
            'estado' => 'serviciosat_trabajos.estado',
            'fechafin' => 'serviciosat_trabajos.fechafin',
            'fechainicio' => 'serviciosat_trabajos.fechainicio',
            'horafin' => 'serviciosat_trabajos.horafin',
            'horainicio' => 'serviciosat_trabajos.horainicio',
            'idservicio' => 'serviciosat_trabajos.idservicio',
            'idtrabajo' => 'serviciosat_trabajos.idtrabajo',
            'nick' => 'serviciosat_trabajos.nick',
            'observaciones' => 'serviciosat_trabajos.observaciones',
            'precio' => 'serviciosat_trabajos.precio',
            'referencia' => 'serviciosat_trabajos.referencia',
            'codcliente' => 'serviciosat.codcliente',
            'codigo' => 'serviciosat.codigo',
        ];
    }

    protected function getSQLFrom(): string
    {
        return 'serviciosat_trabajos'
            . ' LEFT JOIN serviciosat ON serviciosat_trabajos.idservicio = serviciosat.idservicio';
    }

    protected function getTables(): array
    {
        return ['serviciosat', 'serviciosat_trabajos'];
    }
}