<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Plugins\Servicios\Lib;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Plugins\Servicios\Model\ServicioAT;
use FacturaScripts\Plugins\Servicios\Model\TrabajoAT;

/**
 * Description of ServiceToInvoice
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ServiceToInvoice
{

    /**
     * 
     * @param ServicioAT $service
     *
     * @return bool
     */
    public static function generate(&$service)
    {
        $customer = new Cliente();
        if (false === $customer->loadFromCode($service->codcliente)) {
            return false;
        }

        /// start transaction
        $database = new DataBase();
        $database->beginTransaction();

        $newInvoice = new FacturaCliente();
        $newInvoice->setSubject($customer);
        $newInvoice->codagente = $service->codagente ?? $newInvoice->codagente;
        $newInvoice->idservicio = $service->idservicio;
        $newInvoice->nick = $service->nick;
        if (false === $newInvoice->save()) {
            $database->rollback();
            return false;
        }

        $found = false;
        foreach ($service->getTrabajos() as $work) {
            if ($work->estado !== TrabajoAT::STATUS_MAKE_INVOICE) {
                continue;
            }

            $found = true;
            $newLine = empty($work->referencia) ? $newInvoice->getNewLine() : $newInvoice->getNewProductLine($work->referencia);
            $newLine->cantidad = $work->cantidad;
            if ($work->precio) {
                $newLine->pvpunitario = $work->precio;
            }

            if ($work->descripcion) {
                $newLine->descripcion = $work->descripcion;
            }

            $work->estado = TrabajoAT::STATUS_INVOICED;
            if (false === $newLine->save() || false === $work->save()) {
                $database->rollback();
                return false;
            }
        }

        if (false === $found) {
            static::toolBox()->i18nLog()->warning('no-works-to-invoice');
            $database->rollback();
            return false;
        }

        /// update invoice
        $docTools = new BusinessDocumentTools();
        $docTools->recalculate($newInvoice);
        if ($newInvoice->save()) {
            $database->commit();
            return true;
        }

        $database->rollback();
        return false;
    }

    /**
     * 
     * @return ToolBox
     */
    protected static function toolBox()
    {
        return new ToolBox();
    }
}
