<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\Email\MailNotifier;
use FacturaScripts\Core\Template\CronClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\EmailNotification;
use FacturaScripts\Dinamic\Model\ServicioAT;

class Cron extends CronClass
{
    public function run(): void
    {
        $this->job('send-service-notification')
            ->everyDayAt(8)
            ->run(function () {
                foreach (ServicioAT::all() as $servicio) {

                    $fecha = Tools::date($servicio->fecha);
                    $fecha = Tools::dateOperation($fecha, '-1 day');

                    $fechaActual = Tools::date();

                    if ($fecha === $fechaActual) {
                        $cliente = $servicio->getCustomer();

                        $notification = new EmailNotification();
                        $notification->load('new-start-service');

                        MailNotifier::send('new-start-service', $cliente->email, $cliente->codcliente, [
                            'number' => $servicio->idservicio,
                            'name' => $cliente->nombre,
                            'url' => Tools::siteUrl() . '/EditServicioAT?code=' . $servicio->idservicio
                        ]);
                    }
                }
            });
    }
}
