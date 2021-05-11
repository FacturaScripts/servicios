<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Plugins\Servicios\Lib\Export;

use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Plugins\Servicios\Model\ServicioAT;

/**
 * Description of PDFserviciosExport
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class PDFserviciosExport extends \FacturaScripts\Core\Lib\Export\PDFExport
{

    /**
     * 
     * @param ServicioAT $model
     * @param array      $columns
     * @param string     $title
     *
     * @return bool
     */
    public function addModelPage($model, $columns, $title = ''): bool
    {
        $this->newPage();
        $idempresa = isset($model->idempresa) ? $model->idempresa : null;
        $this->insertHeader($idempresa);

        $this->pdf->ezText("\n" . $title . ': ' . $model->idservicio . "\n", self::FONT_SIZE + 6);
        $this->newLine();

        $subject = $model->getSubject();
        $tipoidfiscal = empty($subject->tipoidfiscal) ? $this->i18n->trans('cifnif') : $subject->tipoidfiscal;
        $tableData = [
            ['key' => $this->i18n->trans('date'), 'value' => $model->fecha],
            ['key' => $this->i18n->trans('hour'), 'value' => $model->hora],
            ['key' => $this->i18n->trans('customer'), 'value' => Utils::fixHtml($subject->nombre)],
            ['key' => $tipoidfiscal, 'value' => $subject->cifnif],
            ['key' => $this->i18n->trans('address'), 'value' => $subject->getDefaultAddress()->direccion],
            ['key' => $this->i18n->trans('phone'), 'value' => $subject->telefono1]
        ];

        $tableOptions = [
            'width' => $this->tableWidth,
            'showHeadings' => 0,
            'shaded' => 0,
            'lineCol' => [1, 1, 1],
            'cols' => []
        ];
        $this->insertParalellTable($tableData, '', $tableOptions);
        $this->pdf->ezText('');

        if ($model->descripcion) {
            $this->pdf->ezText("\n" . $this->i18n->trans('description') . "\n", self::FONT_SIZE + 6);
            $this->newLine();
            $this->pdf->ezText(\nl2br($model->descripcion) . "\n", self::FONT_SIZE + 2);
        }

        if ($model->material) {
            $this->pdf->ezText("\n" . $this->i18n->trans('material') . "\n", self::FONT_SIZE + 6);
            $this->newLine();
            $this->pdf->ezText(\nl2br($model->material) . "\n", self::FONT_SIZE + 2);
        }

        if ($model->solucion) {
            $this->pdf->ezText("\n" . $this->i18n->trans('solution') . "\n", self::FONT_SIZE + 6);
            $this->newLine();
            $this->pdf->ezText(\nl2br($model->solucion) . "\n", self::FONT_SIZE + 2);
        }

        return false;
    }
}
