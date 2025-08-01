<?php

namespace FacturaScripts\Plugins\Servicios;

use DateTime;
use FacturaScripts\Core\Lib\Email\MailNotifier;
use FacturaScripts\Core\Template\CronClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\EmailNotification;
use FacturaScripts\Dinamic\Model\ServicioAT;

/**
 * El cron de FacturaScripts ejecutará todos los procesos cron de los plugins activos,
 * siempre y cuando haya sido configurado en el sistema o hosting.
 * Así que si necesita ejecutar algo de forma periódica, el mejor lugar es el cron de su plugin.
 *
 * https://facturascripts.com/publicaciones/el-archivo-cron-php-855
 */
class Cron extends CronClass
{
    public function run(): void
    {
        $this->job('send-service-notification')
            ->everyDayAt(8)
            ->run(function () {
                $service = new ServicioAT();

                foreach ($service->all() as $servicio) {

                    $fecha = Tools::date($servicio->fecha);
                    $fecha = Tools::dateOperation($fecha, '-1 day');

                    $fechaActual = Tools::date();

                    if ($fecha === $fechaActual) {
                        $cliente = $servicio->getCustomer();

                        $notification = new EmailNotification();
                        $notification->loadFromCode('new-start-service');

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
