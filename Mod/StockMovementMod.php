<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2022-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Servicios\Mod;

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\TrabajoAT;
use FacturaScripts\Dinamic\Model\MovimientoStock;
use FacturaScripts\Dinamic\Model\Variante;
use FacturaScripts\Plugins\StockAvanzado\Contract\StockMovementModInterface;

/**
 * Mod de StockAvanzado que regenera los movimientos de stock de los trabajos de los servicios
 * cuando se reconstruye el stock de un producto (o de todos).
 */
class StockMovementMod implements StockMovementModInterface
{
    /**
     * Genera los movimientos de los trabajos con referencia.
     * Si se indica un producto, solo se generan los de los trabajos de ese producto.
     * Si la gestión de stock de Servicios está desactivada, no genera nada.
     *
     * @param int|null $idproduct Producto que se está reconstruyendo, o null para todos.
     */
    public function run(?int $idproduct = null): void
    {
        // si la gestión de stock en servicios está desactivada, no generamos movimientos
        if (Tools::settings('servicios', 'disablestockmanagement', false)) {
            return;
        }

        $where = [Where::notEq('referencia', '')];

        // si se reconstruye un producto concreto, solo sus referencias
        if (!is_null($idproduct)) {
            $referencias = [];
            foreach (Variante::all([Where::eq('idproducto', $idproduct)], [], 0, 0) as $variante) {
                $referencias[] = $variante->referencia;
            }
            if (empty($referencias)) {
                return;
            }
            $where[] = Where::in('referencia', $referencias);
        }

        // generamos un movimiento para cada trabajo con referencia
        $model = new TrabajoAT();
        $orderBy = ['idtrabajo' => 'ASC'];
        $limit = 1000;
        $offset = 0;

        do {
            $trabajos = $model->all($where, $orderBy, $offset, $limit);
            foreach ($trabajos as $trabajo) {
                $this->generateMovement($trabajo);
            }
            $offset += $limit;
        } while (count($trabajos) == $limit);
    }

    /**
     * Crea el movimiento de stock de un trabajo, salvo que su estado no afecte al stock,
     * el producto no controle stock o el trabajo ya tenga su movimiento.
     * La gestión de stock de Servicios desactivada ya la descarta run().
     *
     * @param TrabajoAT $trabajo Trabajo del que se genera el movimiento.
     */
    private function generateMovement(TrabajoAT $trabajo): void
    {
        $estados = [TrabajoAT::STATUS_NONE, TrabajoAT::STATUS_MAKE_INVOICE, TrabajoAT::STATUS_MAKE_DELIVERY_NOTE];
        if (empty($trabajo->referencia) || empty($trabajo->cantidad) || false === in_array($trabajo->estado, $estados)) {
            return;
        }

        // ¿El producto controla stock?
        $producto = $trabajo->getVariante()->getProducto();
        if ($producto->nostock) {
            return;
        }

        // ¿el trabajo ya tiene su movimiento?
        $existente = new MovimientoStock();
        $where = [
            Where::eq('docid', $trabajo->idtrabajo),
            Where::eq('docmodel', 'TrabajoAT'),
        ];
        if ($existente->loadWhere($where)) {
            return;
        }

        $movement = new MovimientoStock();
        $movement->codalmacen = $trabajo->getServicio()->codalmacen;
        $movement->referencia = $trabajo->referencia;
        $movement->cantidad = 0 - $trabajo->cantidad;
        $movement->docid = $trabajo->idtrabajo;
        $movement->docmodel = 'TrabajoAT';
        $movement->documento = Tools::trans('service') . ' #' . $trabajo->idservicio;
        $movement->idproducto = $trabajo->getVariante()->idproducto;
        $movement->fecha = $trabajo->fechainicio;
        $movement->hora = $trabajo->horainicio;
        $movement->save();
    }
}
