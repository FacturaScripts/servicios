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

namespace FacturaScripts\Plugins\Servicios\Lib;

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Template\ExtensionsTrait;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Dinamic\Model\ServicioAT;
use FacturaScripts\Dinamic\Model\TrabajoAT;

/**
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ServiceToInvoice
{
    use ExtensionsTrait;

    protected static $generated = [];

    public static function clear(): void
    {
        self::$generated = [];
    }

    public static function deliveryNote(ServicioAT &$service): bool
    {
        $customer = new Cliente();
        if (false === $customer->load($service->codcliente)) {
            return false;
        }

        // start transaction
        $db = new DataBase();
        $db->beginTransaction();

        $newAlbaran = new AlbaranCliente();
        $newAlbaran->setSubject($customer);
        $newAlbaran->codagente = $service->codagente ?? $newAlbaran->codagente;
        $newAlbaran->codalmacen = $service->codalmacen;
        $newAlbaran->idempresa = $service->idempresa;
        $newAlbaran->idservicio = $service->idservicio;
        $newAlbaran->nick = $service->nick;

        if ($service->hasColumn('idproyecto') &&
            $newAlbaran->hasColumn('idproyecto') &&
            $service->idproyecto) {
            $newAlbaran->idproyecto = $service->idproyecto;
        }

        $pipe = new self();
        $pipeAlbaran = $pipe->pipe('deliveryNote', $service, $newAlbaran);
        if ($pipeAlbaran) {
            $newAlbaran = $pipeAlbaran;
        }

        if (false === $newAlbaran->save()) {
            $db->rollback();
            return false;
        }

        if (false === static::addLineService($newAlbaran, $service)) {
            $db->rollback();
            return false;
        }

        $found = false;
        foreach ($service->getTrabajos() as $work) {
            if ($work->estado !== TrabajoAT::STATUS_MAKE_DELIVERY_NOTE) {
                continue;
            }

            $found = true;
            if (false === static::addLineWork($newAlbaran, $work, TrabajoAT::STATUS_DELIVERY_NOTE)) {
                $db->rollback();
                return false;
            }
        }

        if (false === $found) {
            Tools::log()->warning('no-works-to-delivery-note');
            $db->rollback();
            return false;
        }

        return static::recalculate($newAlbaran, $db);
    }

    public static function estimation(ServicioAT &$service): bool
    {
        $customer = new Cliente();
        if (false === $customer->load($service->codcliente)) {
            return false;
        }

        // start transaction
        $db = new DataBase();
        $db->beginTransaction();

        $newEstimation = new PresupuestoCliente();
        $newEstimation->setSubject($customer);
        $newEstimation->codagente = $service->codagente ?? $newEstimation->codagente;
        $newEstimation->codalmacen = $service->codalmacen;
        $newEstimation->idempresa = $service->idempresa;
        $newEstimation->idservicio = $service->idservicio;
        $newEstimation->nick = $service->nick;

        if ($service->hasColumn('idproyecto') &&
            $newEstimation->hasColumn('idproyecto') &&
            $service->idproyecto) {
            $newEstimation->idproyecto = $service->idproyecto;
        }

        $pipe = new self();
        $pipeEstimation = $pipe->pipe('estimation', $service, $newEstimation);
        if ($pipeEstimation) {
            $newEstimation = $pipeEstimation;
        }

        if (false === $newEstimation->save()) {
            $db->rollback();
            return false;
        }

        if (false === static::addLineService($newEstimation, $service)) {
            $db->rollback();
            return false;
        }

        $found = false;
        foreach ($service->getTrabajos() as $work) {
            if ($work->estado !== TrabajoAT::STATUS_MAKE_ESTIMATION) {
                continue;
            }

            $found = true;
            if (false === static::addLineWork($newEstimation, $work, TrabajoAT::STATUS_ESTIMATION)) {
                $db->rollback();
                return false;
            }
        }

        if (false === $found) {
            Tools::log()->warning('no-works-to-estimation');
            $db->rollback();
            return false;
        }

        return static::recalculate($newEstimation, $db);
    }

    public static function generated(): array
    {
        return self::$generated;
    }

    public static function invoice(ServicioAT &$service): bool
    {
        $customer = new Cliente();
        if (false === $customer->load($service->codcliente)) {
            return false;
        }

        // start transaction
        $db = new DataBase();
        $db->beginTransaction();

        $newInvoice = new FacturaCliente();
        $newInvoice->setSubject($customer);
        $newInvoice->codagente = $service->codagente ?? $newInvoice->codagente;
        $newInvoice->codalmacen = $service->codalmacen;
        $newInvoice->idempresa = $service->idempresa;
        $newInvoice->idservicio = $service->idservicio;
        $newInvoice->nick = $service->nick;

        if ($service->hasColumn('idproyecto') &&
            $newInvoice->hasColumn('idproyecto') &&
            $service->idproyecto) {
            $newInvoice->idproyecto = $service->idproyecto;
        }

        $pipe = new self();
        $pipeInvoice = $pipe->pipe('invoice', $service, $newInvoice);
        if ($pipeInvoice) {
            $newInvoice = $pipeInvoice;
        }

        if (false === $newInvoice->save()) {
            $db->rollback();
            return false;
        }

        if (false === static::addLineService($newInvoice, $service)) {
            $db->rollback();
            return false;
        }

        $found = false;
        foreach ($service->getTrabajos() as $work) {
            if ($work->estado !== TrabajoAT::STATUS_MAKE_INVOICE) {
                continue;
            }

            $found = true;
            if (false === static::addLineWork($newInvoice, $work, TrabajoAT::STATUS_INVOICED)) {
                $db->rollback();
                return false;
            }
        }

        if (false === $found) {
            Tools::log()->warning('no-works-to-invoice');
            $db->rollback();
            return false;
        }

        return static::recalculate($newInvoice, $db);
    }

    protected static function addLineService(SalesDocument &$doc, ServicioAT $service): bool
    {
        $saveLine = false;

        $newLine = $doc->getNewLine();
        $newLine->cantidad = 0;
        $newLine->descripcion = Tools::trans('service') . ': ' . $service->codigo;
        $newLine->codimpuesto = null;
        $newLine->iva = 0;

        if (Tools::settings('servicios', 'document_machine')) {
            foreach ($service->getMachines() as $machine) {
                $newLine->descripcion .= "\n"
                    . Tools::trans('machine') . ': ' . $machine->nombre;

                if ($machine->numserie) {
                    $newLine->descripcion .= ' (' . $machine->numserie . ')';
                }

                $saveLine = true;
            }
        }

        if (Tools::settings('servicios', 'document_start_date')) {
            $startDate = $service->fecha;
            foreach ($service->getTrabajos() as $work) {
                if (strtotime($work->fechainicio) < strtotime($startDate)) {
                    $startDate = $work->fechainicio;
                }
            }

            $newLine->descripcion .= "\n"
                . Tools::trans('start-date') . ': ' . $startDate;

            $saveLine = true;
        }

        if (Tools::settings('servicios', 'document_end_date')) {
            $endDate = $service->fecha;
            foreach ($service->getTrabajos() as $work) {
                if (strtotime($work->fechafin) > strtotime($endDate)) {
                    $endDate = $work->fechafin;
                }
            }

            $newLine->descripcion .= "\n"
                . Tools::trans('end-date') . ': ' . $endDate;

            $saveLine = true;
        }

        if (Tools::settings('servicios', 'document_description') && $service->descripcion) {
            $newLine->descripcion .= "\n\n"
                . Tools::trans('description') . "\n" . $service->descripcion;

            $saveLine = true;
        }

        if (Tools::settings('servicios', 'document_material') && $service->material) {
            $newLine->descripcion .= "\n\n"
                . Tools::trans('material')
                . "\n" . $service->material;

            $saveLine = true;
        }

        if (Tools::settings('servicios', 'document_solution') && $service->solucion) {
            $newLine->descripcion .= "\n\n"
                . Tools::trans('solution')
                . "\n" . $service->solucion;

            $saveLine = true;
        }

        if (Tools::settings('servicios', 'document_observations') && $service->observaciones) {
            $newLine->descripcion .= "\n\n"
                . Tools::trans('observations')
                . "\n" . $service->observaciones;

            $saveLine = true;
        }

        $pipe = new self();
        $pipeLine = $pipe->pipe('lineService', $service, $newLine);
        if ($pipeLine) {
            $newLine = $pipeLine;
        }

        if ($saveLine && false === $newLine->save()) {
            return false;
        }

        return true;
    }

    protected static function addLineWork(SalesDocument &$doc, TrabajoAT &$work, int $estado): bool
    {
        $newLine = empty($work->referencia) ? $doc->getNewLine() : $doc->getNewProductLine($work->referencia);
        $newLine->cantidad = $work->cantidad;
        $newLine->idtrabajo = $work->idtrabajo;
        if ($work->precio) {
            $newLine->pvpunitario = $work->precio;
        }

        if ($work->descripcion) {
            $newLine->descripcion = $work->descripcion;
        }

        $work->estado = $estado;

        $pipe = new self();
        $pipeLine = $pipe->pipe('lineWork', $doc, $work, $newLine);
        if ($pipeLine) {
            $newLine = $pipeLine;
        }

        if (false === $work->save() || false === $newLine->save()) {
            return false;
        }

        return true;
    }

    protected static function recalculate(SalesDocument &$newDoc, DataBase &$db): bool
    {
        $lines = $newDoc->getLines();
        Calculator::calculate($newDoc, $lines, true);
        if (Calculator::calculate($newDoc, $lines, true)) {
            $db->commit();

            self::$generated[] = $newDoc;
            return true;
        }

        $db->rollback();
        return false;
    }
}
