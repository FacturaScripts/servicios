<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Servicios;

use FacturaScripts\Core\Base\CronClass;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Servicios\Model\ServicioAT;

class Cron extends CronClass
{
    const JOB_NAME = 'update-services-code';
    const JOB_INTERVAL = '1 year';

    public function run()
    {
        if ($this->isTimeForJob(self::JOB_NAME, self::JOB_INTERVAL)) {

            // buscamos todos los servicios con codigo = null
            $serviceModel = new ServicioAT();
            $where = [new DataBaseWhere('codigo', null, 'IS')];
            $orderBy = ['idservicio' => 'DESC'];
            foreach ($serviceModel->all($where, $orderBy, 0, 500) as $service) {

                // guardamos, para que se genere el codigo
                $service->save();
            }

            $this->jobDone(self::JOB_NAME);
        }
    }
}

