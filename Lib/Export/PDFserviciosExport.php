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
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
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
        $subject = $model->getSubject();
        $idempresa = isset($model->idempresa) ? $model->idempresa : null;

        $this->newPage();
        $this->insertHeader($idempresa);
        $this->pdf->ezText("\n" . $title . ': ' . $model->idservicio . "\n", self::FONT_SIZE + 6);
        $this->newLine();

        $this->insertParallelTable($this->serviceData($model, $subject), '', $this->tableOptions());
        $this->pdf->ezText('');

        if ($this->toolBox()->appSettings()->get('servicios', 'printmachineinfo', false)) {
            $this->printTableSection('machines', $this->machinesData($model));
        }

        $this->printTextSection('description', $model->descripcion);
        $this->printTextSection('material', $model->material);
        $this->printTextSection('solution', $model->solucion);

        if ($this->toolBox()->appSettings()->get('servicios', 'printobservations', false)) {
            $this->printTextSection('observations', $model->observaciones);
        }

        if ($this->toolBox()->appSettings()->get('servicios', 'printworks', false)) {
            $this->printTableSection('work', $this->worksData($model));
        }

        $footer = $this->toolBox()->appSettings()->get('servicios', 'footertext', '');
        $this->printTextSection("", $footer, false);

        return false;
    }

    /**
     *
     * @param ServicioAT $model
     *
     * @return array
     */
    private function machinesData(&$model): array
    {
        $result = [];
        foreach ($model->getMachines() as $machine) {
            $result[] = [
                $this->i18n->trans('name') => $machine->nombre,
                $this->i18n->trans('serial-number') => $machine->numserie,
                $this->i18n->trans('description') => $machine->descripcion
            ];
        }
        return $result;
    }

    /**
     * Print a section with an array of data.
     *
     * @param string $title
     * @param array  $data
     */
    protected function printTableSection($title, $data)
    {
        $this->pdf->ezText("\n" . $this->i18n->trans($title) . "\n", self::FONT_SIZE + 4);
        $this->newLine();
        $this->pdf->ezTable($data, '', '', $this->tableOptions(1));
        $this->pdf->ezText('');
    }

    /**
     * Print a section with a text data.
     *
     * @param string $title
     * @param string $text
     */
    protected function printTextSection($title, $text, $addLine = true)
    {
        if (empty($text)) {
            return;
        }

        $this->pdf->ezText("\n" . $this->i18n->trans($title) . "\n", self::FONT_SIZE + 4);
        if ($addLine) {
            $this->newLine();
        }
        $this->pdf->ezText(\nl2br($text) . "\n", self::FONT_SIZE + 2);
    }

    /**
     *
     * @return array
     */
    protected function tableOptions($headings = 0): array
    {
        return [
            'width' => $this->tableWidth,
            'showHeadings' => $headings,
            'shaded' => 0,
            'lineCol' => [1, 1, 1],
            'cols' => []
        ];
    }

    /**
     *
     * @param ServicioAT $model
     * @param Cliente    $subject
     *
     * @return array
     */
    private function serviceData(&$model, &$subject): array
    {
        $tipoidfiscal = empty($subject->tipoidfiscal) ? $this->i18n->trans('cifnif') : $subject->tipoidfiscal;

        return [
            ['key' => $this->i18n->trans('date'), 'value' => $model->fecha],
            ['key' => $this->i18n->trans('hour'), 'value' => $model->hora],
            ['key' => $this->i18n->trans('customer'), 'value' => Utils::fixHtml($subject->nombre)],
            ['key' => $tipoidfiscal, 'value' => $subject->cifnif],
            ['key' => $this->i18n->trans('address'), 'value' => $subject->getDefaultAddress()->direccion],
            ['key' => $this->i18n->trans('phone'), 'value' => $subject->telefono1]
        ];
    }

    /**
     *
     * @param ServicioAT $model
     *
     * @return array
     */
    private function worksData(&$model): array
    {
        $result = [];
        foreach ($model->getTrabajos() as $work) {
            $result[] = [
                $this->i18n->trans('from-date') => $work->fechainicio,
                $this->i18n->trans('from-hour') => $work->horainicio,
                $this->i18n->trans('until-date') => $work->fechafin,
                $this->i18n->trans('until-hour') => $work->horafin,
                $this->i18n->trans('observations') => $work->observaciones
            ];
        }
        return $result;
    }
}
