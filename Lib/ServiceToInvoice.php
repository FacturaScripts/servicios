<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
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
     * @param ServicioAT $service
     *
     * @return bool
     */
    public static function deliveryNote(&$service): bool
    {
        $customer = new Cliente();
        if (false === $customer->loadFromCode($service->codcliente)) {
            return false;
        }

        // start transaction
        $database = new DataBase();
        $database->beginTransaction();

        $newAlbaran = new AlbaranCliente();
        $newAlbaran->setSubject($customer);
        $newAlbaran->codagente = $service->codagente ?? $newAlbaran->codagente;
        $newAlbaran->codalmacen = $service->codalmacen;
        $newAlbaran->idempresa = $service->idempresa;
        $newAlbaran->idservicio = $service->idservicio;
        $newAlbaran->nick = $service->nick;
        if (false === $newAlbaran->save()) {
            $database->rollback();
            return false;
        }

        $found = false;
        foreach ($service->getTrabajos() as $work) {
            if ($work->estado !== TrabajoAT::STATUS_MAKE_DELIVERY_NOTE) {
                continue;
            }

            $found = true;
            if (false === static::addLine($newAlbaran, $work, TrabajoAT::STATUS_DELIVERY_NOTE)) {
                $database->rollback();
                return false;
            }
        }

        if (false === $found) {
            ToolBox::i18nLog()->warning('no-works-to-delivery-note');
            $database->rollback();
            return false;
        }

        return static::recalculate($newAlbaran, $database);
    }

    /**
     * @param ServicioAT $service
     *
     * @return bool
     */
    public static function estimation(&$service): bool
    {
        $customer = new Cliente();
        if (false === $customer->loadFromCode($service->codcliente)) {
            return false;
        }

        // start transaction
        $database = new DataBase();
        $database->beginTransaction();

        $newEstimation = new PresupuestoCliente();
        $newEstimation->setSubject($customer);
        $newEstimation->codagente = $service->codagente ?? $newEstimation->codagente;
        $newEstimation->codalmacen = $service->codalmacen;
        $newEstimation->idempresa = $service->idempresa;
        $newEstimation->idservicio = $service->idservicio;
        $newEstimation->nick = $service->nick;
        if (false === $newEstimation->save()) {
            $database->rollback();
            return false;
        }

        $found = false;
        foreach ($service->getTrabajos() as $work) {
            if ($work->estado !== TrabajoAT::STATUS_MAKE_ESTIMATION) {
                continue;
            }

            $found = true;
            if (false === static::addLine($newEstimation, $work, TrabajoAT::STATUS_ESTIMATION)) {
                $database->rollback();
                return false;
            }
        }

        if (false === $found) {
            ToolBox::i18nLog()->warning('no-works-to-estimation');
            $database->rollback();
            return false;
        }

        return static::recalculate($newEstimation, $database);
    }

    /**
     * @param ServicioAT $service
     *
     * @return bool
     */
    public static function invoice(&$service): bool
    {
        $customer = new Cliente();
        if (false === $customer->loadFromCode($service->codcliente)) {
            return false;
        }

        // start transaction
        $database = new DataBase();
        $database->beginTransaction();

        $newInvoice = new FacturaCliente();
        $newInvoice->setSubject($customer);
        $newInvoice->codagente = $service->codagente ?? $newInvoice->codagente;
        $newInvoice->codalmacen = $service->codalmacen;
        $newInvoice->idempresa = $service->idempresa;
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
            if (false === static::addLine($newInvoice, $work, TrabajoAT::STATUS_INVOICED)) {
                $database->rollback();
                return false;
            }
        }

        if (false === $found) {
            ToolBox::i18nLog()->warning('no-works-to-invoice');
            $database->rollback();
            return false;
        }

        return static::recalculate($newInvoice, $database);
    }

    protected static function addLine(SalesDocument &$doc, TrabajoAT &$work, int $estado): bool
    {
        $newLine = empty($work->referencia) ? $doc->getNewLine() : $doc->getNewProductLine($work->referencia);
        $newLine->cantidad = $work->cantidad;
        if ($work->precio) {
            $newLine->pvpunitario = $work->precio;
        }

        if ($work->descripcion) {
            $newLine->descripcion = $work->descripcion;
        }

        $work->estado = $estado;
        if (false === $newLine->save() || false === $work->save()) {
            return false;
        }

        return true;
    }

    /**
     * @param SalesDocument $newDoc
     * @param DataBase $database
     *
     * @return bool
     */
    protected static function recalculate(&$newDoc, &$database): bool
    {
        $docTools = new BusinessDocumentTools();
        $docTools->recalculate($newDoc);
        if ($newDoc->save()) {
            $database->commit();
            return true;
        }

        $database->rollback();
        return false;
    }
}
