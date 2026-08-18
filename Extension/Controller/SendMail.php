<?php
/**
 * Copyright (C) 2026 Daniel Fernández Giménez <contacto@danielfg.es>
 */

namespace FacturaScripts\Plugins\Servicios\Extension\Controller;

use Closure;
use FacturaScripts\Core\Tools;

/**
 * Extensión del controlador SendMail que añade al email el enlace público del servicio para portal cliente.
 *
 * Si el ajuste show_public_share_mail_body está activo y se está enviando un servicio,
 * añade al cuerpo del mensaje la url pública con el
 * código de compartición para que el cliente pueda verlo sin identificarse.
 *
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
class SendMail
{
    /**
     * Añade al cuerpo del email el texto y el enlace público del servicio que se envía.
     *
     * @return Closure
     */
    public function loadDataDefault(): Closure
    {
        return function ($model) {
            if (false === ((bool)Tools::settings('portalcliente', 'show_public_share_mail_body', false))
                || $model->modelClassName() !== 'ServicioAT') {
                return;
            }

            $this->newMail->body(
                $this->newMail->text
                . "\n\n"
                . Tools::trans('public-share-service-mail-body')
                . "\n"
                . Tools::siteUrl() . $model->url('public-share')
            );
        };
    }
}
