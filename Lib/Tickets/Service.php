<?php
/**
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Plugins\Servicios\Lib\Tickets;

use FacturaScripts\Core\Model\Base\ModelClass;
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
    public static function print(ModelClass $model, TicketPrinter $printer, User $user, Agente $agent = null): bool
    {
        static::init();

        $ticket = new Ticket();
        $ticket->idprinter = $printer->id;
        $ticket->nick = $user->nick;
        $ticket->title = static::$i18n->trans('service') . ' Service.php' . $model->primaryColumnValue();

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

        static::$escpos->text(static::sanitize(static::$i18n->trans('date') . ': ' . $model->fecha . ' ' . $model->hora) . "\n");

        $customer = $model->getCustomer();
        static::$escpos->text(static::sanitize(static::$i18n->trans('customer') . ': ' . $customer->razonsocial) . "\n");
        if ($customer->telefono1) {
            static::$escpos->text(static::sanitize(static::$i18n->trans('phone') . ': ' . $customer->telefono1) . "\n\n");
        }
        if ($customer->telefono2) {
            static::$escpos->text(static::sanitize(static::$i18n->trans('phone') . ': ' . $customer->telefono2) . "\n\n");
        }

        static::$escpos->text(static::sanitize(static::$i18n->trans('description') . ': ' . $model->descripcion) . "\n");

        if ($model->material) {
            static::$escpos->text(static::sanitize(static::$i18n->trans('material') . ': ' . $model->material) . "\n");
        }
    }

    protected static function setFooter(ModelClass $model, TicketPrinter $printer): void
    {
        parent::setFooter($model, $printer);

        // si hay un texto personalizado de pie de ticket, lo añadimos
        if (false === empty(Tools::settings('servicios', 'footertext'))) {
            static::$escpos->text("\n" . static::sanitize(Tools::settings('servicios', 'footertext')) . "\n");
        }
    }
}
