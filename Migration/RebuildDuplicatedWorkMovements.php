<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\Servicios\Migration;

use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Template\MigrationClass;
use FacturaScripts\Dinamic\Lib\StockMovementManager;

/**
 * Encola la reconstrucción de los movimientos de stock de los productos con trabajos de servicios
 * que tienen más de un movimiento. Los duplicaba el mod de StockAvanzado al reconstruir el stock
 * de cualquier otro producto. Solo se reconstruyen los productos afectados, no todos.
 */
class RebuildDuplicatedWorkMovements extends MigrationClass
{
    /** Identificador de la migración. */
    const MIGRATION_NAME = 'rebuild_duplicated_work_movements_2026_11_04';

    /**
     * Busca los productos con movimientos duplicados de un mismo trabajo y encola su reconstrucción.
     */
    public function run(): void
    {
        // sin StockAvanzado no hay movimientos de stock
        if (false === Plugins::isEnabled('StockAvanzado') || false === $this->db()->tableExists('stocks_movimientos')) {
            return;
        }

        $sql = "SELECT DISTINCT idproducto FROM stocks_movimientos"
            . " WHERE docmodel = 'TrabajoAT' AND idproducto IS NOT NULL"
            . " GROUP BY idproducto, docid HAVING COUNT(*) > 1";
        foreach ($this->db()->select($sql) as $row) {
            StockMovementManager::queueProductRebuild((int)$row['idproducto']);
        }
    }
}
