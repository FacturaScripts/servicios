<?php
/**
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Plugins\Servicios\Lib\Tickets;

use FacturaScripts\Core\Template\ExtensionsTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Tickets\BaseTicket;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\Ticket;
use FacturaScripts\Dinamic\Model\TicketPrinter;
use FacturaScripts\Dinamic\Model\User;

/**
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class Service extends BaseTicket
{
    use ExtensionsTrait;

    public static function print(ModelClass $model, TicketPrinter $printer, User $user, ?Agente $agent = null): bool
    {
        static::init();

        $ticket = new Ticket();
        $ticket->idprinter = $printer->id;
        $ticket->nick = $user->nick;
        $ticket->title = Tools::trans('service') . ': ' . ($model->codigo ?? $model->id());

        static::setOpenDrawer(false);
        static::setHeader($model, $printer, $ticket->title);
        static::setBody($model, $printer);
        static::setFooter($model, $printer);
        $ticket->body = static::getBody();
        $ticket->base64 = true;
        $ticket->appversion = 1;

        if ($agent) {
            $ticket->codagente = $agent->codagente;
        }

        return $ticket->save();
    }

    protected static function setBody(ModelClass $model, TicketPrinter $printer): void
    {
        static::$escpos->setTextSize($printer->font_size, $printer->font_size);

        static::$escpos->text(static::sanitize(Tools::trans('date') . ': ' . $model->fecha . ' ' . $model->hora) . "\n");

        if (Tools::settings('servicios', 'print_ticket_agent', false)) {
            static::$escpos->text(static::sanitize(Tools::trans('agent') . ': ' . $model->getAgent()->nombre) . "\n");
        }

        if (Tools::settings('servicios', 'print_ticket_assigned', false)) {
            static::$escpos->text(static::sanitize(Tools::trans('assigned') . ': ' . $model->getAsignado()->nick) . "\n");
        }

        $customer = $model->getCustomer();
        static::$escpos->text(static::sanitize(Tools::trans('customer') . ': ' . $customer->razonsocial) . "\n");
        if ($model->telefono1 || $customer->telefono1) {
            static::$escpos->text(static::sanitize(Tools::trans('phone') . ': ' . ($model->telefono1 ?? $customer->telefono1)) . "\n");
        }
        if ($model->telefono2 || $customer->telefono2) {
            static::$escpos->text(static::sanitize(Tools::trans('phone2') . ': ' . ($model->telefono2 ?? $customer->telefono2)));
        }

        if ($model->descripcion) {
            static::$escpos->text("\n\n" . static::sanitize(Tools::trans('description')));
            static::$escpos->text("\n" . static::sanitize($model->descripcion));
        }

        if ($model->material) {
            static::$escpos->text("\n\n" . static::sanitize(Tools::trans('material')));
            static::$escpos->text("\n" . static::sanitize($model->material));
        }

        if ($model->solucion) {
            static::$escpos->text("\n\n" . static::sanitize(Tools::trans('solution')));
            static::$escpos->text("\n" . static::sanitize($model->solucion));
        }

        if ($model->observaciones && Tools::settings('servicios', 'print_ticket_observations', false)) {
            static::$escpos->text("\n\n" . static::sanitize(Tools::trans('observations')));
            static::$escpos->text("\n" . static::sanitize($model->observaciones));
        }

        static::setMachines($model, $printer);
        static::setWorks($model, $printer);
    }

    protected static function setMachines(ModelClass $model, TicketPrinter $printer): void
    {
        $machines = $model->getMachines();
        if (empty($machines) || false === Tools::settings('servicios', 'print_ticket_machine_info', false)) {
            return;
        }

        static::$escpos->text("\n\n" . static::sanitize(Tools::trans('machines')) . "\n");
        foreach ($machines as $machine) {
            static::$escpos->text(static::sanitize($machine->nombre . ' - ' . $machine->numserie) . "\n");
        }
    }

    protected static function setFooter(ModelClass $model, TicketPrinter $printer): void
    {
        parent::setFooter($model, $printer);

        // si hay un texto personalizado de pie de ticket, lo añadimos
        if (false === empty(Tools::settings('servicios', 'print_ticket_footer_text'))) {
            static::$escpos->text("\n" . static::sanitize(Tools::settings('servicios', 'print_ticket_footer_text')) . "\n");
        }
    }

    protected static function setWorks(ModelClass $model, TicketPrinter $printer): void
    {
        $works = $model->getTrabajos();
        if (empty($works) || false === Tools::settings('servicios', 'print_ticket_works', false)) {
            return;
        }

        static::$escpos->text("\n\n" . static::sanitize(Tools::trans('works')));
        foreach ($works as $work) {
            static::$escpos->text("\n");
            static::$escpos->text(static::sanitize(Tools::trans('from-date') . ': ' . $work->fechainicio) . "\n");
            static::$escpos->text(static::sanitize(Tools::trans('from-hour') . ': ' . $work->horainicio) . "\n");
            static::$escpos->text(static::sanitize(Tools::trans('until-date') . ': ' . $work->fechafin) . "\n");
            static::$escpos->text(static::sanitize(Tools::trans('until-hour') . ': ' . $work->horafin) . "\n");
            static::$escpos->text(static::sanitize(Tools::trans('observations') . ': ' . $work->observaciones) . "\n");

            if (Tools::settings('servicios', 'print_ticket_work_reference', false)) {
                static::$escpos->text(static::sanitize(Tools::trans('reference') . ': '. $work->referencia) . "\n");
            }

            if (Tools::settings('servicios', 'print_ticket_work_description', false)) {
                static::$escpos->text(static::sanitize(Tools::trans('description') . ': ' . $work->descripcion) . "\n");
            }

            if (Tools::settings('servicios', 'print_ticket_work_quantity', false)) {
                static::$escpos->text(static::sanitize(Tools::trans('quantity') . ': ' . $work->cantidad) . "\n");
            }

            if (Tools::settings('servicios', 'print_ticket_work_price', false)) {
                static::$escpos->text(static::sanitize(Tools::trans('price') . ': ' . Tools::money($work->precio)) . "\n");
            }

            static::$escpos->text("\n");
        }
    }
}
